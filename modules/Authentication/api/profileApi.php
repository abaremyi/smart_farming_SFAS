<?php
/**
 * SFAS — Profile API
 * File: modules/Authentication/api/profileApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/models/UserModel.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw,true) ?: array_merge($_POST,[]);
}
$input = array_merge($_GET,$input);

$db    = Database::getConnection();
$model = new UserModel($db);

switch ($action) {

    case 'change-password':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $current = $input['current_password'] ?? '';
        $new     = $input['new_password']     ?? '';
        $confirm = $input['confirm_password'] ?? '';
        if (!$current || !$new || !$confirm) { echo json_encode(['success'=>false,'message'=>'All fields required']); break; }
        if (strlen($new)<8)   { echo json_encode(['success'=>false,'message'=>'New password must be at least 8 characters']); break; }
        if ($new !== $confirm) { echo json_encode(['success'=>false,'message'=>'Passwords do not match']); break; }
        // Verify current password
        $me = $model->getUserById((int)$user->user_id);
        if (!$me || !password_verify($current,$me['password'])) {
            echo json_encode(['success'=>false,'message'=>'Current password is incorrect']); break;
        }
        $ok = $model->updateUser((int)$user->user_id, ['password'=>$new]);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Password updated successfully':'Update failed']);
        break;

    case 'get':
        $me = $model->getUserById((int)$user->user_id);
        if ($me) { unset($me['password'],$me['otp_code'],$me['otp_expiry']); }
        echo json_encode(['success'=>true,'data'=>$me]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
