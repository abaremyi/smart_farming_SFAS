<?php
/**
 * SFAS — Advisory Tips List
 * File: modules/Advisory/views/advisory.php
 */
$pageTitle   = 'Advisory Tips';
$currentPage = 'advisory';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db    = Database::getConnection();
$model = new AdvisoryModel($db);

$filters = [
    'search'   => trim($_GET['search']   ?? ''),
    'category' => trim($_GET['category'] ?? ''),
    'crop_id'  => (int)($_GET['crop_id'] ?? 0) ?: null,
];
$tips  = $model->getAllTips($filters);
$stats = $model->getTipStats();
$crops = $model->getAllCrops();

$categories = ['Crop Management','Pest & Disease','Soil Health','Irrigation','Harvest & Post-Harvest','Market','General'];
$catIcon = [
    'Crop Management'=>'ri-seedling-line','Pest & Disease'=>'ri-bug-line',
    'Soil Health'=>'ri-earth-line','Irrigation'=>'ri-water-flash-line',
    'Harvest & Post-Harvest'=>'ri-scales-3-line','Market'=>'ri-price-tag-3-line','General'=>'ri-information-line',
];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Advisory Tips</span>
    </div>
    <h1 class="page-title">Advisory Tips</h1>
    <p class="page-sub">Expert farming guidance for Rwanda's farmers</p>
  </div>
  <?php if ($isSuperAdmin || hasPermission($userPermissions,'advisory.create')): ?>
  <a href="<?= url('admin/advisory/create') ?>" class="sfas-btn sfas-btn-primary">
    <i class="ri-add-line"></i> New Tip
  </a>
  <?php endif; ?>
</div>

<!-- Stats row -->
<div class="sfas-stat-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
    <div class="stat-icon"><i class="ri-article-line"></i></div>
    <div class="stat-val"><?= $stats['total'] ?></div>
    <div class="stat-label">Total Tips</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
    <div class="stat-icon"><i class="ri-checkbox-circle-line"></i></div>
    <div class="stat-val"><?= $stats['active'] ?></div>
    <div class="stat-label">Active</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
    <div class="stat-icon"><i class="ri-layout-grid-line"></i></div>
    <div class="stat-val"><?= $stats['categories'] ?></div>
    <div class="stat-label">Categories</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--blue-500);--stat-bg:var(--blue-100)">
    <div class="stat-icon"><i class="ri-eye-line"></i></div>
    <div class="stat-val"><?= number_format($stats['total_views']) ?></div>
    <div class="stat-label">Total Views</div>
  </div>
</div>

