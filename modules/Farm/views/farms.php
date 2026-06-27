<?php
/**
 * SFAS — Farms List View
 * File: modules/Farm/views/farms.php
 */
$pageTitle   = 'Farm Management';
$currentPage = 'farms';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/FarmModel.php';

$db    = Database::getConnection();
$model = new FarmModel($db);

$filters = [
    'search'   => trim($_GET['search']   ?? ''),
    'district' => trim($_GET['district'] ?? ''),
];
// Farmers only see their own farms
if (!$isSuperAdmin && !hasPermission($userPermissions,'farms.view')) {
    $filters['farmer_id'] = $currentUser->user_id;
}

$farms     = $model->getAll($filters);
$stats     = $model->getStats();
$districts = $model->getDistinctDistricts();

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Farms</span>
    </div>
    <h1 class="page-title">Farm Management</h1>
    <p class="page-sub">All registered farms in Nyagatare District and beyond</p>
  </div>
  <a href="<?= url('admin/farms/create') ?>" class="sfas-btn sfas-btn-primary">
    <i class="ri-map-pin-add-line"></i> Register Farm
  </a>
</div>

<!-- Stats -->
<div class="sfas-stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:1.5rem">
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
    <div class="stat-icon"><i class="ri-map-2-line"></i></div>
    <div class="stat-val"><?= number_format($stats['total']) ?></div>
    <div class="stat-label">Total Farms</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--blue-500);--stat-bg:var(--blue-100)">
    <div class="stat-icon"><i class="ri-water-flash-line"></i></div>
    <div class="stat-val"><?= number_format($stats['irrigated']) ?></div>
    <div class="stat-label">Irrigated</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
    <div class="stat-icon"><i class="ri-cloud-line"></i></div>
    <div class="stat-val"><?= number_format($stats['rainfed']) ?></div>
    <div class="stat-label">Rain-fed</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
    <div class="stat-icon"><i class="ri-ruler-2-line"></i></div>
    <div class="stat-val"><?= number_format($stats['avg_size'],1) ?></div>
    <div class="stat-label">Avg Size (ha)</div>
  </div>
</div>

<!-- Filters -->
<div class="sfas-card" style="margin-bottom:1.25rem">
  <div class="sfas-card-body" style="padding:.85rem 1.25rem">
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <input type="text" class="sfas-input" id="searchInput" placeholder="Search farm name, farmer, sector…"
        value="<?= htmlspecialchars($filters['search']) ?>" style="max-width:300px;flex:1">
      <select class="sfas-select" id="districtFilter" style="max-width:180px">
        <option value="">All Districts</option>
        <?php foreach($districts as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $filters['district']===$d?'selected':'' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button class="sfas-btn sfas-btn-primary" onclick="applyFilters()"><i class="ri-search-line"></i> Search</button>
      <button class="sfas-btn sfas-btn-ghost" onclick="clearFilters()"><i class="ri-refresh-line"></i> Clear</button>
    </div>
  </div>
</div>

<!-- Farms Table -->
<div class="sfas-card">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-map-2-line"></i> Farms (<?= count($farms) ?>)</span>
  </div>
  <div class="sfas-table-wrap">
    <table class="sfas-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Farm Name</th>
          <th>Farmer</th>
          <th>Location</th>
          <th>Size (ha)</th>
          <th>Soil</th>
          <th>Water</th>
          <th>Crops</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($farms)): ?>
        <tr><td colspan="9" style="text-align:center;padding:2.5rem;color:var(--text-muted)">
          <i class="ri-map-2-line" style="font-size:2rem;display:block;opacity:.3;margin-bottom:.5rem"></i>
          No farms found. <a href="<?= url('admin/farms/create') ?>" style="color:var(--green-600)">Register the first farm</a>
        </td></tr>
        <?php else: ?>
        <?php foreach ($farms as $i => $farm): ?>
        <tr>
          <td style="color:var(--text-muted)"><?= $i+1 ?></td>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($farm['farm_name']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)">#<?= $farm['id'] ?></div>
          </td>
          <td>
            <div><?= htmlspecialchars($farm['firstname'].' '.$farm['lastname']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($farm['email']) ?></div>
          </td>
          <td>
            <div><?= htmlspecialchars($farm['district'] ?? '—') ?></div>
            <?php if ($farm['sector']): ?><div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($farm['sector']) ?></div><?php endif; ?>
          </td>
          <td><?= $farm['size_ha'] ? number_format($farm['size_ha'],2) : '—' ?></td>
          <td>
            <?php if ($farm['soil_type']): ?>
            <span class="sfas-badge badge-green"><?= htmlspecialchars($farm['soil_type']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php $wc=['Rain-fed'=>'badge-blue','Irrigation'=>'badge-green','Both'=>'badge-amber']; ?>
            <span class="sfas-badge <?= $wc[$farm['water_source']] ?? 'badge-slate' ?>">
              <?= htmlspecialchars($farm['water_source'] ?? '—') ?>
            </span>
          </td>
          <td>
            <span class="sfas-badge badge-green"><i class="ri-seedling-line"></i> <?= (int)$farm['crops_count'] ?></span>
          </td>
          <td>
            <div style="display:flex;gap:.35rem">
              <a href="<?= url('admin/farms/edit') ?>?id=<?= $farm['id'] ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm" title="Edit">
                <i class="ri-edit-line"></i>
              </a>
              <?php if ($isSuperAdmin || hasPermission($userPermissions,'farms.delete') || (int)$farm['farmer_id']===(int)$currentUser->user_id): ?>
              <button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="deleteFarm(<?= $farm['id'] ?>, '<?= addslashes($farm['farm_name']) ?>')" title="Delete">
                <i class="ri-delete-bin-line"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const B = window.BASE_URL;

function applyFilters() {
  const s = document.getElementById('searchInput').value.trim();
  const d = document.getElementById('districtFilter').value;
  const p = new URLSearchParams();
  if (s) p.set('search', s);
  if (d) p.set('district', d);
  window.location.href = B+'/admin/farms'+(p.toString()?'?'+p.toString():'');
}
function clearFilters() { window.location.href = B+'/admin/farms'; }

document.getElementById('searchInput').addEventListener('keydown', e => { if (e.key==='Enter') applyFilters(); });

function deleteFarm(id, name) {
  mgConfirm('Delete Farm?', `"${name}" and all its crop records will be permanently deleted.`, () => {
    mgLoading('Deleting…');
    fetch(B+'/api/farms?action=delete', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id})
    }).then(r=>r.json()).then(d => {
      Swal.close();
      if (d.success) mgSuccess('Deleted', d.message, ()=>location.reload());
      else mgError('Error', d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
