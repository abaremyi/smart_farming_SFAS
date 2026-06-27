<?php
/**
 * SFAS — Reports API
 * File: modules/Advisory/api/reportApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';
require_once dirname(__DIR__,1).'/controllers/AdvisoryController.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth(['reports.view']);
$action = $_GET['action'] ?? '';
$ctrl   = new AdvisoryController();

switch ($action) {
    case 'summary':
        echo json_encode($ctrl->reportSummary()); break;
    case 'by-category':
        echo json_encode($ctrl->reportByCategory()); break;
    case 'by-severity':
        echo json_encode($ctrl->reportAlertsBySeverity()); break;
    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
