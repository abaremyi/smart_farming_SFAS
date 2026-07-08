<?php
/**
 * SFAS — IoT Sensor API
 * File: modules/IoT/api/iotApi.php
 *
 * Actions (PUBLIC - no JWT needed for sensor ingestion):
 *   POST ?action=ingest       → receive reading from ESP8266 (uses IOT_SECRET key)
 *
 * Actions (PROTECTED - JWT required):
 *   GET  ?action=latest       → most recent reading per device
 *   GET  ?action=history      → readings for last N hours (?hours=24)
 *   GET  ?action=devices      → list registered devices
 *   GET  ?action=stats        → min/max/avg for last 24h
 *   POST ?action=clear-old    → delete readings older than 30 days (admin only)
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];

if ($method === 'POST') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: array_merge($_POST, []);
}
$input = array_merge($_GET, $input);

$db = Database::getConnection();

/* ══════════════════════════════════════════════════════
   PUBLIC ACTION — ingest sensor reading
   The ESP8266 posts here every 30 seconds.
   Protected by shared secret (not JWT) because the
   microcontroller cannot handle JWT authentication.
══════════════════════════════════════════════════════ */
if ($action === 'ingest') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success'=>false,'message'=>'POST required']);
        exit;
    }

    // Validate shared secret
    $secret = $_ENV['IOT_SECRET'] ?? 'sfas_iot_secret_2026';
    if (($input['key'] ?? '') !== $secret) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Invalid IoT key']);
        exit;
    }

    // Validate required fields
    $temp = isset($input['temperature']) ? (float)$input['temperature'] : null;
    $hum  = isset($input['humidity'])    ? (float)$input['humidity']    : null;

    if ($temp !== null && ($temp < -40 || $temp > 85)) {
        echo json_encode(['success'=>false,'message'=>'Temperature out of DHT22 range (-40 to 85°C)']);
        exit;
    }
    if ($hum !== null && ($hum < 0 || $hum > 100)) {
        echo json_encode(['success'=>false,'message'=>'Humidity must be 0–100%']);
        exit;
    }

    $deviceId = trim($input['device_id'] ?? 'SFAS-NODE-01');
    $location = trim($input['location']  ?? 'Nyagatare Farm');

    // Insert reading
    $stmt = $db->prepare(
        "INSERT INTO iot_readings
         (device_id, location, temperature, humidity, soil_moisture, rainfall_mm, light_lux, battery_pct, raw_json)
         VALUES (:dev, :loc, :t, :h, :sm, :r, :lx, :bat, :raw)"
    );
    $stmt->execute([
        ':dev' => $deviceId,
        ':loc' => $location,
        ':t'   => $temp,
        ':h'   => $hum,
        ':sm'  => isset($input['soil_moisture']) ? (float)$input['soil_moisture'] : null,
        ':r'   => isset($input['rainfall_mm'])   ? (float)$input['rainfall_mm']   : null,
        ':lx'  => isset($input['light_lux'])     ? (float)$input['light_lux']     : null,
        ':bat' => isset($input['battery_pct'])   ? (int)$input['battery_pct']     : null,
        ':raw' => $raw ?? null,
    ]);
    $newId = (int)$db->lastInsertId();

    // Update last_seen on device registry
    $db->prepare(
        "INSERT INTO iot_devices (device_id, name, location, last_seen)
         VALUES (:dev, :dev, :loc, NOW())
         ON DUPLICATE KEY UPDATE last_seen=NOW(), location=:loc"
    )->execute([':dev'=>$deviceId, ':loc'=>$location]);

    echo json_encode(['success'=>true, 'id'=>$newId, 'device_id'=>$deviceId]);
    exit;
}

/* ══════════════════════════════════════════════════════
   PROTECTED ACTIONS — require JWT via AuthMiddleware
══════════════════════════════════════════════════════ */
require_once dirname(__DIR__, 3) . '/helpers/AuthMiddleware.php';
$auth = new AuthMiddleware();
$user = $auth->requireAuth(['weather.view']); // same permission as weather module

