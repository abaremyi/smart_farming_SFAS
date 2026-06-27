<?php
/**
 * SFAS — Farm Edit View
 * File: modules/Farm/views/farm-edit.php
 */
$pageTitle   = 'Edit Farm';
$currentPage = 'farms';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/FarmModel.php';

$db    = Database::getConnection();
$model = new FarmModel($db);

$id   = (int)($_GET['id'] ?? 0);
$farm = $model->getById($id);
if (!$farm) { header('Location: '.url('admin/farms')); exit; }

// Access check
if (!$isSuperAdmin && !hasPermission($userPermissions,'farms.edit')
    && (int)$farm['farmer_id'] !== (int)$currentUser->user_id) {
    header('Location: '.url('admin/farms')); exit;
}

$farmCrops = $model->getFarmCrops($id);
$allCrops  = $model->getAllCrops();

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/farms') ?>">Farms</a>
      <span>/</span><span>Edit</span>
    </div>
    <h1 class="page-title">Edit Farm: <?= htmlspecialchars($farm['farm_name']) ?></h1>
    <p class="page-sub">Farmer: <?= htmlspecialchars($farm['firstname'].' '.$farm['lastname']) ?></p>
  </div>
  <a href="<?= url('admin/farms') ?>" class="sfas-btn sfas-btn-ghost"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div class="sfas-grid sfas-grid-2">

  <!-- Edit Form -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-edit-line"></i> Farm Details</span>
    </div>
    <div class="sfas-card-body">
      <div id="formAlert" style="display:none"></div>

      <div class="sfas-form-group">
        <label class="sfas-label">Farm Name <span class="req">*</span></label>
        <input type="text" id="farm_name" class="sfas-input" value="<?= htmlspecialchars($farm['farm_name']) ?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="district" class="sfas-select">
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Bugesera','Musanze','Huye','Kigali'] as $d): ?>
            <option value="<?= $d ?>" <?= $farm['district']===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Sector</label>
          <input type="text" id="sector" class="sfas-input" value="<?= htmlspecialchars($farm['sector']??'') ?>">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Cell / Village</label>
        <input type="text" id="cell" class="sfas-input" value="<?= htmlspecialchars($farm['cell']??'') ?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Size (ha)</label>
          <input type="number" id="size_ha" class="sfas-input" value="<?= $farm['size_ha'] ?>" step="0.01" min="0">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Soil Type</label>
          <select id="soil_type" class="sfas-select">
            <option value="">— Select —</option>
            <?php foreach(['Clay','Sandy','Loam','Sandy-Loam','Clay-Loam','Silt'] as $s): ?>
            <option value="<?= $s ?>" <?= $farm['soil_type']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Water Source</label>
          <select id="water_source" class="sfas-select">
            <?php foreach(['Rain-fed','Irrigation','Both'] as $w): ?>
            <option value="<?= $w ?>" <?= $farm['water_source']===$w?'selected':'' ?>><?= $w ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Latitude</label>
          <input type="number" id="latitude" class="sfas-input" value="<?= $farm['latitude'] ?>" step="0.0000001">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Longitude</label>
          <input type="number" id="longitude" class="sfas-input" value="<?= $farm['longitude'] ?>" step="0.0000001">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Notes</label>
        <textarea id="notes" class="sfas-textarea" rows="3"><?= htmlspecialchars($farm['notes']??'') ?></textarea>
      </div>

      <button class="sfas-btn sfas-btn-primary" id="saveBtn" onclick="saveFarm()">
        <i class="ri-save-line"></i> Save Changes
      </button>
    </div>
  </div>

  <!-- Crops on this farm -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-seedling-line"></i> Crops on this Farm</span>
      <button class="sfas-btn sfas-btn-primary sfas-btn-sm" onclick="showAddCrop()">
        <i class="ri-add-line"></i> Add Crop
      </button>
    </div>
    <div class="sfas-card-body" style="padding:0">
      <div id="cropsList">
        <?php if (empty($farmCrops)): ?>
        <div class="sfas-empty"><i class="ri-seedling-line"></i><h3>No crops added</h3><p>Add crops currently growing on this farm.</p></div>
        <?php else: ?>
        <?php foreach ($farmCrops as $fc): ?>
        <div class="price-row" id="fc<?= $fc['id'] ?>">
          <div>
            <div style="font-weight:600"><?= htmlspecialchars($fc['crop_name']) ?>
              <?php if($fc['local_name']): ?><span style="font-size:.75rem;color:var(--text-muted)"> (<?= htmlspecialchars($fc['local_name']) ?>)</span><?php endif; ?>
            </div>
            <div style="font-size:.75rem;color:var(--text-muted)">
              <?= htmlspecialchars($fc['season']??'') ?>
              <?php if($fc['area_ha']): ?> · <?= $fc['area_ha'] ?>ha<?php endif; ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:.5rem">
            <?php $sc=['Growing'=>'badge-green','Planning'=>'badge-blue','Harvested'=>'badge-slate','Failed'=>'badge-red']; ?>
            <span class="sfas-badge <?= $sc[$fc['status']]??'badge-slate' ?>"><?= $fc['status'] ?></span>
            <button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="removeCrop(<?= $fc['id'] ?>)" title="Remove">
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add Crop Panel -->
    <div id="addCropPanel" style="display:none;padding:1.1rem 1.25rem;border-top:1px solid var(--border)">
      <div class="sfas-form-group">
        <label class="sfas-label">Crop</label>
        <select id="newCropId" class="sfas-select">
          <option value="">— Select crop —</option>
          <?php foreach ($allCrops as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['local_name']??'') ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Season</label>
          <select id="newSeason" class="sfas-select">
            <option value="">— Select —</option>
            <option>Season A</option><option>Season B</option><option>Year-round</option>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Area (ha)</label>
          <input type="number" id="newArea" class="sfas-input" placeholder="0.00" step="0.01">
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Planted Date</label>
          <input type="date" id="newPlanted" class="sfas-input">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Status</label>
          <select id="newStatus" class="sfas-select">
            <option>Growing</option><option>Planning</option><option>Harvested</option><option>Failed</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:.5rem">
        <button class="sfas-btn sfas-btn-primary sfas-btn-sm" onclick="addCrop()"><i class="ri-check-line"></i> Add</button>
        <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="document.getElementById('addCropPanel').style.display='none'">Cancel</button>
      </div>
    </div>
  </div>

