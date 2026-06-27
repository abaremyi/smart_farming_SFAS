<?php
/**
 * SFAS — My Profile
 * File: modules/Authentication/views/profile.php
 */
$pageTitle   = 'My Profile';
$currentPage = 'profile';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/UserModel.php';

$db    = Database::getConnection();
$model = new UserModel($db);
$me    = $model->getUserById((int)$currentUser->user_id);

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>My Profile</span>
    </div>
    <h1 class="page-title">My Profile</h1>
    <p class="page-sub">Manage your account information and password</p>
  </div>
</div>

<div class="sfas-grid sfas-grid-2" style="max-width:900px">

  <!-- Profile Info Card -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-user-line"></i> Profile Information</span>
    </div>
    <div class="sfas-card-body">
      <!-- Avatar -->
      <div style="text-align:center;margin-bottom:1.5rem">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--green-500),var(--gold-500));
          margin:0 auto .75rem;display:flex;align-items:center;justify-content:center;
          font-size:1.8rem;font-weight:700;color:white">
          <?= $userInitials ?>
        </div>
        <div style="font-weight:700;font-size:1.1rem"><?= $userFullName ?></div>
        <div style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars($me['email']??'') ?></div>
        <span class="sfas-badge badge-green" style="margin-top:.4rem"><?= htmlspecialchars($me['role_display']??$me['role_name']??'User') ?></span>
      </div>

      <div id="profileAlert" style="display:none"></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem">
        <div class="sfas-form-group">
          <label class="sfas-label">First Name</label>
          <input type="text" id="pFirstname" class="sfas-input" value="<?= htmlspecialchars($me['firstname']??'') ?>">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Last Name</label>
          <input type="text" id="pLastname" class="sfas-input" value="<?= htmlspecialchars($me['lastname']??'') ?>">
        </div>
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Phone</label>
        <input type="text" id="pPhone" class="sfas-input" value="<?= htmlspecialchars($me['phone']??'') ?>" placeholder="+250 7XX XXX XXX">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">District</label>
        <select id="pDistrict" class="sfas-select">
          <option value="">— Select —</option>
          <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Musanze','Huye','Kigali','Rubavu'] as $d): ?>
          <option value="<?= $d ?>" <?= ($me['district']??'')===$d?'selected':'' ?>><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="sfas-btn sfas-btn-primary" id="saveProfileBtn" onclick="saveProfile()">
        <i class="ri-save-line"></i> Save Changes
      </button>
    </div>
  </div>

  <!-- Change Password Card -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-lock-line"></i> Change Password</span>
    </div>
    <div class="sfas-card-body">
      <div id="pwdAlert" style="display:none"></div>

      <div class="sfas-form-group">
        <label class="sfas-label">Current Password</label>
        <input type="password" id="currentPwd" class="sfas-input" placeholder="Your current password">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">New Password</label>
        <input type="password" id="newPwd" class="sfas-input" placeholder="Min 8 characters">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Confirm New Password</label>
        <input type="password" id="confirmPwd" class="sfas-input" placeholder="Repeat new password">
      </div>

      <button class="sfas-btn sfas-btn-primary" id="savePwdBtn" onclick="changePassword()">
        <i class="ri-lock-password-line"></i> Update Password
      </button>

      <!-- Account Info -->
      <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border)">
        <div style="font-size:.82rem;color:var(--text-muted)">
          <div style="margin-bottom:.4rem"><i class="ri-calendar-line"></i> Joined: <strong><?= date('d M Y',strtotime($me['created_at']??'now')) ?></strong></div>
          <div style="margin-bottom:.4rem"><i class="ri-login-box-line"></i> Last login: <strong><?= $me['last_login']?date('d M Y H:i',strtotime($me['last_login'])):'—' ?></strong></div>
          <div><i class="ri-shield-user-line"></i> Role: <strong><?= htmlspecialchars($me['role_display']??$me['role_name']??'—') ?></strong></div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const B = window.BASE_URL;
const ME_ID = <?= (int)$currentUser->user_id ?>;

async function saveProfile() {
  const alertEl = document.getElementById('profileAlert');
  alertEl.style.display='none';
  const btn=document.getElementById('saveProfileBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';
  try {
    const r = await fetch(B+'/api/users?action=update',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        id:        ME_ID,
        firstname: document.getElementById('pFirstname').value.trim(),
        lastname:  document.getElementById('pLastname').value.trim(),
        phone:     document.getElementById('pPhone').value.trim()||null,
        district:  document.getElementById('pDistrict').value||null,
      })
    });
    const d=await r.json();
    if(d.success) { alertEl.innerHTML='<div class="sfas-alert alert-success"><i class="ri-check-circle-line"></i><span>Profile updated successfully.</span></div>'; alertEl.style.display='block'; }
    else { alertEl.innerHTML=`<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${d.message}</span></div>`; alertEl.style.display='block'; }
  } catch(e) { alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error.</span></div>'; alertEl.style.display='block'; }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Changes'; }
}

async function changePassword() {
  const alertEl = document.getElementById('pwdAlert');
  alertEl.style.display='none';
  const current = document.getElementById('currentPwd').value;
  const newPwd  = document.getElementById('newPwd').value;
  const confirm = document.getElementById('confirmPwd').value;
  if(!current||!newPwd){ alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>All fields required.</span></div>'; alertEl.style.display='block'; return; }
  if(newPwd.length<8){ alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>New password must be at least 8 characters.</span></div>'; alertEl.style.display='block'; return; }
  if(newPwd!==confirm){ alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Passwords do not match.</span></div>'; alertEl.style.display='block'; return; }
  const btn=document.getElementById('savePwdBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Updating…';
  try {
    const r=await fetch(B+'/api/profile?action=change-password',{
      method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({current_password:current,new_password:newPwd,confirm_password:confirm})
    });
    const d=await r.json();
    if(d.success){ alertEl.innerHTML='<div class="sfas-alert alert-success"><i class="ri-check-circle-line"></i><span>Password updated!</span></div>'; alertEl.style.display='block';
      document.getElementById('currentPwd').value='';document.getElementById('newPwd').value='';document.getElementById('confirmPwd').value='';
    } else { alertEl.innerHTML=`<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${d.message}</span></div>`; alertEl.style.display='block'; }
  } catch(e){ alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error.</span></div>'; alertEl.style.display='block'; }
  finally{ btn.disabled=false; btn.innerHTML='<i class="ri-lock-password-line"></i> Update Password'; }
}
</script>

<?php require get_layout('admin-scripts'); ?>
