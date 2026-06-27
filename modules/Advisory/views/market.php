<?php
/**
 * SFAS — Market Prices
 * File: modules/Advisory/views/market.php
 */
$pageTitle   = 'Market Prices';
$currentPage = 'market';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db     = Database::getConnection();
$model  = new AdvisoryModel($db);
$prices = $model->getAllPrices([]);
$crops  = $model->getAllCrops();

// Group prices by crop for summary
$byCrop = [];
foreach ($prices as $p) {
    $byCrop[$p['crop_name']][] = $p;
}

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Market Prices</span>
    </div>
    <h1 class="page-title">Market Prices</h1>
    <p class="page-sub">Crop prices from markets across Rwanda (RWF per kg)</p>
  </div>
  <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
  <button class="sfas-btn sfas-btn-primary" onclick="toggleAddForm()">
    <i class="ri-add-line"></i> Add Price
  </button>
  <?php endif; ?>
</div>

<!-- Add Price Form -->
<?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
<div id="addPriceForm" style="display:none;margin-bottom:1.5rem">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-price-tag-3-line"></i> Record New Price</span>
      <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="toggleAddForm()"><i class="ri-close-line"></i></button>
    </div>
    <div class="sfas-card-body">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.85rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Crop <span class="req">*</span></label>
          <select id="pCropId" class="sfas-select">
            <option value="">— Select crop —</option>
            <?php foreach ($crops as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Market Name <span class="req">*</span></label>
          <input type="text" id="pMarket" class="sfas-input" placeholder="e.g. Nyagatare Main Market">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="pDistrict" class="sfas-select">
            <option value="">— Select —</option>
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Musanze','Huye','Kigali','Rubavu'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Price (RWF/kg) <span class="req">*</span></label>
          <input type="number" id="pPrice" class="sfas-input" placeholder="350" step="0.01" min="0">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Unit</label>
          <select id="pUnit" class="sfas-select">
            <option value="kg">kg</option>
            <option value="tonne">tonne</option>
            <option value="bag (50kg)">bag (50kg)</option>
            <option value="crate">crate</option>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Date</label>
          <input type="date" id="pDate" class="sfas-input" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Source</label>
        <select id="pSource" class="sfas-select" style="max-width:240px">
          <option>Field Survey</option>
          <option>REMA Data</option>
          <option>MINAGRI</option>
          <option>Trader Interview</option>
          <option>Other</option>
        </select>
      </div>
      <div style="display:flex;gap:.5rem">
        <button class="sfas-btn sfas-btn-primary" id="savePriceBtn" onclick="savePrice()">
          <i class="ri-save-line"></i> Save Price
        </button>
        <button class="sfas-btn sfas-btn-ghost" onclick="toggleAddForm()">Cancel</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Summary cards by crop -->
<?php if (!empty($byCrop)): ?>
<div style="margin-bottom:1.5rem">
  <h2 style="font-size:1rem;font-weight:700;color:var(--slate-700);margin-bottom:.85rem;display:flex;align-items:center;gap:.4rem">
    <i class="ri-bar-chart-box-line" style="color:var(--green-500)"></i> Latest Prices by Crop
  </h2>
  <div class="sfas-grid sfas-grid-4">
    <?php
    $shownCrops = [];
    foreach ($prices as $p):
      if (isset($shownCrops[$p['crop_id']])) continue;
      $shownCrops[$p['crop_id']] = true;
    ?>
    <div class="sfas-card" style="border-top:3px solid var(--green-400)">
      <div class="sfas-card-body" style="padding:1rem">
        <div style="font-weight:700;font-size:.95rem;color:var(--slate-800)"><?= htmlspecialchars($p['crop_name']) ?></div>
        <?php if ($p['local_name']): ?>
        <div style="font-size:.75rem;color:var(--green-600);font-style:italic"><?= htmlspecialchars($p['local_name']) ?></div>
        <?php endif; ?>
        <div style="font-size:1.4rem;font-weight:700;color:var(--green-700);font-family:'JetBrains Mono',monospace;margin:.5rem 0">
          RWF <?= number_format($p['price_rwf'],0) ?>
          <span style="font-size:.75rem;font-weight:400;color:var(--text-muted)">/ <?= $p['unit'] ?></span>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted)">
          <i class="ri-store-line"></i> <?= htmlspecialchars($p['market']) ?><br>
          <i class="ri-calendar-line"></i> <?= date('d M Y',strtotime($p['price_date'])) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Full Prices Table -->
