<?php
/**
 * SFAS — Register Farm View
 * File: modules/Farm/views/farm-create.php
 */
$pageTitle   = 'Register Farm';
$currentPage = 'farms';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/FarmModel.php';

$db      = Database::getConnection();
$model   = new FarmModel($db);
$farmers = $model->getFarmers();

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/farms') ?>">Farms</a>
      <span>/</span><span>Register Farm</span>
    </div>
    <h1 class="page-title">Register a Farm</h1>
    <p class="page-sub">Add a new farm plot to the SFAS system</p>
  </div>
  <a href="<?= url('admin/farms') ?>" class="sfas-btn sfas-btn-ghost"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div style="max-width:760px">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-map-pin-add-line"></i> Farm Details</span>
    </div>
    <div class="sfas-card-body">
      <div id="formAlert" style="display:none"></div>

      <!-- Farmer (admins/agronomists can pick; farmers auto-assigned) -->
      <?php if ($isSuperAdmin || hasPermission($userPermissions,'farms.view')): ?>
      <div class="sfas-form-group">
        <label class="sfas-label">Farmer <span class="req">*</span></label>
        <select id="farmer_id" class="sfas-select">
          <option value="">— Select farmer —</option>
          <?php foreach ($farmers as $f): ?>
          <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="sfas-form-group">
        <label class="sfas-label">Farm Name <span class="req">*</span></label>
        <input type="text" id="farm_name" class="sfas-input" placeholder="e.g. Karama Valley Farm">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="district" class="sfas-select">
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Bugesera','Musanze','Huye','Kigali'] as $d): ?>
            <option value="<?= $d ?>" <?= $d==='Nyagatare'?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Sector</label>
          <input type="text" id="sector" class="sfas-input" placeholder="e.g. Karama">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Cell / Village</label>
        <input type="text" id="cell" class="sfas-input" placeholder="e.g. Nyagahanga">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Size (hectares)</label>
          <input type="number" id="size_ha" class="sfas-input" placeholder="0.00" step="0.01" min="0">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Soil Type</label>
          <select id="soil_type" class="sfas-select">
            <option value="">— Select —</option>
            <?php foreach(['Clay','Sandy','Loam','Sandy-Loam','Clay-Loam','Silt'] as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Water Source</label>
          <select id="water_source" class="sfas-select">
            <option value="Rain-fed">Rain-fed</option>
            <option value="Irrigation">Irrigation</option>
            <option value="Both">Both</option>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Latitude <span style="font-size:.75rem;color:var(--text-muted)">(optional)</span></label>
          <input type="number" id="latitude" class="sfas-input" placeholder="-1.2956" step="0.0000001">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Longitude <span style="font-size:.75rem;color:var(--text-muted)">(optional)</span></label>
          <input type="number" id="longitude" class="sfas-input" placeholder="30.3256" step="0.0000001">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Notes</label>
        <textarea id="notes" class="sfas-textarea" placeholder="Any additional details about the farm…" rows="3"></textarea>
      </div>

      <div style="display:flex;gap:.75rem;margin-top:.5rem">
        <button class="sfas-btn sfas-btn-primary" id="saveBtn" onclick="saveFarm()">
          <i class="ri-save-line"></i> Register Farm
        </button>
        <a href="<?= url('admin/farms') ?>" class="sfas-btn sfas-btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</div>

<script>
const B = window.BASE_URL;
const isAdmin = <?= ($isSuperAdmin||hasPermission($userPermissions,'farms.view'))?'true':'false' ?>;

function saveFarm() {
  const alertEl = document.getElementById('formAlert');
  alertEl.style.display = 'none';

  const farm_name = document.getElementById('farm_name').value.trim();
  if (!farm_name) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Farm name is required.</span></div>';
    alertEl.style.display = 'block'; return;
  }

  const body = {
    farm_name,
    district:     document.getElementById('district').value,
    sector:       document.getElementById('sector').value.trim(),
    cell:         document.getElementById('cell').value.trim(),
    size_ha:      document.getElementById('size_ha').value || null,
    soil_type:    document.getElementById('soil_type').value || null,
    water_source: document.getElementById('water_source').value,
    latitude:     document.getElementById('latitude').value || null,
    longitude:    document.getElementById('longitude').value || null,
    notes:        document.getElementById('notes').value.trim(),
  };

  if (isAdmin) {
    const fid = document.getElementById('farmer_id')?.value;
    if (!fid) {
      alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Please select a farmer.</span></div>';
      alertEl.style.display = 'block'; return;
    }
    body.farmer_id = fid;
  }

  const btn = document.getElementById('saveBtn');
  btn.disabled = true; btn.innerHTML = '<span class="sfas-spinner"></span> Saving…';

  fetch(B+'/api/farms?action=create', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  }).then(r=>r.json()).then(d => {
    if (d.success) {
      mgSuccess('Farm Registered!', d.message, ()=>{ window.location.href = B+'/admin/farms'; });
    } else {
      alertEl.innerHTML = `<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${d.message}</span></div>`;
      alertEl.style.display = 'block';
      btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Register Farm';
    }
  }).catch(()=>{
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error. Please try again.</span></div>';
    alertEl.style.display = 'block';
    btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Register Farm';
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