<!-- Filters -->
<div class="sfas-card" style="margin-bottom:1.25rem">
  <div class="sfas-card-body" style="padding:.85rem 1.25rem">
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <input type="text" id="searchInput" class="sfas-input" placeholder="Search title or content…"
        value="<?= htmlspecialchars($filters['search']) ?>" style="flex:1;max-width:280px">
      <select id="categoryFilter" class="sfas-select" style="max-width:200px">
        <option value="">All Categories</option>
        <?php foreach($categories as $cat): ?>
        <option value="<?= $cat ?>" <?= $filters['category']===$cat?'selected':'' ?>><?= $cat ?></option>
        <?php endforeach; ?>
      </select>
      <select id="cropFilter" class="sfas-select" style="max-width:160px">
        <option value="">All Crops</option>
        <?php foreach($crops as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $filters['crop_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="sfas-btn sfas-btn-primary" onclick="applyFilters()"><i class="ri-search-line"></i> Filter</button>
      <button class="sfas-btn sfas-btn-ghost" onclick="window.location.href='<?= url('admin/advisory') ?>'"><i class="ri-refresh-line"></i></button>
    </div>
  </div>
</div>

<!-- Tips Grid -->
<?php if (empty($tips)): ?>
<div class="sfas-card">
  <div class="sfas-empty" style="padding:3rem">
    <i class="ri-lightbulb-line"></i>
    <h3>No advisory tips found</h3>
    <p>Create the first tip to guide farmers.</p>
    <?php if ($isSuperAdmin || hasPermission($userPermissions,'advisory.create')): ?>
    <a href="<?= url('admin/advisory/create') ?>" class="sfas-btn sfas-btn-primary" style="margin-top:1rem">
      <i class="ri-add-line"></i> Create First Tip
    </a>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="sfas-grid sfas-grid-2">
  <?php foreach ($tips as $tip): ?>
  <div class="sfas-card" id="tip<?= $tip['id'] ?>">
    <div class="sfas-card-body">
      <!-- Header row -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem;margin-bottom:.6rem">
        <div>
          <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
            <i class="<?= $catIcon[$tip['category']]??'ri-information-line' ?>" style="color:var(--green-500);font-size:.9rem"></i>
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--green-600)"><?= htmlspecialchars($tip['category']) ?></span>
            <?php if ($tip['crop_name']): ?>
            <span style="font-size:.72rem;color:var(--text-muted)">· <?= htmlspecialchars($tip['crop_name']) ?></span>
            <?php endif; ?>
          </div>
          <h3 style="font-size:.95rem;font-weight:700;color:var(--slate-800);line-height:1.3"><?= htmlspecialchars($tip['title']) ?></h3>
        </div>
        <span class="sfas-badge <?= $tip['is_active'] ? 'badge-green' : 'badge-slate' ?>">
          <?= $tip['is_active'] ? 'Active' : 'Inactive' ?>
        </span>
      </div>
      <!-- Content preview -->
      <p style="font-size:.85rem;color:var(--text-muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">
        <?= htmlspecialchars($tip['content']) ?>
      </p>
      <!-- Meta -->
      <div style="display:flex;gap.5rem;flex-wrap:wrap;font-size:.75rem;color:var(--text-light);margin-top:.85rem;padding-top:.75rem;border-top:1px solid var(--border)">
        <span style="margin-right:.6rem"><i class="ri-user-line"></i> <?= htmlspecialchars($tip['author_first'].' '.$tip['author_last']) ?></span>
        <span style="margin-right:.6rem"><i class="ri-calendar-line"></i> <?= date('d M Y',strtotime($tip['created_at'])) ?></span>
        <?php if($tip['district']): ?><span style="margin-right:.6rem"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($tip['district']) ?></span><?php endif; ?>
        <span><i class="ri-eye-line"></i> <?= number_format($tip['views']) ?> views</span>
      </div>
      <!-- Actions -->
      <?php if ($isSuperAdmin || hasPermission($userPermissions,'advisory.edit')): ?>
      <div style="display:flex;gap:.4rem;margin-top:.85rem">
        <a href="<?= url('admin/advisory/edit') ?>?id=<?= $tip['id'] ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
          <i class="ri-edit-line"></i> Edit
        </a>
        <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="toggleTip(<?= $tip['id'] ?>,<?= $tip['is_active'] ?>)">
          <i class="ri-<?= $tip['is_active']?'eye-off':'eye' ?>-line"></i>
          <?= $tip['is_active']?'Deactivate':'Activate' ?>
        </button>
        <?php if ($isSuperAdmin || hasPermission($userPermissions,'advisory.delete')): ?>
        <button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="deleteTip(<?= $tip['id'] ?>,'<?= addslashes($tip['title']) ?>')">
          <i class="ri-delete-bin-line"></i>
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const B = window.BASE_URL;
function applyFilters() {
  const p = new URLSearchParams();
  const s = document.getElementById('searchInput').value.trim();
  const c = document.getElementById('categoryFilter').value;
  const cr = document.getElementById('cropFilter').value;
  if(s) p.set('search',s); if(c) p.set('category',c); if(cr) p.set('crop_id',cr);
  window.location.href = B+'/admin/advisory'+(p.toString()?'?'+p:'');
}
document.getElementById('searchInput').addEventListener('keydown',e=>{ if(e.key==='Enter') applyFilters(); });

function toggleTip(id, currentState) {
  const label = currentState ? 'Deactivate' : 'Activate';
  mgConfirm(label+' Tip?', label+' this advisory tip?', ()=>{
    fetch(B+'/api/advisory?action=toggle',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if(d.success) mgSuccess('Done',d.message,()=>location.reload());
      else mgError('Error',d.message);
    });
  });
}
function deleteTip(id,title){
  mgConfirm('Delete Tip?',`"${title}" will be permanently deleted.`,()=>{
    fetch(B+'/api/advisory?action=delete',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if(d.success){ document.getElementById('tip'+id)?.remove(); mgSuccess('Deleted',d.message); }
      else mgError('Error',d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
