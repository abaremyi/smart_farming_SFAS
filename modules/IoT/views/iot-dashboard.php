<?php
/**
 * SFAS — IoT Sensor Dashboard
 * File: modules/IoT/views/iot-dashboard.php
 *
 * Shows: live reading card, 24h line chart, stats, device list
 * Auto-refreshes every 30 seconds to show latest sensor data
 */
$pageTitle   = 'IoT Field Sensors';
$currentPage = 'iot';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require get_layout('admin-head');
?>

<style>
.iot-live-card {
  background: linear-gradient(135deg, var(--green-800), var(--green-600));
  color: white; border-radius: var(--radius-lg);
  padding: 1.5rem 1.75rem; position: relative; overflow: hidden;
}
.iot-live-card::after {
  content: '📡'; position: absolute; right: 1.5rem; top: 50%;
  transform: translateY(-50%); font-size: 4rem; opacity: .15; pointer-events: none;
}
.iot-metric {
  text-align: center; padding: 1.1rem .75rem;
  background: rgba(255,255,255,.12); border-radius: var(--radius-md);
  flex: 1; min-width: 110px;
}
.iot-metric .val {
  font-size: 2rem; font-weight: 800;
  font-family: 'JetBrains Mono', monospace; line-height: 1;
}
.iot-metric .lbl { font-size: .75rem; opacity: .8; margin-top: .3rem; }
.pulse {
  display: inline-block; width: 10px; height: 10px;
  border-radius: 50%; background: #4ade80;
  animation: pulse 2s ease-in-out infinite;
  margin-right: .35rem;
}
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
.offline-dot { background: #f87171; animation: none; }
</style>

<!-- Page Header -->
<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>IoT Sensors</span>
    </div>
    <h1 class="page-title">IoT Field Sensor Dashboard</h1>
    <p class="page-sub">Real-time data from ESP8266 sensor nodes deployed in the field</p>
  </div>
  <div style="display:flex;gap:.5rem;align-items:center">
    <select id="deviceSelect" class="sfas-select" style="width:200px" onchange="loadAll()">
      <option value="SFAS-NODE-01">Node 01 — Demo Farm</option>
    </select>
    <button class="sfas-btn sfas-btn-outline" onclick="loadAll()">
      <i class="ri-refresh-line"></i> Refresh
    </button>
  </div>
</div>

<!-- ── LIVE READING CARD ───────────────────────────────── -->
<div class="iot-live-card" style="margin-bottom:1.25rem" id="liveCard">
  <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
    <span class="pulse" id="statusDot"></span>
    <span style="font-size:.85rem;font-weight:600" id="statusText">Loading…</span>
    <span style="margin-left:auto;font-size:.78rem;opacity:.7" id="lastUpdated"></span>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap" id="metricsRow">
    <div class="iot-metric"><div class="val">—</div><div class="lbl">Temperature °C</div></div>
    <div class="iot-metric"><div class="val">—</div><div class="lbl">Humidity %</div></div>
  </div>
</div>

<!-- ── STATS + CHART ───────────────────────────────────── -->
<div class="sfas-grid sfas-grid-2" style="margin-bottom:1.25rem">

  <!-- 24h Stats -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-bar-chart-2-line"></i> Last 24 Hours — Statistics</span>
    </div>
    <div class="sfas-card-body" id="statsBody">
      <div style="text-align:center;color:var(--text-muted)"><span class="sfas-spinner"></span></div>
    </div>
  </div>

  <!-- Mini chart -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-line-chart-line"></i> Temperature & Humidity — Last 24h</span>
    </div>
    <div class="sfas-card-body">
      <canvas id="sensorChart" height="200"></canvas>
      <div id="chartEmpty" style="display:none" class="sfas-empty" style="padding:1.5rem">
        <i class="ri-line-chart-line"></i><p>No history data yet</p>
      </div>
    </div>
  </div>

</div>

<!-- ── COMPARISON: IoT vs Open-Meteo ──────────────────── -->
<div class="sfas-card" style="margin-bottom:1.25rem">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-scales-3-line"></i> Field Sensor vs. Weather Forecast Comparison</span>
  </div>
  <div class="sfas-card-body">
    <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1rem;line-height:1.6">
      The sensor measures actual on-farm conditions. Open-Meteo gives regional forecasts.
      Differences reveal local microclimates that affect pest risk and crop performance.
    </p>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem" id="comparisonGrid">
      <div style="text-align:center;padding:1rem;background:var(--green-50);border-radius:var(--radius-md)">
        <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.4rem">IoT Sensor</div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--green-700)" id="cmpIot">—°C</div>
        <div style="font-size:.75rem;color:var(--text-muted)">Field temperature</div>
      </div>
      <div style="text-align:center;padding:1rem;background:var(--blue-100);border-radius:var(--radius-md)">
        <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.4rem">Open-Meteo</div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--blue-500)" id="cmpWeather">—°C</div>
        <div style="font-size:.75rem;color:var(--text-muted)">Regional forecast</div>
      </div>
      <div style="text-align:center;padding:1rem;background:var(--amber-100);border-radius:var(--radius-md)">
        <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;margin-bottom:.4rem">Difference</div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--gold-600)" id="cmpDiff">—°C</div>
        <div style="font-size:.75rem;color:var(--text-muted)">Microclimate variation</div>
      </div>
    </div>
  </div>
</div>

<!-- ── HISTORY TABLE ───────────────────────────────────── -->
<div class="sfas-card">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-table-line"></i> Recent Readings</span>
    <select id="hoursSelect" class="sfas-select" style="width:130px;font-size:.8rem" onchange="loadHistory()">
      <option value="6">Last 6 hours</option>
      <option value="24" selected>Last 24 hours</option>
      <option value="48">Last 48 hours</option>
      <option value="168">Last 7 days</option>
    </select>
  </div>
  <div class="sfas-table-wrap">
    <table class="sfas-table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Device</th>
          <th>Temp (°C)</th>
          <th>Humidity (%)</th>
          <th>Soil Moisture (%)</th>
          <th>Rainfall (mm)</th>
          <th>Battery</th>
        </tr>
      </thead>
      <tbody id="historyBody">
        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">
          <span class="sfas-spinner"></span> Loading…
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Wiring guide collapsible -->
<div style="margin-top:1.25rem">
  <button class="sfas-btn sfas-btn-ghost" onclick="toggleWiring()" id="wiringBtn">
    <i class="ri-cpu-line"></i> Show ESP8266 Wiring Guide
  </button>
  <div id="wiringGuide" style="display:none;margin-top:.85rem">
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-cpu-line"></i> Hardware Connection Guide (ESP8266 + DHT22)</span>
      </div>
      <div class="sfas-card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;flex-wrap:wrap">
          <div>
            <h3 style="font-size:.9rem;font-weight:700;margin-bottom:.75rem;color:var(--green-700)">
              <i class="ri-plug-line"></i> Wiring Connections
            </h3>
            <table class="sfas-table" style="font-size:.82rem">
              <thead><tr><th>DHT22 Pin</th><th>ESP8266 Pin</th><th>Note</th></tr></thead>
              <tbody>
                <tr><td><strong>VCC (+)</strong></td><td>3.3V</td><td>Power</td></tr>
                <tr><td><strong>DATA</strong></td><td>D4 (GPIO2)</td><td>Signal pin</td></tr>
                <tr><td><strong>NC</strong></td><td>—</td><td>Not connected</td></tr>
                <tr><td><strong>GND (–)</strong></td><td>GND</td><td>Ground</td></tr>
              </tbody>
            </table>
            <div class="sfas-alert alert-info" style="margin-top:.75rem">
              <i class="ri-information-line"></i>
              <span>Add a 10kΩ pull-up resistor between the DHT22 DATA pin and VCC for stable readings.</span>
            </div>
          </div>
          <div>
            <h3 style="font-size:.9rem;font-weight:700;margin-bottom:.75rem;color:var(--green-700)">
              <i class="ri-settings-3-line"></i> Arduino IDE Setup
            </h3>
            <div style="font-size:.82rem;line-height:1.8;color:var(--text-muted)">
              <div><strong>1.</strong> Download Arduino IDE from arduino.cc</div>
              <div><strong>2.</strong> File → Preferences → Board Manager URL:</div>
              <div style="font-family:monospace;font-size:.75rem;background:var(--slate-100);padding:.35rem .6rem;border-radius:4px;margin:.25rem 0">
                https://arduino.esp8266.com/stable/package_esp8266com_index.json
              </div>
              <div><strong>3.</strong> Tools → Board Manager → search "ESP8266" → Install</div>
              <div><strong>4.</strong> Tools → Board → "NodeMCU 1.0 (ESP-12E)"</div>
              <div><strong>5.</strong> Sketch → Include Library → Manage Libraries → install "DHT sensor library" by Adafruit</div>
              <div><strong>6.</strong> Open <code>sfas_sensor.ino</code> → edit WiFi + URL → Upload</div>
            </div>
          </div>
        </div>
        <div style="margin-top:1rem">
          <div style="font-size:.82rem;font-weight:600;color:var(--slate-700);margin-bottom:.4rem">
            <i class="ri-key-line"></i> Add this to your .env file:
          </div>
          <div style="background:var(--slate-900);color:#e2e8f0;padding:.85rem 1rem;
            border-radius:var(--radius-md);font-family:'JetBrains Mono',monospace;font-size:.78rem">
            <span style="color:#34d399">IOT_SECRET</span>=<span style="color:#fbbf24">sfas_iot_secret_2026</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const B = window.BASE_URL;
let sensorChart = null;
let autoRefreshTimer = null;

/* ── Load everything ──────────────────────────────────── */
function loadAll() {
  loadLatest();
  loadStats();
  loadHistory();
  loadWeatherComparison();
}

/* ── Latest reading ───────────────────────────────────── */
async function loadLatest() {
  const dev = document.getElementById('deviceSelect').value;
  try {
    const r = await fetch(`${B}/api/iot?action=latest&device_id=${encodeURIComponent(dev)}`, {credentials:'include'});
    const d = await r.json();
    if (!d.success || !d.data) { setOffline(); return; }
    const s = d.data;
    const ageMs = Date.now() - new Date(s.recorded_at).getTime();
    const isOnline = ageMs < 120000; // online if reading < 2 minutes old

    const dot  = document.getElementById('statusDot');
    const txt  = document.getElementById('statusText');
    const upd  = document.getElementById('lastUpdated');
    dot.className  = 'pulse' + (isOnline ? '' : ' offline-dot');
    txt.textContent = isOnline ? `🟢 ONLINE — ${s.device_id} · ${s.location}` : `🔴 OFFLINE — Last seen ${timeSince(s.recorded_at)} ago`;
    upd.textContent = `Updated: ${new Date(s.recorded_at).toLocaleString()}`;

    // Build metrics
    const metrics = [];
    if (s.temperature  !== null) metrics.push(['temperature',  s.temperature  + '°C', 'Temperature']);
    if (s.humidity     !== null) metrics.push(['humidity',     s.humidity     + '%',  'Humidity']);
    if (s.soil_moisture!== null) metrics.push(['soil_moisture',s.soil_moisture+ '%',  'Soil Moisture']);
    if (s.rainfall_mm  !== null) metrics.push(['rainfall_mm',  s.rainfall_mm  + 'mm', 'Rainfall']);
    if (s.battery_pct  !== null) metrics.push(['battery',      s.battery_pct  + '%',  'Battery']);

    document.getElementById('metricsRow').innerHTML = metrics.map(([k,v,l]) =>
      `<div class="iot-metric"><div class="val">${v}</div><div class="lbl">${l}</div></div>`
    ).join('') || '<p style="opacity:.7">No data from sensor yet</p>';

    // Update comparison
    if (s.temperature !== null) {
      document.getElementById('cmpIot').textContent = s.temperature + '°C';
    }
  } catch(e) { setOffline(); }
}

function setOffline() {
  document.getElementById('statusDot').className = 'pulse offline-dot';
  document.getElementById('statusText').textContent = '🔴 Sensor offline or not yet configured';
  document.getElementById('metricsRow').innerHTML =
    '<p style="opacity:.7;font-size:.85rem">No readings received yet. Check sensor connection.</p>';
}

/* ── 24h Stats ────────────────────────────────────────── */
async function loadStats() {
  const dev = document.getElementById('deviceSelect').value;
  try {
    const r = await fetch(`${B}/api/iot?action=stats&device_id=${encodeURIComponent(dev)}`, {credentials:'include'});
    const d = await r.json();
    const s = d.data || {};
    document.getElementById('statsBody').innerHTML = s.total_readings > 0 ? `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        ${statCard('ri-temp-hot-line','Temp Min / Max / Avg',
          `${s.temp_min??'—'}°C / ${s.temp_max??'—'}°C / ${s.temp_avg??'—'}°C`, 'var(--red-100)','var(--red-600)')}
        ${statCard('ri-water-percent-line','Humidity Min / Max / Avg',
          `${s.hum_min??'—'}% / ${s.hum_max??'—'}% / ${s.hum_avg??'—'}%`, 'var(--blue-100)','var(--blue-500)')}
        ${s.soil_avg ? statCard('ri-earth-line','Avg Soil Moisture', s.soil_avg+'%','var(--amber-100)','var(--gold-600)') : ''}
        ${s.rain_total ? statCard('ri-drop-line','Total Rainfall', s.rain_total+'mm','var(--blue-100)','var(--blue-500)') : ''}
        ${statCard('ri-survey-line','Total Readings', s.total_readings+' in 24h','var(--green-100)','var(--green-600)')}
      </div>` : `<div class="sfas-empty"><i class="ri-bar-chart-2-line"></i><p>No data in the last 24 hours</p></div>`;
  } catch(e) {}
}

function statCard(icon, label, val, bg, color) {
  return `<div style="background:${bg};border-radius:var(--radius-md);padding:.85rem;text-align:center">
    <i class="${icon}" style="font-size:1.2rem;color:${color}"></i>
    <div style="font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin:.3rem 0">${label}</div>
    <div style="font-weight:700;font-size:.9rem;font-family:'JetBrains Mono',monospace;color:${color}">${val}</div>
  </div>`;
}

/* ── History + Chart ──────────────────────────────────── */
async function loadHistory() {
  const dev   = document.getElementById('deviceSelect').value;
  const hours = document.getElementById('hoursSelect').value;
  try {
    const r = await fetch(`${B}/api/iot?action=history&device_id=${encodeURIComponent(dev)}&hours=${hours}`, {credentials:'include'});
    const d = await r.json();
    const rows = d.data || [];

    // Table
    const tbody = document.getElementById('historyBody');
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">No readings in this period. Is the sensor powered on?</td></tr>';
    } else {
      // Show most recent 50 in table
      tbody.innerHTML = rows.slice().reverse().slice(0,50).map(row => `
        <tr>
          <td style="font-size:.8rem;color:var(--text-muted)">${new Date(row.recorded_at).toLocaleString()}</td>
          <td><span class="sfas-badge badge-green" style="font-size:.68rem">${esc(row.device_id)}</span></td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--red-600)">${row.temperature??'—'}</td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--blue-500)">${row.humidity??'—'}</td>
          <td>${row.soil_moisture??'—'}</td>
          <td>${row.rainfall_mm??'—'}</td>
          <td>${row.battery_pct!=null ? row.battery_pct+'%' : '—'}</td>
        </tr>`).join('');
    }

    // Chart
    if (rows.length > 1) {
      document.getElementById('chartEmpty').style.display = 'none';
      const labels = rows.map(r => new Date(r.recorded_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}));
      const temps  = rows.map(r => r.temperature);
      const hums   = rows.map(r => r.humidity);

      const ctx = document.getElementById('sensorChart').getContext('2d');
      if (sensorChart) sensorChart.destroy();
      sensorChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Temperature (°C)', data: temps, borderColor: '#dc2626', backgroundColor: '#dc262620',
              fill: true, tension: 0.4, pointRadius: 2, borderWidth: 2 },
            { label: 'Humidity (%)',      data: hums,  borderColor: '#3b82f6', backgroundColor: '#3b82f620',
              fill: true, tension: 0.4, pointRadius: 2, borderWidth: 2 },
          ]
        },
        options: {
          responsive: true, interaction: { mode: 'index', intersect: false },
          plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
          scales: { x: { ticks: { maxTicksLimit: 8, font: { size: 10 } } }, y: { beginAtZero: false } }
        }
      });
    } else {
      document.getElementById('chartEmpty').style.display = 'block';
    }
  } catch(e) {}
}

