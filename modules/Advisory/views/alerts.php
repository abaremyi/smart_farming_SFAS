<?php
/**
 * SFAS — Pest Alerts Management
 * File: modules/Advisory/views/alerts.php
 */
$pageTitle   = 'Pest & Disease Alerts';
$currentPage = 'advisory';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db     = Database::getConnection();
$model  = new AdvisoryModel($db);
$alerts = $model->getAllAlerts([]);
$crops  = $model->getAllCrops();

$severityColor = ['Low'=>'badge-green','Medium'=>'badge-amber','High'=>'badge-red','Critical'=>'badge-red'];
$severityBg    = ['Low'=>'#e6f7ed','Medium'=>'#fef3c7','High'=>'#fee2e2','Critical'=>'#fff0eb'];
$severityBorder= ['Low'=>'var(--green-500)','Medium'=>'var(--amber-500)','High'=>'var(--red-600)','Critical'=>'#7c2d12'];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/advisory') ?>">Advisory</a>
      <span>/</span><span>Pest Alerts</span>
    </div>
    <h1 class="page-title">Pest & Disease Alerts</h1>
    <p class="page-sub">Active outbreak warnings for farmers in Rwanda</p>
  </div>
  <?php if ($isSuperAdmin || hasPermission($userPermissions,'alerts.manage')): ?>
  <button class="sfas-btn sfas-btn-primary" onclick="showCreatePanel()">
    <i class="ri-add-line"></i> New Alert
  </button>
  <?php endif; ?>
</div>

