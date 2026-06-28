<?php
/**
 * SFAS — Profile API (v3)
 * File: modules/Authentication/api/profileApi.php
 *
 * Actions:
 *   GET  ?action=get            → return my profile data
 *   POST ?action=update-info    → update name/phone/district/sector/email
 *   POST ?action=upload-photo   → multipart file upload, saves to uploads/users/
 *   POST ?action=change-password → verify current, set new password
 *
 * Photo upload rules:
 *   - Max 2 MB
 *   - Allowed: jpg, jpeg, png, webp, gif
 *   - Verifies actual MIME (not just extension)
 *   - Deletes old photo from disk before saving new one
 *   - Stores relative path 'users/filename.ext' in users.photo column
 *   - Returns photo_url = full URL for live UI update without page reload
 *
 * IMPORTANT: Do NOT set Content-Type header for multipart uploads —
 *   the browser sets the correct boundary automatically.
 *   Only JSON endpoints need 'Content-Type: application/json'.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
require_once dirname(__DIR__, 1) . '/models/UserModel.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$myId   = (int)$user->user_id;

$db    = Database::getConnection();
$model = new UserModel($db);

// Parse JSON body only for non-file requests
$input = [];
if ($method === 'POST' && empty($_FILES)) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: (array)$_POST;
}
$input = array_merge($_GET, $input);

switch ($action) {

    /* ── GET profile ──────────────────────────────────── */
    case 'get':
        $me = $model->getUserById($myId);
        if ($me) {
            unset($me['password'], $me['otp_code'], $me['otp_expiry'], $me['perm_keys']);
            $me['photo_url'] = !empty($me['photo']) ? upload_url($me['photo']) : '';
        }
        echo json_encode(['success' => true, 'data' => $me]);
        break;

    /* ── UPDATE profile info ──────────────────────────── */
    case 'update-info':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }

        $update = [];

        $firstname = trim($input['firstname'] ?? '');
        $lastname  = trim($input['lastname']  ?? '');
        if ($firstname) $update['firstname'] = $firstname;
        if ($lastname)  $update['lastname']  = $lastname;

        if (isset($input['phone'])) {
            $update['phone'] = trim($input['phone']) ?: null;
        }
        if (isset($input['district'])) {
            $update['district'] = trim($input['district']) ?: null;
        }
        if (isset($input['sector'])) {
            $update['sector'] = trim($input['sector']) ?: null;
        }

        // Email change — validate uniqueness
        if (!empty($input['email'])) {
            $newEmail = strtolower(trim($input['email']));
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success'=>false,'message'=>'Invalid email address']);
                break;
            }
            if ($model->emailExists($newEmail, $myId)) {
                echo json_encode(['success'=>false,'message'=>'Email already used by another account']);
                break;
            }
            $update['email'] = $newEmail;
        }

        if (empty($update)) {
            echo json_encode(['success'=>false,'message'=>'Nothing to update']);
            break;
        }

        $ok = $model->updateUser($myId, $update);
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Profile updated successfully' : 'Update failed. Please try again.'
        ]);
        break;

    /* ── UPLOAD PHOTO ─────────────────────────────────── */
    case 'upload-photo':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }

        // Check file was received
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            echo json_encode(['success'=>false,'message'=>'No photo received. Make sure the input name is "photo"']);
            break;
        }

        $file = $_FILES['photo'];

        // PHP upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errMap = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (check php.ini upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit',
                UPLOAD_ERR_PARTIAL    => 'File only partially uploaded — try again',
                UPLOAD_ERR_NO_TMP_DIR => 'Server has no temp directory for uploads',
                UPLOAD_ERR_CANT_WRITE => 'Server cannot write to disk',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension',
            ];
            $msg = $errMap[$file['error']] ?? 'Upload error code ' . $file['error'];
            echo json_encode(['success'=>false,'message'=>$msg]);
            break;
        }

        // Size limit: 2 MB
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success'=>false,'message'=>'Photo must be smaller than 2 MB']);
            break;
        }

        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowedExts)) {
            echo json_encode(['success'=>false,'message'=>'Only JPG, PNG, WEBP or GIF photos are allowed']);
            break;
        }

        // Real MIME check (defence against disguised files)
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $realMime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($realMime, $allowedMimes)) {
            echo json_encode(['success'=>false,'message'=>'File is not a valid image (MIME check failed)']);
            break;
        }

        // Ensure uploads/users/ directory exists and is writable
        $uploadDir = UPLOADS_PATH . '/users/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode(['success'=>false,'message'=>'Cannot create upload directory. Check permissions on uploads/']);
                break;
            }
        }
        if (!is_writable($uploadDir)) {
            echo json_encode(['success'=>false,'message'=>'Upload directory is not writable. Run: chmod 755 uploads/users/']);
            break;
        }

        // Delete old photo from disk if it exists
        $me = $model->getUserById($myId);
        if (!empty($me['photo'])) {
            $oldPath = UPLOADS_PATH . '/' . ltrim($me['photo'], '/');
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Generate unique filename and move file
        $filename = bin2hex(random_bytes(10)) . '_' . $myId . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['success'=>false,'message'=>'Failed to save photo. Check uploads/users/ write permissions']);
            break;
        }

        // Save relative path to DB
        $relPath = 'users/' . $filename;
        $saved   = $model->updatePhoto($myId, $relPath);

        if (!$saved) {
            // File saved to disk but DB failed — clean up
            @unlink($destPath);
            echo json_encode(['success'=>false,'message'=>'Photo saved but database update failed']);
            break;
        }

        echo json_encode([
            'success'   => true,
            'message'   => 'Profile photo updated successfully',
            'photo'     => $relPath,
            'photo_url' => upload_url($relPath),
        ]);
        break;

    /* ── CHANGE PASSWORD ──────────────────────────────── */
    case 'change-password':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }

        $current = $input['current_password'] ?? '';
        $new     = $input['new_password']     ?? '';
        $confirm = $input['confirm_password'] ?? '';

        if (!$current || !$new || !$confirm) {
            echo json_encode(['success'=>false,'message'=>'All three password fields are required']);
            break;
        }
        if (strlen($new) < 8) {
            echo json_encode(['success'=>false,'message'=>'New password must be at least 8 characters']);
            break;
        }
        if ($new !== $confirm) {
            echo json_encode(['success'=>false,'message'=>'New passwords do not match']);
            break;
        }

        // Verify current password against DB hash
        $me = $model->getUserById($myId);
        if (!$me || !password_verify($current, $me['password'])) {
            echo json_encode(['success'=>false,'message'=>'Current password is incorrect']);
            break;
        }

        $ok = $model->updateUser($myId, ['password' => $new]);
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Password changed successfully' : 'Password change failed'
        ]);
        break;

    /* ── UNKNOWN ──────────────────────────────────────── */
    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: ' . htmlspecialchars($action)]);
}