switch ($action) {

    /* ── LATEST reading (one per device) ────────────── */
    case 'latest':
        $deviceId = $input['device_id'] ?? null;
        if ($deviceId) {
            $stmt = $db->prepare(
                "SELECT * FROM iot_readings WHERE device_id=:dev ORDER BY recorded_at DESC LIMIT 1"
            );
            $stmt->execute([':dev' => $deviceId]);
        } else {
            // Latest reading across ALL devices
            $stmt = $db->query(
                "SELECT r.* FROM iot_readings r
                 INNER JOIN (
                   SELECT device_id, MAX(recorded_at) AS max_t FROM iot_readings GROUP BY device_id
                 ) latest ON r.device_id=latest.device_id AND r.recorded_at=latest.max_t
                 ORDER BY r.recorded_at DESC"
            );
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = $deviceId ? ($rows[0] ?? null) : $rows;
        echo json_encode(['success'=>true, 'data'=>$data]);
        break;

    /* ── HISTORY for last N hours ────────────────────── */
    case 'history':
        $hours    = min((int)($input['hours'] ?? 24), 168); // max 7 days
        $deviceId = $input['device_id'] ?? 'SFAS-NODE-01';
        $stmt = $db->prepare(
            "SELECT id, device_id, location, temperature, humidity,
                    soil_moisture, rainfall_mm, battery_pct, recorded_at
             FROM iot_readings
             WHERE device_id=:dev
               AND recorded_at >= NOW() - INTERVAL :h HOUR
             ORDER BY recorded_at ASC
             LIMIT 500"
        );
        $stmt->execute([':dev'=>$deviceId, ':h'=>$hours]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true, 'data'=>$rows, 'count'=>count($rows)]);
        break;

    /* ── STATS for last 24h ──────────────────────────── */
    case 'stats':
        $deviceId = $input['device_id'] ?? 'SFAS-NODE-01';
        $stmt = $db->prepare(
            "SELECT
               COUNT(*)          AS total_readings,
               MIN(temperature)  AS temp_min,
               MAX(temperature)  AS temp_max,
               AVG(temperature)  AS temp_avg,
               MIN(humidity)     AS hum_min,
               MAX(humidity)     AS hum_max,
               AVG(humidity)     AS hum_avg,
               AVG(soil_moisture)AS soil_avg,
               SUM(rainfall_mm)  AS rain_total,
               MIN(recorded_at)  AS period_start,
               MAX(recorded_at)  AS period_end
             FROM iot_readings
             WHERE device_id=:dev
               AND recorded_at >= NOW() - INTERVAL 24 HOUR"
        );
        $stmt->execute([':dev'=>$deviceId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        // Round values
        foreach (['temp_min','temp_max','temp_avg','hum_min','hum_max','hum_avg','soil_avg'] as $k) {
            if ($stats[$k] !== null) $stats[$k] = round((float)$stats[$k], 1);
        }
        echo json_encode(['success'=>true, 'data'=>$stats]);
        break;

    /* ── DEVICES list ────────────────────────────────── */
    case 'devices':
        $rows = $db->query(
            "SELECT d.*, 
               (SELECT COUNT(*) FROM iot_readings r WHERE r.device_id=d.device_id) AS total_readings,
               (SELECT recorded_at FROM iot_readings r WHERE r.device_id=d.device_id ORDER BY recorded_at DESC LIMIT 1) AS last_reading
             FROM iot_devices d ORDER BY d.created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success'=>true, 'data'=>$rows]);
        break;

    /* ── CLEAR old readings (admin only) ─────────────── */
    case 'clear-old':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); break; }
        if (!$user->is_super_admin) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin only']); break; }
        $days = (int)($input['days'] ?? 30);
        $stmt = $db->prepare("DELETE FROM iot_readings WHERE recorded_at < NOW() - INTERVAL :d DAY");
        $stmt->execute([':d'=>$days]);
        echo json_encode(['success'=>true,'message'=>'Deleted readings older than '.$days.' days','deleted'=>$stmt->rowCount()]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Unknown action: '.htmlspecialchars($action)]);
}
