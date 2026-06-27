<?php
/**
 * SFAS — Farm API
 * File: modules/Farm/api/farmApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/controllers/FarmController.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];

if (in_array($method, ['POST','PUT','PATCH'])) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: array_merge($_POST, []);
}
$input = array_merge($_GET, $input);

$ctrl = new FarmController();

switch ($action) {
    case 'list':
        echo json_encode($ctrl->list($input)); break;

    case 'my':
        echo json_encode($ctrl->myFarms((int)$user->user_id)); break;

    case 'get':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        echo json_encode($ctrl->get($id)); break;

    case 'create':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->create($input, $user)); break;

    case 'update':
        if (!in_array($method,['POST','PUT'])) { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST/PUT required']); break; }
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        echo json_encode($ctrl->update($id, $input, $user)); break;

    case 'delete':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        echo json_encode($ctrl->delete($id, $user)); break;

    case 'add-crop':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        echo json_encode($ctrl->addCrop($input)); break;

    case 'remove-crop':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id = (int)($input['id'] ?? 0);
        echo json_encode($ctrl->removeCrop($id)); break;

    case 'crops':
        echo json_encode($ctrl->crops()); break;

    case 'stats':
        echo json_encode($ctrl->stats()); break;

    case 'farmers':
        echo json_encode($ctrl->farmers()); break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);
}