<!-- Create Alert Panel -->
<?php if ($isSuperAdmin || hasPermission($userPermissions,'alerts.manage')): ?>
<div id="createPanel" style="display:none;margin-bottom:1.5rem">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-alarm-warning-line"></i> Create New Alert</span>
      <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="hideCreatePanel()"><i class="ri-close-line"></i></button>
    </div>
    <div class="sfas-card-body">
      <div id="alertFormAlert" style="display:none"></div>
      <div class="sfas-form-group">
        <label class="sfas-label">Alert Title <span class="req">*</span></label>
        <input type="text" id="newTitle" class="sfas-input" placeholder="e.g. Fall Armyworm Outbreak — Karama Sector">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.75rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Pest / Disease Name</label>
          <input type="text" id="newPestName" class="sfas-input" placeholder="e.g. Fall Armyworm">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Affected Crop</label>
          <select id="newCropId" class="sfas-select">
            <option value="">All Crops</option>
            <?php foreach($crops as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Severity</label>
          <select id="newSeverity" class="sfas-select">
            <option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="newDistrict" class="sfas-select">
            <option value="">All Districts</option>
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Musanze','Huye','Kigali'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Sector <span style="font-size:.75rem;color:var(--text-muted)">(optional — leave blank for whole district)</span></label>
        <input type="text" id="newSector" class="sfas-input" placeholder="e.g. Karama">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Description & Recommended Action <span class="req">*</span></label>
        <textarea id="newDesc" class="sfas-textarea" rows="5"
          placeholder="Describe symptoms, affected areas, and what farmers should do. Include specific pesticide names and rates if applicable."></textarea>
      </div>
      <div style="display:flex;gap:.5rem">
        <button class="sfas-btn sfas-btn-primary" id="createAlertBtn" onclick="createAlert()">
          <i class="ri-alarm-warning-line"></i> Publish Alert
        </button>
        <button class="sfas-btn sfas-btn-ghost" onclick="hideCreatePanel()">Cancel</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Alerts List -->
<?php if (empty($alerts)): ?>
<div class="sfas-card">
  <div class="sfas-empty" style="padding:3rem">
    <i class="ri-alarm-warning-line"></i>
    <h3>No alerts active</h3>
    <p>No pest or disease outbreaks currently reported.</p>
  </div>
</div>
<?php else: ?>
<div class="sfas-grid sfas-grid-2">
  <?php foreach ($alerts as $a): ?>
  <div class="sfas-card" id="alert<?= $a['id'] ?>"
    style="border-left:4px solid <?= $severityBorder[$a['severity']]??'var(--amber-500)' ?>">
    <div class="sfas-card-body">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.6rem">
        <div>
          <h3 style="font-size:.95rem;font-weight:700;color:var(--slate-800)"><?= htmlspecialchars($a['title']) ?></h3>
          <?php if ($a['pest_name']): ?>
          <div style="font-size:.78rem;color:var(--text-muted);margin-top:.2rem">
            <i class="ri-bug-line"></i> <?= htmlspecialchars($a['pest_name']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:.3rem;flex-shrink:0">
          <span class="sfas-badge <?= $severityColor[$a['severity']]??'badge-slate' ?>"><?= $a['severity'] ?></span>
          <span class="sfas-badge <?= $a['is_active']?'badge-green':'badge-slate' ?>"><?= $a['is_active']?'Active':'Resolved' ?></span>
        </div>
      </div>
      <p style="font-size:.84rem;color:var(--text-muted);line-height:1.6;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden">
        <?= htmlspecialchars($a['description']) ?>
      </p>
      <div style="display:flex;flex-wrap:wrap;gap:.5rem;font-size:.75rem;color:var(--text-light);margin-top:.75rem;padding-top:.6rem;border-top:1px solid var(--border)">
        <?php if($a['crop_name']): ?><span><i class="ri-seedling-line"></i> <?= $a['crop_name'] ?></span><?php endif; ?>
        <?php if($a['district']): ?><span><i class="ri-map-pin-line"></i> <?= $a['district'] ?><?= $a['sector']?' · '.$a['sector']:'' ?></span><?php endif; ?>
        <span><i class="ri-calendar-line"></i> <?= date('d M Y',strtotime($a['created_at'])) ?></span>
      </div>
      <?php if ($isSuperAdmin || hasPermission($userPermissions,'alerts.manage')): ?>
      <div style="display:flex;gap:.35rem;margin-top:.75rem">
        <button class="sfas-btn sfas-btn-outline sfas-btn-sm"
          onclick="toggleAlert(<?= $a['id'] ?>,<?= $a['is_active']?0:1 ?>)">
          <i class="ri-<?= $a['is_active']?'checkbox-circle':'circle' ?>-line"></i>
          <?= $a['is_active']?'Mark Resolved':'Reactivate' ?>
        </button>
        <button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="deleteAlert(<?= $a['id'] ?>)">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const B = window.BASE_URL;
function showCreatePanel(){ document.getElementById('createPanel').style.display='block'; window.scrollTo({top:0,behavior:'smooth'}); }
function hideCreatePanel(){ document.getElementById('createPanel').style.display='none'; }

async function createAlert() {
  const title = document.getElementById('newTitle').value.trim();
  const desc  = document.getElementById('newDesc').value.trim();
  if(!title||!desc){ mgError('Required','Title and description are required.'); return; }
  const btn=document.getElementById('createAlertBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Publishing…';
  try {
    const r = await fetch(B+'/api/alerts?action=create',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        title, description:desc,
        pest_name: document.getElementById('newPestName').value.trim()||null,
        crop_id:   document.getElementById('newCropId').value||null,
        severity:  document.getElementById('newSeverity').value,
        district:  document.getElementById('newDistrict').value||null,
        sector:    document.getElementById('newSector').value.trim()||null,
      })
    });
    const d=await r.json();
    if(d.success) mgSuccess('Alert Published!',d.message,()=>location.reload());
    else mgError('Error',d.message);
  } catch(e){ mgError('Error','Network error.'); }
  finally{ btn.disabled=false; btn.innerHTML='<i class="ri-alarm-warning-line"></i> Publish Alert'; }
}

function toggleAlert(id, active) {
  const label = active ? 'Reactivate' : 'Mark as Resolved';
  mgConfirm(label+'?',label+' this alert?',()=>{
    fetch(B+'/api/alerts?action=toggle',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify({id,active})
    }).then(r=>r.json()).then(d=>{
      if(d.success) mgSuccess('Done',d.message,()=>location.reload());
      else mgError('Error',d.message);
    });
  });
}
function deleteAlert(id){
  mgConfirm('Delete Alert?','This alert will be permanently deleted.',()=>{
    fetch(B+'/api/alerts?action=delete',{method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},body:JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if(d.success){ document.getElementById('alert'+id)?.remove(); }
      else mgError('Error',d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
