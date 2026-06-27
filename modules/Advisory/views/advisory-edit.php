<?php
/**
 * SFAS — Edit Advisory Tip
 * File: modules/Advisory/views/advisory-edit.php
 */
$pageTitle   = 'Edit Advisory Tip';
$currentPage = 'advisory';
$requiredPermission = 'advisory.edit';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db    = Database::getConnection();
$model = new AdvisoryModel($db);

$id  = (int)($_GET['id'] ?? 0);
$tip = $model->getTipById($id);
if (!$tip) { header('Location: '.url('admin/advisory')); exit; }

$crops      = $model->getAllCrops();
$categories = ['Crop Management','Pest & Disease','Soil Health','Irrigation','Harvest & Post-Harvest','Market','General'];
$districts  = ['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Bugesera','Musanze','Huye','Kigali'];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/advisory') ?>">Advisory Tips</a>
      <span>/</span><span>Edit</span>
    </div>
    <h1 class="page-title">Edit Advisory Tip</h1>
  </div>
  <a href="<?= url('admin/advisory') ?>" class="sfas-btn sfas-btn-ghost"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div style="max-width:820px">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-edit-line"></i> Edit Tip #<?= $id ?></span>
      <span class="sfas-badge <?= $tip['is_active']?'badge-green':'badge-slate' ?>"><?= $tip['is_active']?'Active':'Inactive' ?></span>
    </div>
    <div class="sfas-card-body">
      <div id="formAlert" style="display:none"></div>

      <div class="sfas-form-group">
        <label class="sfas-label">Title <span class="req">*</span></label>
        <input type="text" id="title" class="sfas-input" value="<?= htmlspecialchars($tip['title']) ?>">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Category</label>
          <select id="category" class="sfas-select">
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat ?>" <?= $tip['category']===$cat?'selected':'' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Related Crop</label>
          <select id="crop_id" class="sfas-select">
            <option value="">All Crops</option>
            <?php foreach($crops as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $tip['crop_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Season</label>
          <select id="season" class="sfas-select">
            <option value="">All Seasons</option>
            <?php foreach(['Season A','Season B','Year-round'] as $s): ?>
            <option value="<?= $s ?>" <?= $tip['season']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="district" class="sfas-select">
            <option value="">All Districts</option>
            <?php foreach($districts as $d): ?>
            <option value="<?= $d ?>" <?= $tip['district']===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Status</label>
          <select id="is_active" class="sfas-select">
            <option value="1" <?= $tip['is_active']?'selected':'' ?>>Active (visible to farmers)</option>
            <option value="0" <?= !$tip['is_active']?'selected':'' ?>>Inactive (draft)</option>
          </select>
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Content <span class="req">*</span></label>
        <textarea id="content" class="sfas-textarea" rows="12"><?= htmlspecialchars($tip['content']) ?></textarea>
      </div>

      <div style="display:flex;gap:.75rem">
        <button class="sfas-btn sfas-btn-primary" id="saveBtn" onclick="saveTip()">
          <i class="ri-save-line"></i> Save Changes
        </button>
        <a href="<?= url('admin/advisory') ?>" class="sfas-btn sfas-btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</div>

<script>
const B = window.BASE_URL;
const TIP_ID = <?= $id ?>;

async function saveTip() {
  const alertEl = document.getElementById('formAlert');
  alertEl.style.display='none';
  const title   = document.getElementById('title').value.trim();
  const content = document.getElementById('content').value.trim();
  if(!title)   { alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Title required.</span></div>'; alertEl.style.display='block'; return; }
  if(!content) { alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Content required.</span></div>'; alertEl.style.display='block'; return; }

  const btn=document.getElementById('saveBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';

  try {
    const r = await fetch(B+'/api/advisory?action=update',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        id: TIP_ID, title, content,
        category:  document.getElementById('category').value,
        crop_id:   document.getElementById('crop_id').value||null,
        season:    document.getElementById('season').value||null,
        district:  document.getElementById('district').value||null,
        is_active: document.getElementById('is_active').value,
      })
    });
    const d = await r.json();
    if(d.success) mgSuccess('Saved!',d.message,()=>{ window.location.href=B+'/admin/advisory'; });
    else { alertEl.innerHTML=`<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${d.message}</span></div>`; alertEl.style.display='block'; }
  } catch(e) { alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error.</span></div>'; alertEl.style.display='block'; }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Changes'; }
}
</script>

<?php require get_layout('admin-scripts'); ?>
