<?php
/**
 * SFAS — Alert API
 * File: modules/Advisory/api/alertApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/controllers/AdvisoryController.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];
if (in_array($method,['POST','PUT'])) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw,true) ?: array_merge($_POST,[]);
}
$input = array_merge($_GET,$input);
$ctrl = new AdvisoryController();

switch ($action) {
    case 'list':
        echo json_encode($ctrl->listAlerts($input)); break;
    case 'create':
        if($method!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'POST required']);break;}
        echo json_encode($ctrl->createAlert($input,(int)$user->user_id)); break;
    case 'toggle':
        if($method!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'POST required']);break;}
        $active = (int)($input['active'] ?? 1);
        echo json_encode($ctrl->toggleAlert((int)($input['id']??0),$active)); break;
    case 'delete':
        if($method!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'POST required']);break;}
        echo json_encode($ctrl->deleteAlert((int)($input['id']??0))); break;
    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
