<?php
/**
 * SFAS — Admin Base Guard
 * File: helpers/admin-base.php
 *
 * Validates JWT, loads user permissions, sets global convenience vars.
 * Follows the exact same pattern as the existing SIPIS admin-base.php.
 */
if (!defined('ROOT_PATH')) {
    $guessRoot = __DIR__;
    for ($i=0;$i<6;$i++) {
        if (file_exists($guessRoot.'/config/paths.php')) {
            require_once $guessRoot.'/config/paths.php'; break;
        }
        $guessRoot = dirname($guessRoot);
    }
    if (!defined('ROOT_PATH')) die('ROOT_PATH not defined.');
}
if (!defined('JWT_SECRET_KEY')) require_once ROOT_PATH.'/config/config.php';
require_once ROOT_PATH.'/config/database.php';
require_once ROOT_PATH.'/helpers/AuthMiddleware.php';
require_once ROOT_PATH.'/helpers/PermissionHelper.php';

if (session_status()===PHP_SESSION_NONE) session_start();

$_reqPerms = [];
if (!empty($requiredPermission))
    $_reqPerms = is_array($requiredPermission)?$requiredPermission:[$requiredPermission];

$_auth  = new AuthMiddleware();
$_token = $_COOKIE['auth_token'] ?? '';
if (!$_token) { header('Location: '.url('login')); exit; }

try { $currentUser = $_auth->requireAuth($_reqPerms); }
catch (Exception $e) {
    setcookie('auth_token','',time()-3600,'/');
    header('Location: '.url('logout')); exit;
}

define('SESSION_TIMEOUT',   1800);
define('LOCK_WARNING_TIME', 60);

if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();
$timeSinceActivity = time() - $_SESSION['last_activity'];
$sessionRemaining  = max(0, SESSION_TIMEOUT - $timeSinceActivity);

$isHeartbeat = isset($_SERVER['HTTP_X_HEARTBEAT'])
            || (($_GET['action']??'') === 'heartbeat')
            || (($_POST['action']??'') === 'heartbeat');
if (!$isHeartbeat) $_SESSION['last_activity'] = time();
$_SESSION['session_remaining'] = $sessionRemaining;

$userPermissions = (array)($currentUser->permissions ?? []);
$isSuperAdmin    = !empty($currentUser->is_super_admin) || ($currentUser->role_name??'') === 'Admin';

$userFullName = trim(($currentUser->firstname??'').' '.($currentUser->lastname??''));
if ($userFullName==='') $userFullName = $currentUser->email ?? 'User';
$userFullName = htmlspecialchars($userFullName);
$userInitials = strtoupper(substr($currentUser->firstname??'',0,1).substr($currentUser->lastname??'',0,1));
if ($userInitials==='') $userInitials = 'FA';
$userPhoto    = $currentUser->photo ?? '';
$sessionExpiryTimestamp = time() + $sessionRemaining;

if (empty($pageTitle))   $pageTitle   = 'SFAS';
if (empty($currentPage)) $currentPage = basename(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH));

unset($_reqPerms,$_token,$_auth,$timeSinceActivity,$isHeartbeat);