<div class="sfas-card">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-table-line"></i> All Price Records (<?= count($prices) ?>)</span>
    <div style="display:flex;gap:.5rem">
      <select id="filterCrop" class="sfas-select sfas-btn-sm" style="width:150px" onchange="filterTable()">
        <option value="">All Crops</option>
        <?php foreach ($crops as $c): ?>
        <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="sfas-table-wrap">
    <table class="sfas-table" id="pricesTable">
      <thead>
        <tr>
          <th>Crop</th>
          <th>Market</th>
          <th>District</th>
          <th>Price (RWF)</th>
          <th>Unit</th>
          <th>Date</th>
          <th>Source</th>
          <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
          <th>Action</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($prices)): ?>
        <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted)">
          No price records yet.
        </td></tr>
        <?php else: ?>
        <?php foreach ($prices as $p): ?>
        <tr class="price-table-row" data-crop="<?= htmlspecialchars($p['crop_name']) ?>">
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($p['crop_name']) ?></div>
            <?php if ($p['local_name']): ?><div style="font-size:.73rem;color:var(--text-muted)"><?= htmlspecialchars($p['local_name']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p['market']) ?></td>
          <td><?= htmlspecialchars($p['district'] ?? '—') ?></td>
          <td style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green-700)">
            <?= number_format($p['price_rwf'],0) ?>
          </td>
          <td><?= htmlspecialchars($p['unit']) ?></td>
          <td style="color:var(--text-muted)"><?= date('d M Y',strtotime($p['price_date'])) ?></td>
          <td><span class="sfas-badge badge-slate"><?= htmlspecialchars($p['source']) ?></span></td>
          <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
          <td>
            <button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="deletePrice(<?= $p['id'] ?>)" title="Delete">
              <i class="ri-delete-bin-line"></i>
            </button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const B = window.BASE_URL;

function toggleAddForm() {
  const f = document.getElementById('addPriceForm');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function savePrice() {
  const crop_id   = document.getElementById('pCropId').value;
  const market    = document.getElementById('pMarket').value.trim();
  const price_rwf = document.getElementById('pPrice').value;
  if (!crop_id || !market || !price_rwf) { mgError('Required','Crop, market, and price are required.'); return; }

  const btn = document.getElementById('savePriceBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';

  try {
    const r = await fetch(B+'/api/market?action=add', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        crop_id, market,
        district:   document.getElementById('pDistrict').value || null,
        price_rwf,
        unit:       document.getElementById('pUnit').value,
        price_date: document.getElementById('pDate').value,
        source:     document.getElementById('pSource').value,
      })
    });
    const d = await r.json();
    if (d.success) mgSuccess('Saved!', d.message, ()=>location.reload());
    else mgError('Error', d.message);
  } catch(e) { mgError('Error','Network error.'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Price'; }
}

function deletePrice(id) {
  mgConfirm('Delete Record?','This price record will be removed.',()=>{
    fetch(B+'/api/market?action=delete',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if(d.success) location.reload();
      else mgError('Error',d.message);
    });
  });
}

function filterTable() {
  const val = document.getElementById('filterCrop').value.toLowerCase();
  document.querySelectorAll('.price-table-row').forEach(row => {
    const crop = row.dataset.crop?.toLowerCase() || '';
    row.style.display = (!val || crop === val) ? '' : 'none';
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
