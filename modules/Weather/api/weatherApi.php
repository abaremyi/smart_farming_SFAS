<?php
/**
 * SFAS — Weather API (PHP proxy + DB cache)
 * File: modules/Weather/api/weatherApi.php
 *
 * Proxies Open-Meteo (FREE, no key needed).
 * Caches in weather_cache table for 1 hour to limit requests.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once dirname(__DIR__,3).'/config/paths.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,3).'/helpers/AuthMiddleware.php';

$auth = new AuthMiddleware();
$auth->requireAuth(['weather.view']);

$action   = $_GET['action'] ?? 'current';
$district = trim($_GET['district'] ?? 'Nyagatare');

// District coordinates (Rwanda key districts)
$coords = [
    'Nyagatare' => ['lat'=>-1.2956, 'lon'=>30.3256],
    'Gatsibo'   => ['lat'=>-1.5833, 'lon'=>30.4333],
    'Kayonza'   => ['lat'=>-1.8931, 'lon'=>30.6522],
    'Kirehe'    => ['lat'=>-2.1564, 'lon'=>30.6703],
    'Musanze'   => ['lat'=>-1.4986, 'lon'=>29.6344],
    'Huye'      => ['lat'=>-2.5967, 'lon'=>29.7394],
    'Kigali'    => ['lat'=>-1.9441, 'lon'=>30.0619],
    'Rubavu'    => ['lat'=>-1.6817, 'lon'=>29.2461],
    'Muhanga'   => ['lat'=>-2.0822, 'lon'=>29.7553],
    'Ngoma'     => ['lat'=>-2.1494, 'lon'=>30.4958],
];

$loc = $coords[$district] ?? $coords['Nyagatare'];
$lat = $_GET['lat'] ?? $loc['lat'];
$lon = $_GET['lon'] ?? $loc['lon'];

$db = Database::getConnection();

// ── Try cache first (1 hour TTL) ──────────────────────────
$cacheKey = $district;
try {
    $cached = $db->prepare("SELECT data_json, fetched_at FROM weather_cache WHERE district=:d LIMIT 1");
    $cached->execute([':d' => $cacheKey]);
    $row = $cached->fetch();
    if ($row && (time() - strtotime($row['fetched_at'])) < 3600) {
        echo $row['data_json'];
        exit;
    }
} catch (Exception $e) { /* ignore cache errors, fetch fresh */ }

// ── Fetch from Open-Meteo (FREE API) ─────────────────────
$vars = [
    'current'  => 'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code,apparent_temperature',
    'hourly'   => 'temperature_2m,precipitation_probability,weather_code',
    'daily'    => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max,uv_index_max',
    'timezone' => 'Africa/Kigali',
    'forecast_days' => 7,
];

$url = 'https://api.open-meteo.com/v1/forecast?latitude='.$lat.'&longitude='.$lon;
foreach ($vars as $k => $v) {
    $url .= '&'.$k.'='.urlencode($v);
}

$ctx = stream_context_create(['http'=>['timeout'=>10]]);
$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) {
    // Try cURL as fallback
    $ch = curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10]);
    $raw = curl_exec($ch);
    curl_close($ch);
}

if (!$raw) {
    // Return stale cache if available
    if (!empty($row['data_json'])) {
        echo $row['data_json'];
    } else {
        http_response_code(503);
        echo json_encode(['success'=>false,'message'=>'Weather service unavailable']);
    }
    exit;
}

$data = json_decode($raw, true);
if (!isset($data['current'])) {
    http_response_code(502);
    echo json_encode(['success'=>false,'message'=>'Invalid weather response']);
    exit;
}

// Add meta
$data['meta'] = [
    'district'   => $district,
    'latitude'   => $lat,
    'longitude'  => $lon,
    'fetched_at' => date('Y-m-d H:i:s'),
    'success'    => true,
];

$json = json_encode($data);

// ── Cache result ───────────────────────────────────────────
try {
    $db->prepare(
        "INSERT INTO weather_cache (district,lat,lon,data_json,fetched_at)
         VALUES (:d,:la,:lo,:dj,NOW())
         ON DUPLICATE KEY UPDATE data_json=:dj2, fetched_at=NOW(), lat=:la2, lon=:lo2"
    )->execute([':d'=>$cacheKey,':la'=>$lat,':lo'=>$lon,':dj'=>$json,':dj2'=>$json,':la2'=>$lat,':lo2'=>$lon]);
} catch (Exception $e) { error_log('Weather cache: '.$e->getMessage()); }

echo $json;
