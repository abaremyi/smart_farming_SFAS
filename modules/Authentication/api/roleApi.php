<?php
/**
 * SFAS — Role & Permissions API
 * File: modules/Authentication/api/roleApi.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth(['settings.manage']);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];
if (in_array($method, ['POST','PUT'])) {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: array_merge($_POST, []);
}
$input = array_merge($_GET, $input);
$db    = Database::getConnection();

switch ($action) {

    /* ── LIST ROLES ──────────────────────────────────────── */
    case 'list-roles':
        $roles = $db->query(
            "SELECT r.*, COUNT(DISTINCT u.id) AS user_count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id ORDER BY r.id"
        )->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'data'=>$roles]);
        break;

    /* ── LIST PERMISSIONS ────────────────────────────────── */
    case 'list-permissions':
        $perms = $db->query(
            "SELECT * FROM permissions ORDER BY module, action"
        )->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true,'data'=>$perms]);
        break;

    /* ── GET ROLE PERMISSIONS ────────────────────────────── */
    case 'role-permissions':
        $roleId = (int)($input['role_id'] ?? 0);
        if (!$roleId) { echo json_encode(['success'=>false,'message'=>'role_id required']); break; }
        $stmt = $db->prepare(
            "SELECT permission_id FROM role_permissions WHERE role_id=:rid"
        );
        $stmt->execute([':rid' => $roleId]);
        $permIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success'=>true,'data'=>array_map('intval', $permIds)]);
        break;

    /* ── SAVE ROLE PERMISSIONS ───────────────────────────── */
    case 'save-role-permissions':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $roleId  = (int)($input['role_id']     ?? 0);
        $permIds = $input['permission_ids']    ?? [];
        if (!$roleId) { echo json_encode(['success'=>false,'message'=>'role_id required']); break; }

        // Prevent editing super-admin role (id=1)
        if ($roleId === 1) { echo json_encode(['success'=>false,'message'=>'Cannot modify Admin role permissions']); break; }

        $db->prepare("DELETE FROM role_permissions WHERE role_id=:rid")->execute([':rid'=>$roleId]);
        if (!empty($permIds)) {
            $ins = $db->prepare("INSERT IGNORE INTO role_permissions (role_id,permission_id) VALUES (:rid,:pid)");
            foreach ($permIds as $pid) {
                $ins->execute([':rid'=>$roleId,':pid'=>(int)$pid]);
            }
        }
        echo json_encode(['success'=>true,'message'=>'Permissions saved successfully']);
        break;

    /* ── CREATE ROLE ─────────────────────────────────────── */
    case 'create-role':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $name = trim($input['name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if (!$name) { echo json_encode(['success'=>false,'message'=>'Role name required']); break; }
        try {
            $stmt = $db->prepare("INSERT INTO roles (name,description) VALUES (:n,:d)");
            $stmt->execute([':n'=>$name,':d'=>$desc?:null]);
            echo json_encode(['success'=>true,'message'=>'Role created','id'=>(int)$db->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success'=>false,'message'=>'Role name already exists']);
        }
        break;

    /* ── UPDATE ROLE ─────────────────────────────────────── */
    case 'update-role':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id   = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if (!$id || !$name) { echo json_encode(['success'=>false,'message'=>'id and name required']); break; }
        if ($id === 1) { echo json_encode(['success'=>false,'message'=>'Cannot rename Admin role']); break; }
        $db->prepare("UPDATE roles SET name=:n,description=:d WHERE id=:id")
           ->execute([':n'=>$name,':d'=>$desc?:null,':id'=>$id]);
        echo json_encode(['success'=>true,'message'=>'Role updated']);
        break;

    /* ── DELETE ROLE ─────────────────────────────────────── */
    case 'delete-role':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'id required']); break; }
        if (in_array($id,[1,2,3])) { echo json_encode(['success'=>false,'message'=>'Cannot delete default system roles']); break; }
        // Check no users on this role
        $count = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id=:id");
        $count->execute([':id'=>$id]);
        if ((int)$count->fetchColumn() > 0) {
            echo json_encode(['success'=>false,'message'=>'Cannot delete role — users are assigned to it. Reassign them first.']); break;
        }
        $db->prepare("DELETE FROM roles WHERE id=:id")->execute([':id'=>$id]);
        echo json_encode(['success'=>true,'message'=>'Role deleted']);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);
}