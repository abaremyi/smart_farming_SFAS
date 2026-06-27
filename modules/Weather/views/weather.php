<?php
/**
 * SFAS — Weather Full View
 * File: modules/Weather/views/weather.php
 */
$pageTitle   = 'Weather Forecast';
$currentPage = 'weather';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require get_layout('admin-head');
?>

<style>
.weather-hero{
  background:linear-gradient(135deg,var(--green-800) 0%,var(--green-600) 60%,var(--green-400) 100%);
  border-radius:var(--radius-lg);color:white;padding:2rem;
  display:flex;justify-content:space-between;align-items:center;
  flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;
  position:relative;overflow:hidden;
}
.weather-hero::after{
  content:'';position:absolute;right:-20px;top:-20px;
  width:200px;height:200px;border-radius:50%;
  background:rgba(255,255,255,.06);pointer-events:none;
}
.weather-main-temp{font-size:4rem;font-weight:800;font-family:'Sora',sans-serif;line-height:1}
.weather-icon-big{font-size:4rem;filter:drop-shadow(0 2px 8px rgba(0,0,0,.3))}
.weather-meta{display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:1rem}
.weather-meta-item{font-size:.85rem;opacity:.88}
.weather-meta-item strong{display:block;font-size:1rem;font-weight:700}
.forecast-day{
  background:white;border:1px solid var(--border);border-radius:var(--radius-md);
  padding:.9rem .75rem;text-align:center;transition:var(--transition);
}
.forecast-day:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
.forecast-day .fday{font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase}
.forecast-day .ficon{font-size:1.8rem;margin:.3rem 0}
.forecast-day .fmax{font-size:1rem;font-weight:700;color:var(--slate-800)}
.forecast-day .fmin{font-size:.8rem;color:var(--text-muted)}
.forecast-day .frain{font-size:.75rem;color:var(--blue-500);margin-top:.3rem}
.season-pill{
  display:inline-flex;align-items:center;gap:.4rem;
  padding:.35rem .85rem;border-radius:var(--radius-full);
  font-size:.8rem;font-weight:600;
}
</style>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Weather</span>
    </div>
    <h1 class="page-title">Weather Forecast</h1>
    <p class="page-sub">Real-time weather data powered by Open-Meteo (free) for Rwanda</p>
  </div>
  <div style="display:flex;gap:.5rem;align-items:center">
    <select id="districtSelect" class="sfas-select" onchange="loadWeather()" style="width:160px">
      <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Musanze','Huye','Kigali','Rubavu','Muhanga','Ngoma'] as $d): ?>
      <option value="<?= $d ?>"><?= $d ?></option>
      <?php endforeach; ?>
    </select>
    <button class="sfas-btn sfas-btn-outline" onclick="loadWeather()"><i class="ri-refresh-line"></i></button>
  </div>
</div>

<!-- Loading state -->
<div id="weatherLoading" style="text-align:center;padding:4rem;color:var(--text-muted)">
  <span class="sfas-spinner" style="width:40px;height:40px;border-width:4px"></span>
  <p style="margin-top:1rem">Fetching weather data…</p>
</div>

