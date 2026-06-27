<?php
/**
 * SFAS — Add User View
 * File: modules/Authentication/views/users-add-user.php
 */
$pageTitle   = 'Add User';
$currentPage = 'users';
$requiredPermission = 'users.create';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/UserModel.php';

$db    = Database::getConnection();
$model = new UserModel($db);
$roles = $model->getAllRoles();

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/users-management') ?>">Users</a>
      <span>/</span><span>Add User</span>
    </div>
    <h1 class="page-title">Add New User</h1>
    <p class="page-sub">Create a farmer, agronomist, or admin account directly (no OTP required)</p>
  </div>
  <a href="<?= url('admin/users-management') ?>" class="sfas-btn sfas-btn-ghost"><i class="ri-arrow-left-line"></i> Back</a>
</div>

<div style="max-width:680px">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-user-add-line"></i> User Details</span>
    </div>
    <div class="sfas-card-body">
      <div id="formAlert" style="display:none"></div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">First Name <span class="req">*</span></label>
          <input type="text" id="firstname" class="sfas-input" placeholder="First name">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Last Name <span class="req">*</span></label>
          <input type="text" id="lastname" class="sfas-input" placeholder="Last name">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Email <span class="req">*</span></label>
        <input type="email" id="email" class="sfas-input" placeholder="user@example.com">
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Phone</label>
        <input type="text" id="phone" class="sfas-input" placeholder="+250 7XX XXX XXX">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Role <span class="req">*</span></label>
          <select id="role_id" class="sfas-select" onchange="updateRoleName()">
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>" data-name="<?= htmlspecialchars($r['name']) ?>">
              <?= htmlspecialchars($r['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="district" class="sfas-select">
            <option value="">— Select district —</option>
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Musanze','Huye','Kigali','Rubavu'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.9rem">
        <div class="sfas-form-group">
          <label class="sfas-label">Password <span class="req">*</span></label>
          <input type="password" id="password" class="sfas-input" placeholder="Min 8 characters">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Confirm Password <span class="req">*</span></label>
          <input type="password" id="confirm_password" class="sfas-input" placeholder="Repeat password">
        </div>
      </div>

      <div class="sfas-form-group">
        <label class="sfas-label">Account Status</label>
        <select id="account_status" class="sfas-select">
          <option value="active">Active (can log in immediately)</option>
          <option value="pending">Pending (requires activation)</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <div class="sfas-alert alert-info" style="margin-bottom:1rem">
        <i class="ri-information-line"></i>
        <span>Users added by administrators are created without OTP email verification. The account status you choose applies immediately.</span>
      </div>

      <div style="display:flex;gap:.75rem">
        <button class="sfas-btn sfas-btn-primary" id="saveBtn" onclick="saveUser()">
          <i class="ri-user-add-line"></i> Create User
        </button>
        <a href="<?= url('admin/users-management') ?>" class="sfas-btn sfas-btn-ghost">Cancel</a>
      </div>
    </div>
  </div>
</div>

<script>
const B = window.BASE_URL;

function updateRoleName() {
  // Just for JS reference
}

async function saveUser() {
  const alertEl = document.getElementById('formAlert');
  alertEl.style.display = 'none';

  const firstname = document.getElementById('firstname').value.trim();
  const lastname  = document.getElementById('lastname').value.trim();
  const email     = document.getElementById('email').value.trim();
  const password  = document.getElementById('password').value;
  const confirm   = document.getElementById('confirm_password').value;
  const role_id   = document.getElementById('role_id').value;

  if (!firstname || !lastname) { showAlert('First and last name are required.'); return; }
  if (!email)    { showAlert('Email is required.'); return; }
  if (!password) { showAlert('Password is required.'); return; }
  if (password.length < 8) { showAlert('Password must be at least 8 characters.'); return; }
  if (password !== confirm) { showAlert('Passwords do not match.'); return; }

  const roleSelect = document.getElementById('role_id');
  const role_name  = roleSelect.options[roleSelect.selectedIndex].dataset.name;

  const btn = document.getElementById('saveBtn');
  btn.disabled = true; btn.innerHTML = '<span class="sfas-spinner"></span> Creating…';

  try {
    const r = await fetch(B+'/api/users?action=create', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        firstname, lastname, email,
        phone:          document.getElementById('phone').value.trim() || null,
        role_id:        parseInt(role_id),
        role_name,
        district:       document.getElementById('district').value || null,
        password,
        confirm_password: confirm,
        account_status: document.getElementById('account_status').value,
      })
    });
    const d = await r.json();
    if (d.success) mgSuccess('User Created!', d.message, ()=>{ window.location.href = B+'/admin/users-management'; });
    else showAlert(d.message);
  } catch(e) { showAlert('Network error. Please try again.'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-user-add-line"></i> Create User'; }
}

function showAlert(msg) {
  const el = document.getElementById('formAlert');
  el.innerHTML = `<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>${msg}</span></div>`;
  el.style.display = 'block';
}
</script>

<?php require get_layout('admin-scripts'); ?>
