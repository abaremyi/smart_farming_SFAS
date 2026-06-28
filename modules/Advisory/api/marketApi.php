<?php
/**
 * SFAS — Market Prices API (COMPLETE with all actions)
 * File: modules/Advisory/api/marketApi.php
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
    // ── Price Management ──────────────────────────────
    case 'list':
        echo json_encode($ctrl->listPrices($input)); 
        break;
        
    case 'latest':
        echo json_encode($ctrl->latestPrices()); 
        break;
        
    case 'add':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        echo json_encode($ctrl->addPrice($input,(int)$user->user_id)); 
        break;
        
    case 'update':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        $auth->requireAuth(['market.manage']);
        echo json_encode($ctrl->updatePrice((int)($input['id']??0), $input, (int)$user->user_id)); 
        break;
        
    case 'delete':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        echo json_encode($ctrl->deletePrice((int)($input['id']??0))); 
        break;

    // ── Crop Management ────────────────────────────────
    case 'create-crop':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        $auth->requireAuth(['market.manage']);
        echo json_encode($ctrl->createCrop($input)); 
        break;
        
    case 'update-crop':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        $auth->requireAuth(['market.manage']);
        echo json_encode($ctrl->updateCrop((int)($input['id']??0), $input)); 
        break;
        
    case 'delete-crop':
        if($method!=='POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }
        $auth->requireAuth(['market.manage']);
        echo json_encode($ctrl->deleteCrop((int)($input['id']??0))); 
        break;

    case 'crops':
        echo json_encode($ctrl->crops()); 
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);
}