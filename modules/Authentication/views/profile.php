<?php
/**
 * SFAS — My Profile
 * File: modules/Authentication/views/profile.php
 *
 * Features:
 *  - Live photo upload with preview (no page reload)
 *  - Update name / phone / district / sector
 *  - Change password with current-password verification
 *  - Account info sidebar (joined date, last login, role)
 */
$pageTitle   = 'My Profile';
$currentPage = 'profile';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 1) . '/models/UserModel.php';

$db    = Database::getConnection();
$model = new UserModel($db);
$me    = $model->getUserById((int)$currentUser->user_id);

$photoUrl = '';
if (!empty($me['photo'])) {
    $photoUrl = upload_url($me['photo']);
}

require get_layout('admin-head');
?>
<style>
/* ── Photo upload ─────────────────────────────────────── */
.avatar-wrap{
  position:relative;width:96px;height:96px;
  margin:0 auto 1rem;cursor:pointer;flex-shrink:0;
}
.avatar-circle{
  width:96px;height:96px;border-radius:50%;
  background:linear-gradient(135deg,var(--green-500),var(--gold-500));
  border:3px solid var(--green-200);
  display:flex;align-items:center;justify-content:center;
  font-size:2rem;font-weight:700;color:white;
  overflow:hidden;
}
.avatar-circle img{
  width:100%;height:100%;object-fit:cover;display:block;
}
.avatar-overlay{
  position:absolute;inset:0;border-radius:50%;
  background:rgba(0,0,0,.5);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:.2rem;opacity:0;transition:opacity .2s;
}
.avatar-wrap:hover .avatar-overlay{ opacity:1; }
.avatar-overlay i{ font-size:1.3rem;color:white; }
.avatar-overlay span{ font-size:.68rem;color:rgba(255,255,255,.85); }

/* Upload bar */
.upload-bar{
  display:none;margin-top:.75rem;
  background:var(--green-50);border:1px solid var(--green-200);
  border-radius:var(--radius-md);padding:.65rem .9rem;
}
.upload-bar .msg{ font-size:.82rem;color:var(--green-700);margin-bottom:.3rem; }
.upload-bar .track{
  height:5px;background:var(--border);border-radius:3px;overflow:hidden;
}
.upload-bar .fill{
  height:100%;background:var(--green-500);width:0;
  transition:width .4s ease;border-radius:3px;
}
</style>

