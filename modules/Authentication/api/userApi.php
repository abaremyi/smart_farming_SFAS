<?php
/**
 * SFAS — User API
 * File: modules/Authentication/api/userApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/models/UserModel.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth(['users.view']);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];
if (in_array($method,['POST','PUT'])) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw,true) ?: array_merge($_POST,[]);
}
$input = array_merge($_GET,$input);

$db    = Database::getConnection();
$model = new UserModel($db);

switch ($action) {
    case 'list':
        $users = $model->getAllUsers($input);
        echo json_encode(['success'=>true,'data'=>$users]);
        break;

    case 'get':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        $u = $model->getUserById($id);
        if (!$u) { echo json_encode(['success'=>false,'message'=>'User not found']); break; }
        unset($u['password'],$u['otp_code'],$u['otp_expiry']);
        echo json_encode(['success'=>true,'data'=>$u]);
        break;

    case 'create':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        // Validate
        if (empty($input['firstname']) || empty($input['lastname'])) { echo json_encode(['success'=>false,'message'=>'First and last name required']); break; }
        if (empty($input['email'])) { echo json_encode(['success'=>false,'message'=>'Email required']); break; }
        if (!filter_var($input['email'],FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Invalid email']); break; }
        if ($model->emailExists($input['email'])) { echo json_encode(['success'=>false,'message'=>'Email already registered']); break; }
        if (empty($input['password']) || strlen($input['password'])<8) { echo json_encode(['success'=>false,'message'=>'Password must be at least 8 characters']); break; }
        if ($input['password'] !== ($input['confirm_password']??'')) { echo json_encode(['success'=>false,'message'=>'Passwords do not match']); break; }

        $uid = $model->createUser([
            'firstname'      => trim($input['firstname']),
            'lastname'       => trim($input['lastname']),
            'email'          => strtolower(trim($input['email'])),
            'phone'          => $input['phone'] ?? null,
            'role_id'        => (int)($input['role_id'] ?? 3),
            'role_name'      => $input['role_name'] ?? 'Farmer',
            'district'       => $input['district'] ?? null,
            'password'       => $input['password'],
            'account_status' => $input['account_status'] ?? 'active',
            'created_by'     => $user->user_id,
        ]);
        echo json_encode(['success'=>true,'message'=>'User created successfully','id'=>$uid]);
        break;

    case 'update':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        $ok = $model->updateUser($id, $input);
        echo json_encode(['success'=>$ok,'message'=>$ok?'User updated':'Update failed']);
        break;

    case 'update-status':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id     = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        if (!$id || !in_array($status,['active','pending','inactive','suspended'])) {
            echo json_encode(['success'=>false,'message'=>'Invalid id or status']); break;
        }
        // Prevent self-deactivation
        if ((int)$id === (int)$user->user_id) {
            echo json_encode(['success'=>false,'message'=>'You cannot change your own status']); break;
        }
        $ok = $model->updateStatus($id,$status);
        echo json_encode(['success'=>$ok,'message'=>$ok?'Status updated to '.$status:'Update failed']);
        break;

    case 'delete':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        if ((int)$id === (int)$user->user_id) { echo json_encode(['success'=>false,'message'=>'You cannot delete your own account']); break; }
        try {
            $ok = $model->deleteUser($id);
            echo json_encode(['success'=>$ok,'message'=>$ok?'User deleted':'Delete failed']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
        break;

    case 'stats':
        echo json_encode(['success'=>true,'data'=>$model->getUserStats()]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);
}
