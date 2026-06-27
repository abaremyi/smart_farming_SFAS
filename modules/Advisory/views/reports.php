<?php
/**
 * SFAS — Reports View
 * File: modules/Advisory/views/reports.php
 */
$pageTitle   = 'Reports';
$currentPage = 'reports';
$requiredPermission = 'reports.view';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db      = Database::getConnection();
$model   = new AdvisoryModel($db);
$summary = $model->getReportSummary();
$byCategory = $model->getTipsByCategory();
$bySeverity = $model->getAlertsBySeverity();
$recent     = $model->getRecentActivity(8);

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Reports</span>
    </div>
    <h1 class="page-title">System Reports</h1>
    <p class="page-sub">Overview of SFAS activity and content</p>
  </div>
  <button class="sfas-btn sfas-btn-outline" onclick="window.print()">
    <i class="ri-printer-line"></i> Print Report
  </button>
</div>

<!-- Summary Stats -->
<div class="sfas-stat-grid" style="margin-bottom:1.5rem">
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
    <div class="stat-icon"><i class="ri-lightbulb-line"></i></div>
    <div class="stat-val"><?= $summary['tips'] ?></div>
    <div class="stat-label">Advisory Tips</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--red-600);--stat-bg:var(--red-100)">
    <div class="stat-icon"><i class="ri-alarm-warning-line"></i></div>
    <div class="stat-val"><?= $summary['alerts'] ?></div>
    <div class="stat-label">Active Alerts</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
    <div class="stat-icon"><i class="ri-map-2-line"></i></div>
    <div class="stat-val"><?= $summary['farms'] ?></div>
    <div class="stat-label">Registered Farms</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
    <div class="stat-icon"><i class="ri-group-line"></i></div>
    <div class="stat-val"><?= $summary['farmers'] ?></div>
    <div class="stat-label">Active Farmers</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--blue-500);--stat-bg:var(--blue-100)">
    <div class="stat-icon"><i class="ri-robot-2-line"></i></div>
    <div class="stat-val"><?= $summary['aiChats'] ?></div>
    <div class="stat-label">AI Conversations</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
    <div class="stat-icon"><i class="ri-price-tag-3-line"></i></div>
    <div class="stat-val"><?= $summary['prices'] ?></div>
    <div class="stat-label">Price Records</div>
  </div>
</div>

<div class="sfas-grid sfas-grid-2" style="margin-bottom:1.25rem">

  <!-- Tips by Category Chart -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-pie-chart-line"></i> Tips by Category</span>
    </div>
    <div class="sfas-card-body">
      <canvas id="categoryChart" height="220"></canvas>
    </div>
  </div>

  <!-- Alerts by Severity -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-bar-chart-2-line"></i> Alerts by Severity</span>
    </div>
    <div class="sfas-card-body">
      <canvas id="severityChart" height="220"></canvas>
      <?php if (empty($bySeverity)): ?>
      <div class="sfas-empty" style="padding:1rem"><i class="ri-bar-chart-line"></i><p>No alerts recorded yet.</p></div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Recent Activity + Categories Table -->
<div class="sfas-grid sfas-grid-2">

  <!-- Recent Activity -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-time-line"></i> Recent Activity</span>
    </div>
    <div class="sfas-card-body" style="padding:0">
      <?php if (empty($recent)): ?>
      <div class="sfas-empty" style="padding:2rem"><i class="ri-history-line"></i><p>No activity yet.</p></div>
      <?php else: ?>
      <?php foreach ($recent as $r): ?>
      <div class="price-row" style="padding:.7rem 1.25rem">
        <div style="display:flex;align-items:center;gap:.6rem">
          <div style="width:32px;height:32px;border-radius:50%;
            background:<?= $r['type']==='tip'?'var(--green-100)':'var(--red-100)' ?>;
            display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="<?= $r['type']==='tip'?'ri-lightbulb-line':'ri-alarm-warning-line' ?>"
              style="color:<?= $r['type']==='tip'?'var(--green-600)':'var(--red-600)' ?>;font-size:.85rem"></i>
          </div>
          <div>
            <div style="font-size:.85rem;font-weight:500"><?= htmlspecialchars(mb_substr($r['label'],0,45)) ?><?= strlen($r['label'])>45?'…':'' ?></div>
            <div style="font-size:.73rem;color:var(--text-muted)"><?= ucfirst($r['type']) ?> · <?= date('d M Y',strtotime($r['created_at'])) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Category breakdown table -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-table-line"></i> Tips Breakdown</span>
    </div>
    <div class="sfas-table-wrap">
      <table class="sfas-table">
        <thead><tr><th>Category</th><th>Tips</th><th>Share</th></tr></thead>
        <tbody>
          <?php
          $totalTips = array_sum(array_column($byCategory,'total'));
          foreach ($byCategory as $bc):
            $pct = $totalTips > 0 ? round($bc['total']/$totalTips*100) : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($bc['category']) ?></td>
            <td><strong><?= $bc['total'] ?></strong></td>
            <td style="min-width:120px">
              <div style="display:flex;align-items:center;gap:.5rem">
                <div class="progress-bar" style="flex:1">
                  <div class="progress-bar-fill" data-fill="<?= $pct ?>%" style="width:0"></div>
                </div>
                <span style="font-size:.75rem;color:var(--text-muted);width:30px"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($byCategory)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">No tips yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Export buttons -->