</div>

<script>
const B = window.BASE_URL;
const FARM_ID = <?= $id ?>;

function saveFarm() {
  const alertEl = document.getElementById('formAlert');
  alertEl.style.display = 'none';
  const farm_name = document.getElementById('farm_name').value.trim();
  if (!farm_name) {
    alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Farm name is required.</span></div>';
    alertEl.style.display='block'; return;
  }
  const btn = document.getElementById('saveBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';

  fetch(B+'/api/farms?action=update', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      id: FARM_ID,
      farm_name, district: document.getElementById('district').value,
      sector: document.getElementById('sector').value.trim(),
      cell: document.getElementById('cell').value.trim(),
      size_ha: document.getElementById('size_ha').value||null,
      soil_type: document.getElementById('soil_type').value||null,
      water_source: document.getElementById('water_source').value,
      latitude: document.getElementById('latitude').value||null,
      longitude: document.getElementById('longitude').value||null,
      notes: document.getElementById('notes').value.trim(),
    })
  }).then(r=>r.json()).then(d=>{
    if (d.success) mgSuccess('Saved!', d.message);
    else { alertEl.innerHTML=`<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${d.message}</span></div>`; alertEl.style.display='block'; }
    btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Changes';
  });
}

function showAddCrop() { document.getElementById('addCropPanel').style.display='block'; }

function addCrop() {
  const crop_id = document.getElementById('newCropId').value;
  if (!crop_id) { mgError('Error','Please select a crop.'); return; }
  fetch(B+'/api/farms?action=add-crop', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      farm_id: FARM_ID, crop_id,
      season: document.getElementById('newSeason').value||null,
      area_ha: document.getElementById('newArea').value||null,
      planted_at: document.getElementById('newPlanted').value||null,
      status: document.getElementById('newStatus').value,
    })
  }).then(r=>r.json()).then(d=>{
    if (d.success) mgSuccess('Added!', 'Crop added to farm', ()=>location.reload());
    else mgError('Error', d.message);
  });
}

function removeCrop(id) {
  mgConfirm('Remove Crop?','This crop record will be removed from the farm.', ()=>{
    fetch(B+'/api/farms?action=remove-crop', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if (d.success) document.getElementById('fc'+id)?.remove();
      else mgError('Error', d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
