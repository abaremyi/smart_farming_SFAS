<?php
/**
 * SFAS — Settings API
 * File: modules/Settings/api/settingsApi.php
 *
 * Actions:
 *   POST ?action=test-email        → sends a test email to the current admin
 *   POST ?action=clear-weather-cache → truncates weather_cache table
 *   GET  ?action=system-info       → returns PHP/DB/upload diagnostics as JSON
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';

use PHPMailer\PHPMailer\PHPMailer;

$auth   = new AuthMiddleware();
$user   = $auth->requireAuth(['settings.manage']);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = Database::getConnection();

switch ($action) {

    /* ── TEST EMAIL ──────────────────────────────────── */
    case 'test-email':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }

        if (!defined('SMTP_USER') || empty(SMTP_USER)) {
            echo json_encode(['success'=>false,'message'=>'SMTP not configured. Add SMTP_USER and SMTP_PASS to your .env file.']);
            break;
        }

        // Get admin email from JWT
        $adminEmail = $user->email ?? '';
        $adminName  = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: 'Admin';

        if (!$adminEmail) {
            echo json_encode(['success'=>false,'message'=>'Could not determine your email address from session.']);
            break;
        }

        try {
            $m = new PHPMailer(true);
            $m->isSMTP();
            $m->Host       = SMTP_HOST;
            $m->SMTPAuth   = true;
            $m->Username   = SMTP_USER;
            $m->Password   = SMTP_PASS;
            $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $m->Port       = 465;
            $m->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $m->addAddress($adminEmail, $adminName);
            $m->Subject = APP_NAME . ' — SMTP Test Email';
            $m->isHTML(true);
            $m->Body = '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:24px;background:#f3fbf6">
<div style="max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 4px 20px rgba(0,0,0,.08)">
  <div style="font-size:20px;font-weight:800;color:#1a5c2a;margin-bottom:8px;">🌱 SFAS</div>
  <h2 style="color:#1e293b;font-size:18px;margin:0 0 12px">SMTP Test Successful!</h2>
  <p style="color:#475569;line-height:1.6">
    This test email confirms that your SMTP configuration is working correctly.<br><br>
    Sent to: <strong>' . htmlspecialchars($adminEmail) . '</strong><br>
    Time: <strong>' . date('d M Y H:i:s') . '</strong>
  </p>
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-top:16px;font-size:13px;color:#14532d">
    ✅ OTP emails for registration and password reset will work correctly.
  </div>
</div></body></html>';
            $m->AltBody = 'SFAS SMTP Test — Email working correctly. Sent at ' . date('d M Y H:i:s');
            $m->send();
            echo json_encode(['success'=>true,'message'=>'Test email sent to ' . $adminEmail . '. Check your inbox!']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>'Email failed: ' . $e->getMessage()]);
        }
        break;

    /* ── CLEAR WEATHER CACHE ─────────────────────────── */
    case 'clear-weather-cache':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'POST required']);
            break;
        }

        try {
            $db->exec("TRUNCATE TABLE weather_cache");
            echo json_encode(['success'=>true,'message'=>'Weather cache cleared. Next weather page load will fetch fresh data from Open-Meteo.']);
        } catch (Exception $e) {
            echo json_encode(['success'=>false,'message'=>'Failed to clear cache: ' . $e->getMessage()]);
        }
        break;

    /* ── SYSTEM INFO ─────────────────────────────────── */
    case 'system-info':
        $info = [
            'php_version'     => PHP_VERSION,
            'php_ok'          => version_compare(PHP_VERSION, '8.0', '>='),
            'db_connected'    => true,
            'upload_dir_ok'   => is_writable(UPLOADS_PATH),
            'upload_users_ok' => is_dir(UPLOADS_PATH.'/users') ? is_writable(UPLOADS_PATH.'/users') : is_writable(UPLOADS_PATH),
            'smtp_set'        => defined('SMTP_USER') && !empty(SMTP_USER),
            'ai_key_set'      => defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY),
            'ai_key_prefix'   => defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)
                                  ? substr(ANTHROPIC_API_KEY, 0, 8) . '...'
                                  : 'not set',
            'tables' => [
                'users'         => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'farms'         => (int)$db->query("SELECT COUNT(*) FROM farms")->fetchColumn(),
                'advisory_tips' => (int)$db->query("SELECT COUNT(*) FROM advisory_tips WHERE is_active=1")->fetchColumn(),
                'market_prices' => (int)$db->query("SELECT COUNT(*) FROM market_prices")->fetchColumn(),
                'pest_alerts'   => (int)$db->query("SELECT COUNT(*) FROM pest_alerts WHERE is_active=1")->fetchColumn(),
                'ai_chats'      => (int)$db->query("SELECT COUNT(*) FROM ai_chat_logs WHERE role='user'")->fetchColumn(),
                'weather_cache' => (int)$db->query("SELECT COUNT(*) FROM weather_cache")->fetchColumn(),
            ],
        ];
        echo json_encode(['success'=>true,'data'=>$info]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: ' . htmlspecialchars($action)]);
}