/* ── Compare with Open-Meteo ──────────────────────────── */
async function loadWeatherComparison() {
  try {
    const r = await fetch('https://api.open-meteo.com/v1/forecast?latitude=-1.2956&longitude=30.3256&current=temperature_2m&timezone=Africa/Kigali');
    const d = await r.json();
    const wTemp = d?.current?.temperature_2m;
    if (wTemp !== undefined) {
      document.getElementById('cmpWeather').textContent = Math.round(wTemp*10)/10 + '°C';
      // Calculate diff after IoT loads
      setTimeout(() => {
        const iotText = document.getElementById('cmpIot').textContent;
        const iotTemp = parseFloat(iotText);
        if (!isNaN(iotTemp)) {
          const diff = (iotTemp - wTemp).toFixed(1);
          document.getElementById('cmpDiff').textContent = (diff > 0 ? '+' : '') + diff + '°C';
        }
      }, 2000);
    }
  } catch(e) {}
}

/* ── Helpers ──────────────────────────────────────────── */
function timeSince(ts) {
  const s = Math.floor((Date.now() - new Date(ts).getTime()) / 1000);
  if (s < 60)   return s + 's';
  if (s < 3600) return Math.floor(s/60) + 'm';
  return Math.floor(s/3600) + 'h';
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function toggleWiring() {
  const g = document.getElementById('wiringGuide');
  const b = document.getElementById('wiringBtn');
  g.style.display = g.style.display==='none' ? 'block' : 'none';
  b.innerHTML = g.style.display==='none'
    ? '<i class="ri-cpu-line"></i> Show ESP8266 Wiring Guide'
    : '<i class="ri-cpu-line"></i> Hide Wiring Guide';
}

/* ── Auto-refresh every 30s ───────────────────────────── */
loadAll();
autoRefreshTimer = setInterval(loadAll, 30000);
</script>

<?php require get_layout('admin-scripts'); ?>