<div class="sfas-card" style="margin-top:1.25rem">
  <div class="sfas-card-body" style="padding:1rem 1.25rem">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
      <span style="font-weight:600;color:var(--slate-700)"><i class="ri-download-line"></i> Export Data:</span>
      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="exportCSV('tips')">
        <i class="ri-file-text-line"></i> Advisory Tips CSV
      </button>
      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="exportCSV('alerts')">
        <i class="ri-alarm-warning-line"></i> Pest Alerts CSV
      </button>
      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="exportCSV('prices')">
        <i class="ri-price-tag-3-line"></i> Market Prices CSV
      </button>
      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="exportCSV('farms')">
        <i class="ri-map-2-line"></i> Farms CSV
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const B = window.BASE_URL;

// Category Pie Chart
const catLabels = <?= json_encode(array_column($byCategory,'category')) ?>;
const catData   = <?= json_encode(array_column($byCategory,'total')) ?>;
if (catLabels.length > 0) {
  new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
      labels: catLabels,
      datasets:[{ data: catData,
        backgroundColor:['#2d9a4e','#e89d18','#3b82f6','#f59e0b','#6366f1','#b91c1c','#64748b'],
        borderWidth: 2, borderColor:'#fff'
      }]
    },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{size:12}, padding:12 } } } }
  });
}

// Severity Bar Chart
const sevLabels = <?= json_encode(array_column($bySeverity,'severity')) ?>;
const sevData   = <?= json_encode(array_column($bySeverity,'total')) ?>;
const sevColors = { Low:'#2d9a4e', Medium:'#f59e0b', High:'#b91c1c', Critical:'#7c2d12' };
if (sevLabels.length > 0) {
  new Chart(document.getElementById('severityChart'), {
    type: 'bar',
    data: {
      labels: sevLabels,
      datasets:[{ label:'Alerts', data: sevData,
        backgroundColor: sevLabels.map(l=>sevColors[l]||'#64748b'),
        borderRadius: 6, borderSkipped: false
      }]
    },
    options:{
      responsive:true,
      plugins:{ legend:{ display:false } },
      scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } }
    }
  });
}

// Animate progress bars
document.querySelectorAll('[data-fill]').forEach(el=>{
  const t=el.getAttribute('data-fill'); el.style.width='0';
  setTimeout(()=>{ el.style.transition='width 0.9s ease'; el.style.width=t; },200);
});

// CSV Export
function exportCSV(type) {
  let url = '';
  if (type==='tips')   url = B+'/api/advisory?action=list';
  if (type==='alerts') url = B+'/api/alerts?action=list';
  if (type==='prices') url = B+'/api/market?action=list';
  if (type==='farms')  url = B+'/api/farms?action=list';

  mgLoading('Preparing export…');
  fetch(url, { credentials:'include' })
    .then(r=>r.json())
    .then(d=>{
      Swal.close();
      if(!d.success||!d.data?.length){ mgError('No Data','Nothing to export yet.'); return; }
      const rows  = d.data;
      const keys  = Object.keys(rows[0]);
      const csv   = [keys.join(','), ...rows.map(r=>keys.map(k=>{
        const v=(r[k]??'').toString().replace(/"/g,'""');
        return /[",\n]/.test(v)?`"${v}"`:v;
      }).join(','))].join('\n');
      const blob  = new Blob([csv],{type:'text/csv'});
      const a     = document.createElement('a');
      a.href      = URL.createObjectURL(blob);
      a.download  = `sfas_${type}_${new Date().toISOString().slice(0,10)}.csv`;
      a.click();
    })
    .catch(()=>{ Swal.close(); mgError('Error','Export failed.'); });
}
</script>

<?php require get_layout('admin-scripts'); ?>