<!-- Page header -->
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

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;max-width:900px">

  <!-- ── LEFT: Photo + Info ─────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:1.1rem">

    <!-- Photo Card -->
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-camera-line"></i> Profile Photo</span>
      </div>
      <div class="sfas-card-body" style="text-align:center">

        <!-- Hidden file input -->
        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp,image/gif"
               style="display:none" onchange="handlePhotoSelect(this)">

        <!-- Clickable avatar -->
        <div class="avatar-wrap" onclick="document.getElementById('photoInput').click()" title="Click to change photo">
          <div class="avatar-circle" id="avatarCircle">
            <?php if ($photoUrl): ?>
              <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Profile photo" id="photoPreviewImg">
            <?php else: ?>
              <span id="avatarInitials"><?= htmlspecialchars($userInitials) ?></span>
              <img src="" alt="" id="photoPreviewImg" style="display:none">
            <?php endif; ?>
          </div>
          <div class="avatar-overlay">
            <i class="ri-camera-line"></i>
            <span>Change</span>
          </div>
        </div>

        <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.75rem">
          JPG · PNG · WEBP · GIF &nbsp;·&nbsp; Max 2 MB<br>
          Click the photo to upload a new one
        </div>

        <!-- Upload progress bar -->
        <div class="upload-bar" id="uploadBar">
          <div class="msg" id="uploadMsg">Uploading…</div>
          <div class="track"><div class="fill" id="uploadFill"></div></div>
        </div>

        <!-- Account summary -->
        <div style="margin-top:1.1rem;padding-top:1rem;border-top:1px solid var(--border);text-align:left">
          <div style="font-size:.8rem;color:var(--text-muted);display:flex;flex-direction:column;gap:.4rem">
            <div><i class="ri-shield-user-line"></i> <strong><?= htmlspecialchars($me['role_display']??$me['role_name']??'User') ?></strong></div>
            <?php if ($me['is_super_admin']): ?>
            <div><i class="ri-vip-crown-line" style="color:var(--gold-600)"></i> Super Administrator</div>
            <?php endif; ?>
            <div><i class="ri-map-pin-line"></i> <?= htmlspecialchars($me['district'] ?? 'District not set') ?></div>
            <div><i class="ri-calendar-line"></i> Joined <?= date('d M Y', strtotime($me['created_at'] ?? 'now')) ?></div>
            <div><i class="ri-login-box-line"></i> Last login: <strong><?= $me['last_login'] ? date('d M Y H:i', strtotime($me['last_login'])) : 'Never' ?></strong></div>
            <div>
              <span class="sfas-badge badge-green"><?= ucfirst($me['account_status'] ?? 'active') ?></span>
            </div>
          </div>
        </div>

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
          <label class="sfas-label">Current Password <span class="req">*</span></label>
          <input type="password" id="currentPwd" class="sfas-input" placeholder="Your current password">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">New Password <span class="req">*</span></label>
          <input type="password" id="newPwd" class="sfas-input" placeholder="Minimum 8 characters">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Confirm New Password <span class="req">*</span></label>
          <input type="password" id="confirmPwd" class="sfas-input" placeholder="Repeat new password">
        </div>

        <button class="sfas-btn sfas-btn-primary" id="savePwdBtn" onclick="changePassword()">
          <i class="ri-lock-password-line"></i> Update Password
        </button>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- ── RIGHT: Profile Info ─────────────────────────────── -->
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-user-settings-line"></i> Personal Information</span>
    </div>
    <div class="sfas-card-body">
      <div id="infoAlert" style="display:none"></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem">
        <div class="sfas-form-group">
          <label class="sfas-label">First Name <span class="req">*</span></label>
          <input type="text" id="pFirstname" class="sfas-input" value="<?= htmlspecialchars($me['firstname'] ?? '') ?>">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Last Name <span class="req">*</span></label>
          <input type="text" id="pLastname" class="sfas-input" value="<?= htmlspecialchars($me['lastname'] ?? '') ?>">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Email Address</label>
        <input type="email" id="pEmail" class="sfas-input" value="<?= htmlspecialchars($me['email'] ?? '') ?>">
        <div class="sfas-input-hint">Changing email will require you to log in again</div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Phone Number</label>
        <input type="text" id="pPhone" class="sfas-input"
               value="<?= htmlspecialchars($me['phone'] ?? '') ?>"
               placeholder="+250 7XX XXX XXX">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem">
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="pDistrict" class="sfas-select">
            <option value="">— Select district —</option>
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Bugesera','Musanze','Huye','Kigali','Rubavu','Muhanga','Karongi','Nyamasheke'] as $d): ?>
            <option value="<?= $d ?>" <?= ($me['district']??'')===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Sector</label>
          <input type="text" id="pSector" class="sfas-input"
                 value="<?= htmlspecialchars($me['sector'] ?? '') ?>"
                 placeholder="e.g. Karama">
        </div>
      </div>

      <div class="sfas-alert alert-info" style="margin-bottom:1rem">
        <i class="ri-information-line"></i>
        <span>Fields marked <strong>*</strong> are required. Other fields are optional.</span>
      </div>

      <button class="sfas-btn sfas-btn-primary" id="saveInfoBtn" onclick="saveInfo()">
        <i class="ri-save-line"></i> Save Changes
      </button>

    </div>
  </div>

</div>

<script>
const B    = window.BASE_URL;
const ME_ID = <?= (int)$currentUser->user_id ?>;

/* ═══════════════════════════════════════════════════════
   PHOTO UPLOAD
═══════════════════════════════════════════════════════ */
function handlePhotoSelect(input) {
  var file = input.files[0];
  if (!file) return;

  // Validate size client-side before uploading
  if (file.size > 2 * 1024 * 1024) {
    mgError('File too large', 'Photo must be smaller than 2 MB');
    input.value = '';
    return;
  }

  // Live preview immediately
  var reader = new FileReader();
  reader.onload = function(e) {
    var img = document.getElementById('photoPreviewImg');
    img.src = e.target.result;
    img.style.display = 'block';
    var initials = document.getElementById('avatarInitials');
    if (initials) initials.style.display = 'none';
  };
  reader.readAsDataURL(file);

  // Show progress bar
  var bar  = document.getElementById('uploadBar');
  var fill = document.getElementById('uploadFill');
  var msg  = document.getElementById('uploadMsg');
  bar.style.display  = 'block';
  fill.style.width   = '20%';
  msg.textContent    = 'Uploading photo…';
  msg.style.color    = 'var(--green-700)';

  // Build FormData — browser sets multipart boundary automatically
  var fd = new FormData();
  fd.append('photo', file);

  fetch(B + '/api/profile?action=upload-photo', {
    method:      'POST',
    credentials: 'include',
    // NO Content-Type header — browser sets it with correct boundary
    body: fd
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    fill.style.width = '100%';
    if (d.success) {
      msg.textContent = '✅ ' + d.message;
      msg.style.color = 'var(--green-600)';

      // Also update the nav avatar so it reflects immediately
      var navImg = document.querySelector('.sfas-nav-avatar img');
      if (navImg && d.photo_url) {
        navImg.src = d.photo_url;
      } else if (!navImg && d.photo_url) {
        // Nav showed initials — replace with img
        var navAvatar = document.querySelector('.sfas-nav-avatar');
        if (navAvatar) {
          navAvatar.innerHTML = '<img src="' + d.photo_url + '" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
        }
      }

      setTimeout(function() {
        bar.style.display = 'none';
        fill.style.width  = '0';
      }, 3000);
    } else {
      msg.textContent = '❌ ' + (d.message || 'Upload failed');
      msg.style.color = 'var(--red-600)';
      // Restore old photo on failure
      setTimeout(function() { bar.style.display = 'none'; }, 4000);
    }
  })
  .catch(function(err) {
    msg.textContent = '❌ Network error during upload. Please try again.';
    msg.style.color = 'var(--red-600)';
    setTimeout(function() { bar.style.display = 'none'; }, 4000);
  });

  // Reset input so same file can be re-selected
  input.value = '';
}