<!-- Main Weather Content -->
<div id="weatherContent" style="display:none">

  <!-- Hero Current Weather -->
  <div class="weather-hero" id="weatherHero"></div>

  <!-- 7-Day Forecast -->
  <div class="sfas-card" style="margin-bottom:1.25rem">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-calendar-weather-line"></i> 7-Day Forecast</span>
    </div>
    <div class="sfas-card-body">
      <div id="forecastGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:.75rem"></div>
    </div>
  </div>

  <!-- Farming Calendar + Tips -->
  <div class="sfas-grid sfas-grid-2">

    <!-- Planting Calendar -->
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-calendar-2-line"></i> Rwanda Planting Calendar</span>
      </div>
      <div class="sfas-card-body">
        <?php
        $month = (int)date('n');
        $seasons = [
          ['name'=>'Season A','months'=>[9,10,11,12,1,2],'color'=>'var(--green-500)','desc'=>'September – February. Plant maize, beans, Irish potato.'],
          ['name'=>'Season B','months'=>[3,4,5,6,7,8],'color'=>'var(--gold-600)','desc'=>'March – August. Plant beans, sorghum, groundnut, vegetables.'],
          ['name'=>'Dry Season','months'=>[6,7,8],'color'=>'var(--amber-500)','desc'=>'June – August. Irrigated crops: onion, tomato, pepper.'],
        ];
        $currentSeason = '';
        foreach ($seasons as $s) {
            if (in_array($month,$s['months'])) { $currentSeason = $s['name']; break; }
        }
        ?>
        <div style="margin-bottom:1rem">
          <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.4rem">Current month:</div>
          <div style="font-weight:700;font-size:1.1rem"><?= date('F Y') ?></div>
          <?php if ($currentSeason): ?>
          <div style="margin-top:.5rem">
            <span class="sfas-badge badge-green" style="font-size:.85rem;padding:.35rem .9rem">
              <i class="ri-calendar-event-line"></i> <?= $currentSeason ?> is active
            </span>
          </div>
          <?php endif; ?>
        </div>

        <?php foreach ($seasons as $s): ?>
        <div style="margin-bottom:1rem;padding:.85rem;border-radius:var(--radius-md);
          background:<?= in_array($month,$s['months'])?'var(--green-50)':'var(--bg-muted)' ?>;
          border:1px solid <?= in_array($month,$s['months'])?'var(--green-300)':'var(--border)' ?>">
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
            <div style="width:10px;height:10px;border-radius:50%;background:<?= $s['color'] ?>;flex-shrink:0"></div>
            <strong style="font-size:.9rem"><?= $s['name'] ?></strong>
            <?php if (in_array($month,$s['months'])): ?>
            <span class="sfas-badge badge-green" style="font-size:.68rem">NOW</span>
            <?php endif; ?>
          </div>
          <p style="font-size:.82rem;color:var(--text-muted);margin:0"><?= $s['desc'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Weather-based farming advice -->
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-robot-2-line"></i> Weather-Based Farming Tips</span>
      </div>
      <div class="sfas-card-body" id="farmingTips">
        <div style="text-align:center;color:var(--text-muted);padding:1rem">
          <span class="sfas-spinner"></span>
          <p style="margin-top:.5rem;font-size:.85rem">Loading weather tips…</p>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Error state -->
<div id="weatherError" style="display:none">
  <div class="sfas-card">
    <div class="sfas-empty" style="padding:3rem">
      <i class="ri-wifi-off-line"></i>
      <h3>Could not load weather</h3>
      <p>Check your internet connection and try again.</p>
      <button class="sfas-btn sfas-btn-primary" onclick="loadWeather()" style="margin-top:1rem">
        <i class="ri-refresh-line"></i> Retry
      </button>
    </div>
  </div>
</div>

<script>
const B = window.BASE_URL;

const WX_ICONS = {
  0:'☀️',1:'🌤️',2:'⛅',3:'☁️',45:'🌫️',48:'🌫️',
  51:'🌦️',53:'🌧️',55:'🌧️',61:'🌧️',63:'🌧️',65:'🌧️',
  80:'🌦️',81:'🌧️',82:'⛈️',95:'⛈️',96:'⛈️',99:'⛈️'
};
const WX_DESC = {
  0:'Clear sky',1:'Mainly clear',2:'Partly cloudy',3:'Overcast',
  45:'Foggy',48:'Foggy',51:'Light drizzle',53:'Drizzle',
  61:'Light rain',63:'Moderate rain',65:'Heavy rain',
  80:'Showers',81:'Showers',82:'Heavy showers',
  95:'Thunderstorm',96:'Thunderstorm',99:'Thunderstorm'
};
const DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

async function loadWeather() {
  document.getElementById('weatherLoading').style.display='block';
  document.getElementById('weatherContent').style.display='none';
  document.getElementById('weatherError').style.display='none';

  const district = document.getElementById('districtSelect').value;

  try {
    const r = await fetch(`${B}/api/weather?action=current&district=${encodeURIComponent(district)}`,
      { credentials:'include' });
    const data = await r.json();
    if (!data.current) throw new Error('No data');

    renderHero(data, district);
    renderForecast(data.daily);
    renderFarmingTips(data.current);

    document.getElementById('weatherLoading').style.display='none';
    document.getElementById('weatherContent').style.display='block';
  } catch(e) {
    document.getElementById('weatherLoading').style.display='none';
    document.getElementById('weatherError').style.display='block';
  }
}

function renderHero(data, district) {
  const c    = data.current;
  const icon = WX_ICONS[c.weather_code] || '🌤️';
  const desc = WX_DESC[c.weather_code]  || 'Unknown';
  document.getElementById('weatherHero').innerHTML = `
    <div>
      <div style="font-size:.85rem;opacity:.75;margin-bottom:.5rem">
        <i class="ri-map-pin-line"></i> ${district} District, Rwanda
        <span style="margin-left:.75rem;font-size:.75rem;opacity:.65">Updated: ${new Date().toLocaleTimeString()}</span>
      </div>
      <div class="weather-main-temp">${Math.round(c.temperature_2m)}°C</div>
      <div style="font-size:1.1rem;margin-top:.4rem;opacity:.9">${desc}</div>
      <div style="font-size:.85rem;opacity:.75;margin-top:.2rem">Feels like ${Math.round(c.apparent_temperature||c.temperature_2m)}°C</div>
      <div class="weather-meta">
        <div class="weather-meta-item"><strong>${c.relative_humidity_2m}%</strong>Humidity</div>
        <div class="weather-meta-item"><strong>${c.precipitation??0} mm</strong>Precipitation</div>
        <div class="weather-meta-item"><strong>${Math.round(c.wind_speed_10m)} km/h</strong>Wind Speed</div>
      </div>
    </div>
    <div class="weather-icon-big">${icon}</div>`;
}

function renderForecast(daily) {
  if (!daily?.time) return;
  const grid = document.getElementById('forecastGrid');
  grid.innerHTML = daily.time.map((date, i) => {
    const d    = new Date(date+'T00:00:00');
    const icon = WX_ICONS[daily.weather_code[i]] || '🌤️';
    const rain = daily.precipitation_sum?.[i] ?? 0;
    const uv   = daily.uv_index_max?.[i] ?? 0;
    return `<div class="forecast-day">
      <div class="fday">${i===0?'Today':DAYS[d.getDay()]}</div>
      <div class="fday" style="font-size:.7rem;opacity:.7">${d.getDate()}/${d.getMonth()+1}</div>
      <div class="ficon">${icon}</div>
      <div class="fmax">${Math.round(daily.temperature_2m_max[i])}°</div>
      <div class="fmin">${Math.round(daily.temperature_2m_min[i])}°</div>
      ${rain>0?`<div class="frain"><i class="ri-drop-line"></i> ${rain.toFixed(1)}mm</div>`:''}
      ${uv>6?`<div style="font-size:.7rem;color:var(--red-600);margin-top:.2rem"><i class="ri-sun-line"></i> UV ${uv}</div>`:''}
    </div>`;
  }).join('');
}

function renderFarmingTips(current) {
  const tips = [];
  const temp = current.temperature_2m;
  const humidity = current.relative_humidity_2m;
  const rain = current.precipitation ?? 0;
  const wind = current.wind_speed_10m;

  if (temp > 30) tips.push({icon:'ri-temp-hot-line',color:'var(--red-600)',text:'High temperature ('+Math.round(temp)+'°C). Water crops early morning or evening to reduce heat stress. Mulch around plants to conserve soil moisture.'});
  else if (temp < 15) tips.push({icon:'ri-temp-cold-line',color:'var(--blue-500)',text:'Cool temperature ('+Math.round(temp)+'°C). Good for cool-season crops like cabbage, peas, and potato. Protect young seedlings from cold nights.'});
  else tips.push({icon:'ri-check-circle-line',color:'var(--green-500)',text:'Temperature ('+Math.round(temp)+'°C) is ideal for most Rwanda crops including maize, beans, and tomatoes.'});

  if (humidity > 85) tips.push({icon:'ri-water-percent-line',color:'var(--amber-500)',text:'High humidity ('+humidity+'%). Risk of fungal diseases — watch for blight, rust, and powdery mildew. Improve air circulation and apply preventive fungicide.'});
  else if (humidity < 40) tips.push({icon:'ri-drop-line',color:'var(--blue-500)',text:'Low humidity ('+humidity+'%). Increase irrigation frequency. Drip irrigation recommended to reduce evaporation.'});

  if (rain > 5) tips.push({icon:'ri-rain-line',color:'var(--blue-500)',text:'Significant rain today ('+rain+'mm). Hold off on irrigation. Check drainage channels. Avoid applying fertilizer immediately — it may wash away.'});
  if (rain === 0) tips.push({icon:'ri-sun-line',color:'var(--gold-600)',text:'No rain expected. Check soil moisture and irrigate if needed, especially for young seedlings and flowering crops.'});

  if (wind > 30) tips.push({icon:'ri-windy-line',color:'var(--amber-500)',text:'Strong winds ('+Math.round(wind)+' km/h). Stake tall crops like maize and tomatoes. Avoid spraying pesticides today — spray drift will reduce effectiveness.'});

  document.getElementById('farmingTips').innerHTML = tips.map(t=>`
    <div class="sfas-alert" style="border-color:${t.color};background:${t.color}11;margin-bottom:.6rem">
      <i class="${t.icon}" style="color:${t.color}"></i>
      <span style="font-size:.84rem">${t.text}</span>
    </div>`).join('') || '<div class="sfas-empty"><i class="ri-check-circle-line"></i><p>Weather conditions are normal. No special actions needed.</p></div>';
}

// Auto-load on page open
loadWeather();
</script>

<?php require get_layout('admin-scripts'); ?>
