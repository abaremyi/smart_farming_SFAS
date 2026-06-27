<?php
/**
 * SFAS — Create Advisory Tip
 * File: modules/Advisory/views/advisory-create.php
 */
$pageTitle   = 'New Advisory Tip';
$currentPage = 'advisory';
$requiredPermission = 'advisory.create';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

$db    = Database::getConnection();
$model = new AdvisoryModel($db);
$crops = $model->getAllCrops();

$categories = ['Crop Management','Pest & Disease','Soil Health','Irrigation','Harvest & Post-Harvest','Market','General'];
$districts  = ['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Bugesera','Musanze','Huye','Kigali'];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/advisory') ?>">Advisory Tips</a>
      <span>/</span><span>New Tip</span>
    </div>
    <h1 class="page-title">Create Advisory Tip</h1>
    <p class="page-sub">Share expert farming knowledge with Rwanda's farmers</p>
  </div>
  <a href="<?= url('admin/advisory') ?>" class="sfas-btn sfas-btn-ghost"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div style="max-width:820px">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-quill-pen-line"></i> Tip Details</span>
    </div>
    <div class="sfas-card-body">
      <div id="formAlert" style="display:none"></div>

      <div class="sfas-form-group">
        <label class="sfas-label">Title <span class="req">*</span></label>
        <input type="text" id="title" class="sfas-input" placeholder="e.g. Maize Planting Best Practices for Season A">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Category <span class="req">*</span></label>
          <select id="category" class="sfas-select">
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat ?>"><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Related Crop</label>
          <select id="crop_id" class="sfas-select">
            <option value="">All Crops</option>
            <?php foreach($crops as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Season</label>
          <select id="season" class="sfas-select">
            <option value="">All Seasons</option>
            <option>Season A</option><option>Season B</option><option>Year-round</option>
          </select>
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">District <span style="font-size:.75rem;color:var(--text-muted)">(leave blank to apply to all districts)</span></label>
        <select id="district" class="sfas-select">
          <option value="">All Districts</option>
          <?php foreach($districts as $d): ?>
          <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Content <span class="req">*</span></label>
        <textarea id="content" class="sfas-textarea" rows="10"
          placeholder="Write the full advisory tip here. Be specific: mention planting rates, spacing, chemical names, timing, etc."></textarea>
        <div class="sfas-input-hint">Be detailed and practical. Use specific measurements (kg/ha, cm spacing, pH levels, etc.)</div>
      </div>

      <!-- AI Assist button -->
      <div style="margin-bottom:1.25rem">
        <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="aiAssist()" id="aiBtn">
          <i class="ri-robot-2-line"></i> AI: Improve this tip
        </button>
        <span style="font-size:.77rem;color:var(--text-muted);margin-left:.5rem">Let the AI expand and improve your content</span>
      </div>

      <div style="display:flex;gap:.75rem">
        <button class="sfas-btn sfas-btn-primary" id="saveBtn" onclick="saveTip()">
          <i class="ri-save-line"></i> Publish Tip
        </button>
        <button class="sfas-btn sfas-btn-ghost" onclick="saveTip(0)">
          <i class="ri-draft-line"></i> Save as Draft
        </button>
        <a href="<?= url('admin/advisory') ?>" class="sfas-btn sfas-btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</div>

<script>
const B = window.BASE_URL;

async function saveTip(isActive=1) {
  const alertEl = document.getElementById('formAlert');
  alertEl.style.display='none';
  const title   = document.getElementById('title').value.trim();
  const content = document.getElementById('content').value.trim();
  if(!title)   { showAlert('Title is required.'); return; }
  if(!content) { showAlert('Content is required.'); return; }

  const btn = document.getElementById('saveBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';

  const body = {
    title, content,
    category: document.getElementById('category').value,
    crop_id:  document.getElementById('crop_id').value  || null,
    season:   document.getElementById('season').value   || null,
    district: document.getElementById('district').value || null,
    is_active: isActive,
  };

  try {
    const r = await fetch(B+'/api/advisory?action=create',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    const d = await r.json();
    if(d.success) mgSuccess('Tip Created!', d.message, ()=>{ window.location.href = B+'/admin/advisory'; });
    else showAlert(d.message);
  } catch(e) { showAlert('Network error. Please try again.'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Publish Tip'; }
}

function showAlert(msg) {
  const el = document.getElementById('formAlert');
  el.innerHTML=`<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${msg}</span></div>`;
  el.style.display='block'; el.scrollIntoView({behavior:'smooth',block:'nearest'});
}

async function aiAssist() {
  const title   = document.getElementById('title').value.trim();
  const content = document.getElementById('content').value.trim();
  const cat     = document.getElementById('category').value;
  if(!title && !content) { mgError('Need content','Please write at least a title or some content first.'); return; }

  const btn = document.getElementById('aiBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> AI working…';

  try {
    const prompt = `You are a Rwandan agricultural expert. Improve and expand this advisory tip for farmers:
Title: ${title}
Category: ${cat}
Content: ${content||'(empty — generate based on title)'}

Write a complete, detailed, practical advisory tip. Include specific rates, timings, product names available in Rwanda, and step-by-step instructions where applicable. Keep it under 300 words. Return only the improved content text, no preamble.`;

    const r = await fetch(B+'/api/ai/chat', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({message: prompt, history:[]})
    });
    const d = await r.json();
    if(d.success) {
      document.getElementById('content').value = d.reply;
      mgSuccess('AI Improved!','Content has been expanded by the AI. Review and edit as needed.');
    } else {
      mgError('AI Error', d.message || 'Could not improve content. Check your Anthropic API key.');
    }
  } catch(e) { mgError('Error','Network error.'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-robot-2-line"></i> AI: Improve this tip'; }
}
</script>

<?php require get_layout('admin-scripts'); ?>