/* ═══════════════════════════════════════════════════════
   SAVE PROFILE INFO
═══════════════════════════════════════════════════════ */
async function saveInfo() {
  var alertEl = document.getElementById('infoAlert');
  alertEl.style.display = 'none';

  var firstname = document.getElementById('pFirstname').value.trim();
  var lastname  = document.getElementById('pLastname').value.trim();

  if (!firstname || !lastname) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>First and last name are required.</span></div>';
    alertEl.style.display = 'block';
    return;
  }

  var btn = document.getElementById('saveInfoBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="sfas-spinner"></span> Saving…';

  try {
    var r = await fetch(B + '/api/profile?action=update-info', {
      method:      'POST',
      credentials: 'include',
      headers:     {'Content-Type': 'application/json'},
      body: JSON.stringify({
        firstname: firstname,
        lastname:  lastname,
        email:     document.getElementById('pEmail').value.trim(),
        phone:     document.getElementById('pPhone').value.trim()    || null,
        district:  document.getElementById('pDistrict').value        || null,
        sector:    document.getElementById('pSector').value.trim()   || null,
      })
    });
    var d = await r.json();

    if (d.success) {
      alertEl.innerHTML = '<div class="sfas-alert alert-success"><i class="ri-check-circle-line"></i><span>' + d.message + '</span></div>';
    } else {
      alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>' + (d.message || 'Update failed') + '</span></div>';
    }
    alertEl.style.display = 'block';
  } catch(e) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error. Please check your connection.</span></div>';
    alertEl.style.display = 'block';
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="ri-save-line"></i> Save Changes';
}

/* ═══════════════════════════════════════════════════════
   CHANGE PASSWORD
═══════════════════════════════════════════════════════ */
async function changePassword() {
  var alertEl = document.getElementById('pwdAlert');
  alertEl.style.display = 'none';

  var current = document.getElementById('currentPwd').value;
  var newPwd  = document.getElementById('newPwd').value;
  var confirm = document.getElementById('confirmPwd').value;

  if (!current || !newPwd || !confirm) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>All three password fields are required.</span></div>';
    alertEl.style.display = 'block';
    return;
  }
  if (newPwd.length < 8) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>New password must be at least 8 characters.</span></div>';
    alertEl.style.display = 'block';
    return;
  }
  if (newPwd !== confirm) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>New passwords do not match.</span></div>';
    alertEl.style.display = 'block';
    return;
  }

  var btn = document.getElementById('savePwdBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="sfas-spinner"></span> Updating…';

  try {
    var r = await fetch(B + '/api/profile?action=change-password', {
      method:      'POST',
      credentials: 'include',
      headers:     {'Content-Type': 'application/json'},
      body: JSON.stringify({
        current_password: current,
        new_password:     newPwd,
        confirm_password: confirm
      })
    });
    var d = await r.json();

    if (d.success) {
      alertEl.innerHTML = '<div class="sfas-alert alert-success"><i class="ri-check-circle-line"></i><span>' + d.message + '</span></div>';
      document.getElementById('currentPwd').value = '';
      document.getElementById('newPwd').value     = '';
      document.getElementById('confirmPwd').value = '';
    } else {
      alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>' + (d.message || 'Password change failed') + '</span></div>';
    }
    alertEl.style.display = 'block';
  } catch(e) {
    alertEl.innerHTML = '<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error. Please try again.</span></div>';
    alertEl.style.display = 'block';
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="ri-lock-password-line"></i> Update Password';
}
</script>

<?php require get_layout('admin-scripts'); ?>