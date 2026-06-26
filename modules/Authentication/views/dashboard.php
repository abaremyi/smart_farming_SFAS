<?php
/**
 * SFAS — Admin Dashboard
 * File: modules/Authentication/views/dashboard.php
 *
 * Shows: weather widget, farm stats, latest advisory tips,
 *        active pest alerts, market prices, AI chat shortcut.
 */
$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';

$db = Database::getConnection();

// ── Stats ──────────────────────────────────────────────────
$statsQ = $db->query("
  SELECT
    (SELECT COUNT(*) FROM users  WHERE account_status='active')  AS total_users,
    (SELECT COUNT(*) FROM users  WHERE account_status='pending') AS pending_users,
    (SELECT COUNT(*) FROM farms)                                 AS total_farms,
    (SELECT COUNT(*) FROM advisory_tips WHERE is_active=1)       AS active_tips,
    (SELECT COUNT(*) FROM pest_alerts   WHERE is_active=1)       AS active_alerts,
    (SELECT COUNT(*) FROM market_prices WHERE price_date=CURDATE()) AS prices_today
");
$stats = $statsQ->fetch(PDO::FETCH_ASSOC) ?: [];

// ── Latest Tips ───────────────────────────────────────────
$tips = $db->query("
  SELECT t.*, c.name AS crop_name
  FROM advisory_tips t
  LEFT JOIN crops c ON c.id=t.crop_id
  WHERE t.is_active=1
  ORDER BY t.created_at DESC LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// ── Active Pest Alerts ────────────────────────────────────
$alerts = $db->query("
  SELECT a.*, c.name AS crop_name
  FROM pest_alerts a
  LEFT JOIN crops c ON c.id=a.crop_id
  WHERE a.is_active=1
  ORDER BY FIELD(a.severity,'Critical','High','Medium','Low'), a.created_at DESC
  LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

// ── Market Prices ─────────────────────────────────────────
$prices = $db->query("
  SELECT mp.*, c.name AS crop_name
  FROM market_prices mp
  JOIN crops c ON c.id=mp.crop_id
  ORDER BY mp.price_date DESC, mp.id DESC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// ── Recent Farmers ────────────────────────────────────────
$farmers = $db->query("
  SELECT firstname,lastname,email,district,account_status,created_at
  FROM users WHERE role_id=3
  ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$severityColor = ['Low'=>'badge-green','Medium'=>'badge-amber','High'=>'badge-red','Critical'=>'badge-red'];
$categoryIcon  = [
  'Crop Management'=>'ri-seedling-line',
  'Pest & Disease'=>'ri-bug-line',
  'Soil Health'=>'ri-earth-line',
  'Irrigation'=>'ri-water-flash-line',
  'Harvest & Post-Harvest'=>'ri-scales-3-line',
  'Market'=>'ri-price-tag-3-line',
  'General'=>'ri-information-line',
];

require get_layout('admin-head');
?>

<style>
/* Dashboard-specific overrides */
.dash-weather{
  background:linear-gradient(135deg,var(--green-800) 0%,var(--green-600) 60%,var(--green-400) 100%);
  color:white;border-radius:var(--radius-lg);padding:1.5rem;
  position:relative;overflow:hidden;
}
.dash-weather::after{
  content:'🌿';position:absolute;right:1.5rem;top:50%;transform:translateY(-50%);
  font-size:4.5rem;opacity:.15;
}
.dash-weather .wt{font-size:2.8rem;font-weight:700;font-family:'Sora',sans-serif;line-height:1}
.dash-weather .wd{opacity:.85;margin-top:.3rem;font-size:.95rem}
.dash-weather .wmeta{display:flex;gap:1.5rem;margin-top:1.1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.25)}
.dash-weather .wmeta-item{font-size:.8rem;opacity:.85}
.dash-weather .wmeta-item strong{display:block;font-size:.95rem;opacity:1}
#weatherLoading{text-align:center;padding:1.5rem;opacity:.7}
.ai-shortcut{
  background:linear-gradient(135deg,var(--green-50),var(--gold-100));
  border:1px solid var(--green-200);border-radius:var(--radius-lg);
  padding:1.25rem 1.4rem;display:flex;align-items:center;gap:1rem;
  cursor:pointer;transition:var(--transition);text-decoration:none;
}
.ai-shortcut:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.ai-shortcut .ai-icon{
  width:52px;height:52px;border-radius:var(--radius-md);
  background:linear-gradient(135deg,var(--green-500),var(--gold-500));
  display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;color:white;flex-shrink:0;
}
</style>

<!-- Page Header -->
<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <i class="ri-home-4-line"></i><span>/</span><span>Dashboard</span>
    </div>
    <h1 class="page-title">Good <?= (date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening')) ?>, <?= $userFullName ?>!</h1>
    <p class="page-sub"><?= date('l, d F Y') ?> · Nyagatare District Smart Farming Advisory</p>
  </div>
  <?php if($isSuperAdmin||hasPermission($userPermissions,'advisory.create')): ?>
  <div class="flex gap-1">
    <a href="<?= url('admin/advisory/create') ?>" class="sfas-btn sfas-btn-primary">
      <i class="ri-add-line"></i> New Advisory Tip
    </a>
  </div>
  <?php endif; ?>
</div>

<!-- ── Stat Cards ─────────────────────────────────────────── -->
<div class="sfas-stat-grid">
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
    <div class="stat-icon"><i class="ri-group-line"></i></div>
    <div class="stat-val"><?= number_format($stats['total_users']??0) ?></div>
    <div class="stat-label">Active Users</div>
    <?php if(($stats['pending_users']??0)>0): ?>
    <div class="stat-change"><i class="ri-time-line"></i><?= $stats['pending_users'] ?> pending activation</div>
    <?php endif; ?>
  </div>

  <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
    <div class="stat-icon"><i class="ri-map-2-line"></i></div>
    <div class="stat-val"><?= number_format($stats['total_farms']??0) ?></div>
    <div class="stat-label">Registered Farms</div>
  </div>

  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
    <div class="stat-icon"><i class="ri-lightbulb-line"></i></div>
    <div class="stat-val"><?= number_format($stats['active_tips']??0) ?></div>
    <div class="stat-label">Advisory Tips</div>
  </div>

  <div class="sfas-stat" style="--stat-accent:var(--red-600);--stat-bg:var(--red-100)">
    <div class="stat-icon"><i class="ri-alarm-warning-line"></i></div>
    <div class="stat-val"><?= number_format($stats['active_alerts']??0) ?></div>
    <div class="stat-label">Active Pest Alerts</div>
  </div>
</div>

<!-- ── Main Grid ──────────────────────────────────────────── -->
<div class="sfas-grid sfas-grid-2" style="margin-bottom:1.25rem">

  <!-- Weather & AI shortcut column -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <!-- Weather Widget -->
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-cloud-line"></i> Current Weather · Nyagatare</span>
        <button onclick="loadWeather()" class="sfas-btn sfas-btn-ghost sfas-btn-sm">
          <i class="ri-refresh-line"></i>
        </button>
      </div>
      <div class="sfas-card-body" style="padding:0">
        <div id="weatherLoading" class="dash-weather" style="text-align:center">
          <span class="sfas-spinner"></span>
          <p style="margin-top:.5rem;opacity:.7;font-size:.85rem">Loading weather…</p>
        </div>
        <div id="weatherData" class="dash-weather" style="display:none"></div>
      </div>
    </div>

    <!-- AI Chat Shortcut -->
    <a href="<?= url('admin/ai-assistant') ?>" class="ai-shortcut">
      <div class="ai-icon"><i class="ri-robot-2-line"></i></div>
      <div>
        <div style="font-weight:700;font-size:1rem;color:var(--green-800)">Ask the AI Farming Assistant</div>
        <div style="font-size:.82rem;color:var(--text-muted);margin-top:.2rem">Get instant advice on crops, pests, soil, and markets</div>
      </div>
      <i class="ri-arrow-right-line" style="margin-left:auto;color:var(--green-500);font-size:1.25rem;flex-shrink:0"></i>
    </a>

    <!-- Market Prices -->
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-price-tag-3-line"></i> Market Prices</span>
        <a href="<?= url('admin/market') ?>" class="sfas-btn sfas-btn-ghost sfas-btn-sm">View all</a>
      </div>
      <div class="sfas-card-body" style="padding:.5rem 1.4rem">
        <?php if(empty($prices)): ?>
        <div class="sfas-empty" style="padding:1.5rem">
          <i class="ri-price-tag-line"></i><p>No prices recorded yet</p>
        </div>
        <?php else: ?>
        <?php foreach($prices as $p): ?>
        <div class="price-row">
          <div>
            <div class="price-crop"><?= htmlspecialchars($p['crop_name']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($p['market']) ?></div>
          </div>
          <div>
            <div class="price-val">RWF <?= number_format($p['price_rwf'],0) ?><span style="font-size:.7rem;font-weight:400">/<?= $p['unit'] ?></span></div>
            <div style="font-size:.72rem;color:var(--text-light);text-align:right"><?= date('d M',strtotime($p['price_date'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- Advisory Tips column -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-lightbulb-line"></i> Latest Advisory Tips</span>
        <a href="<?= url('admin/advisory') ?>" class="sfas-btn sfas-btn-ghost sfas-btn-sm">View all</a>
      </div>
      <div class="sfas-card-body" style="display:flex;flex-direction:column;gap:1rem">
        <?php if(empty($tips)): ?>
        <div class="sfas-empty">
          <i class="ri-lightbulb-line"></i>
          <h3>No tips yet</h3>
          <p>Create your first advisory tip to guide farmers.</p>
        </div>
        <?php else: ?>
        <?php foreach($tips as $tip): ?>
        <div class="tip-card">
          <div class="tip-category">
            <i class="<?= $categoryIcon[$tip['category']] ?? 'ri-information-line' ?>"></i>
            <?= htmlspecialchars($tip['category']) ?>
            <?php if($tip['crop_name']): ?>
            · <?= htmlspecialchars($tip['crop_name']) ?>
            <?php endif; ?>
          </div>
          <h3><?= htmlspecialchars($tip['title']) ?></h3>
          <p class="truncate" style="white-space:normal;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden">
            <?= htmlspecialchars($tip['content']) ?>
          </p>
          <div class="tip-meta">
            <span><i class="ri-calendar-line"></i> <?= date('d M Y',strtotime($tip['created_at'])) ?></span>
            <?php if($tip['district']): ?>
            <span><i class="ri-map-pin-line"></i> <?= htmlspecialchars($tip['district']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /right column -->

</div><!-- /main grid -->

<!-- ── Pest Alerts ─────────────────────────────────────────── -->
<?php if(!empty($alerts)): ?>
<div class="sfas-card" style="margin-bottom:1.25rem">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-alarm-warning-line"></i> Active Pest & Disease Alerts</span>
    <a href="<?= url('admin/alerts') ?>" class="sfas-btn sfas-btn-ghost sfas-btn-sm">Manage</a>
  </div>
  <div class="sfas-card-body">
    <div class="sfas-grid sfas-grid-2">
      <?php foreach($alerts as $a): ?>
      <div class="pest-card severity-<?= htmlspecialchars($a['severity']) ?>">
        <div class="pest-header">
          <h3><?= htmlspecialchars($a['title']) ?></h3>
          <span class="sfas-badge <?= $severityColor[$a['severity']] ?? 'badge-slate' ?>">
            <?= htmlspecialchars($a['severity']) ?>
          </span>
        </div>
        <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:.6rem;line-height:1.55">
          <?= htmlspecialchars(mb_substr($a['description'],0,140)).'…' ?>
        </p>
        <div style="font-size:.75rem;color:var(--text-light);display:flex;gap.75rem;flex-wrap:wrap">
          <?php if($a['crop_name']): ?><span style="margin-right:.6rem"><i class="ri-seedling-line"></i> <?= $a['crop_name'] ?></span><?php endif; ?>
          <?php if($a['district']): ?><span style="margin-right:.6rem"><i class="ri-map-pin-line"></i> <?= $a['district'] ?></span><?php endif; ?>
          <?php if($a['sector']): ?><span><i class="ri-community-line"></i> <?= $a['sector'] ?></span><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── Recent Farmers ──────────────────────────────────────── -->
<?php if($isSuperAdmin||hasPermission($userPermissions,'users.view')): ?>
<div class="sfas-card" style="margin-bottom:1.5rem">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-group-line"></i> Recently Registered Farmers</span>
    <a href="<?= url('admin/users-management') ?>" class="sfas-btn sfas-btn-ghost sfas-btn-sm">View all</a>
  </div>
  <div class="sfas-table-wrap">
    <table class="sfas-table">
      <thead>
        <tr>
          <th>Farmer</th><th>Email</th><th>District</th><th>Status</th><th>Joined</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($farmers)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem">No farmers registered yet.</td></tr>
        <?php else: ?>
        <?php foreach($farmers as $f): ?>
        <tr>
          <td><strong><?= htmlspecialchars($f['firstname'].' '.$f['lastname']) ?></strong></td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($f['email']) ?></td>
          <td><?= htmlspecialchars($f['district']??'—') ?></td>
          <td>
            <?php $sc=['active'=>'badge-green','pending'=>'badge-amber','inactive'=>'badge-slate','suspended'=>'badge-red']; ?>
            <span class="sfas-badge <?= $sc[$f['account_status']] ?? 'badge-slate' ?>">
              <?= ucfirst($f['account_status']) ?>
            </span>
          </td>
          <td style="color:var(--text-muted)"><?= date('d M Y',strtotime($f['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
/* ── Weather via Open-Meteo (free, no key needed) ────────── */
async function loadWeather(){
  document.getElementById('weatherLoading').style.display='flex';
  document.getElementById('weatherData').style.display='none';
  try{
    // Nyagatare coords
    const lat='-1.2956',lon='30.3256';
    const url=`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code&timezone=Africa%2FKigali`;
    const r=await fetch(url);
    const d=await r.json();
    const c=d.current;
    const wIcons={0:'☀️',1:'🌤️',2:'⛅',3:'☁️',45:'🌫️',48:'🌫️',51:'🌧️',61:'🌧️',63:'🌧️',80:'🌧️',95:'⛈️'};
    const wDesc={0:'Clear sky',1:'Mainly clear',2:'Partly cloudy',3:'Overcast',45:'Foggy',51:'Drizzle',61:'Rain',63:'Moderate rain',80:'Showers',95:'Thunderstorm'};
    const code=c.weather_code??0;
    const icon=wIcons[code]??'🌤️';
    const desc=wDesc[code]??'Unknown';
    document.getElementById('weatherData').innerHTML=`
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <div class="wt">${Math.round(c.temperature_2m)}°C</div>
          <div class="wd">${desc}</div>
        </div>
        <div style="font-size:3.5rem;opacity:.9">${icon}</div>
      </div>
      <div class="wmeta">
        <div class="wmeta-item"><strong>${c.relative_humidity_2m}%</strong>Humidity</div>
        <div class="wmeta-item"><strong>${c.precipitation??0} mm</strong>Precipitation</div>
        <div class="wmeta-item"><strong>${Math.round(c.wind_speed_10m)} km/h</strong>Wind</div>
        <div class="wmeta-item"><strong>Nyagatare</strong>District</div>
      </div>`;
    document.getElementById('weatherLoading').style.display='none';
    document.getElementById('weatherData').style.display='block';
  }catch(e){
    document.getElementById('weatherLoading').innerHTML=
      '<div style="padding:1rem;opacity:.7"><i class="ri-wifi-off-line" style="font-size:1.5rem"></i><p style="margin-top:.4rem;font-size:.82rem">Could not load weather. Check connection.</p></div>';
  }
}
loadWeather();
</script>

<?php require get_layout('admin-scripts'); ?>
