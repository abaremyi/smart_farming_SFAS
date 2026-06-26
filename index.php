<?php
/**
 * SFAS — Main Router
 * File: index.php
 *
 * All requests route through here via .htaccess.
 * Add new routes in the $routes array.
 */
require_once 'config/paths.php';

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path   = str_replace('/index.php', '', $script_name);

if (strpos($request_uri, $base_path) === 0)
    $request_uri = substr($request_uri, strlen($base_path));

$parsed = parse_url($request_uri);
$path   = isset($parsed['path']) ? rtrim($parsed['path'],'/') : '';
$query  = $parsed['query'] ?? '';

$routes = [
    /* ── Public ─────────────────────────────────────────── */
    ''                              => 'modules/Authentication/views/login.php',
    '/'                             => 'modules/Authentication/views/login.php',
    '/login'                        => 'modules/Authentication/views/login.php',
    '/logout'                       => 'modules/Authentication/views/logout.php',
    '/forgot-password'              => 'modules/Authentication/views/login.php',

    /* ── Auth API ────────────────────────────────────────── */
    '/api/auth'                     => 'modules/Authentication/api/authApi.php',
    '/api/users'                    => 'modules/Authentication/api/userApi.php',
    '/api/roles'                    => 'modules/Authentication/api/roleApi.php',
    '/api/profile'                  => 'modules/Authentication/api/profileApi.php',

    /* ── AI API ──────────────────────────────────────────── */
    '/api/ai/chat'                  => 'modules/AI/api/chatApi.php',

    /* ── Weather API ─────────────────────────────────────── */
    '/api/weather'                  => 'modules/Weather/api/weatherApi.php',

    /* ── Farm & Crop API ─────────────────────────────────── */
    '/api/farms'                    => 'modules/Farm/api/farmApi.php',
    '/api/crops'                    => 'modules/Farm/api/cropApi.php',

    /* ── Advisory API ────────────────────────────────────── */
    '/api/advisory'                 => 'modules/Advisory/api/advisoryApi.php',
    '/api/alerts'                   => 'modules/Advisory/api/alertApi.php',
    '/api/market'                   => 'modules/Advisory/api/marketApi.php',

    /* ── Admin: Dashboard ────────────────────────────────── */
    '/admin/dashboard'              => 'modules/Authentication/views/dashboard.php',

    /* ── Admin: Users ────────────────────────────────────── */
    '/admin/users-management'       => 'modules/Authentication/views/users-management.php',
    '/admin/users-add-user'         => 'modules/Authentication/views/users-add-user.php',
    '/admin/users-view'             => 'modules/Authentication/views/users-view.php',
    '/admin/roles-permissions-management' => 'modules/Authentication/views/roles-permissions-management.php',
    '/admin/profile'                => 'modules/Authentication/views/profile.php',
    '/admin/profile-settings'       => 'modules/Authentication/views/profile-settings.php',

    /* ── Admin: Farms & Crops ─────────────────────────────── */
    '/admin/farms'                  => 'modules/Farm/views/farms.php',
    '/admin/farms/create'           => 'modules/Farm/views/farm-create.php',
    '/admin/farms/edit'             => 'modules/Farm/views/farm-edit.php',
    '/admin/crops'                  => 'modules/Farm/views/crops.php',

    /* ── Admin: Advisory ──────────────────────────────────── */
    '/admin/advisory'               => 'modules/Advisory/views/advisory.php',
    '/admin/advisory/create'        => 'modules/Advisory/views/advisory-create.php',
    '/admin/advisory/edit'          => 'modules/Advisory/views/advisory-edit.php',
    '/admin/alerts'                 => 'modules/Advisory/views/alerts.php',
    '/admin/market'                 => 'modules/Advisory/views/market.php',

    /* ── Admin: AI ────────────────────────────────────────── */
    '/admin/ai-assistant'           => 'modules/AI/views/ai-assistant.php',

    /* ── Admin: Weather ───────────────────────────────────── */
    '/admin/weather'                => 'modules/Weather/views/weather.php',

    /* ── Admin: Reports ───────────────────────────────────── */
    '/admin/reports'                => 'modules/Advisory/views/reports.php',
    '/api/reports'                  => 'modules/Advisory/api/reportApi.php',
];

if (array_key_exists($path, $routes)) {
    $file = ROOT_PATH.'/'.$routes[$path];
    if (file_exists($file)) {
        if (!empty($query)) parse_str($query, $_GET);
        require_once $file;
    } else {
        http_response_code(404);
        _serve404($path);
    }
} else {
    http_response_code(404);
    _serve404($path);
}

function _serve404(string $path): void {
    $f = ROOT_PATH.'/modules/Authentication/views/404.php';
    if (file_exists($f)) { require_once $f; return; }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 · SFAS</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f3fbf6;}
    .box{text-align:center;}.box h1{font-size:5rem;font-weight:900;color:#1a5c2a;margin:0;}
    .box p{color:#64748b;margin:.5rem 0 1.5rem;}.box a{background:#2d9a4e;color:#fff;padding:.75rem 1.75rem;border-radius:99px;text-decoration:none;font-weight:700;}
    </style></head><body><div class="box"><h1>404</h1>
    <p>Page not found: <code>'.htmlspecialchars($path).'</code></p>
    <a href="'.(defined('BASE_URL')?BASE_URL:'/').'">Go Home</a></div></body></html>';
}
