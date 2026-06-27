<?php
/**
 * SFAS — Roles & Permissions Management
 * File: modules/Authentication/views/roles-permissions-management.php
 */
$pageTitle   = 'Roles & Permissions';
$currentPage = 'users';
$requiredPermission = 'settings.manage';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';

$db = Database::getConnection();

// Load roles with user counts
$roles = $db->query(
    "SELECT r.*, COUNT(DISTINCT u.id) AS user_count
     FROM roles r LEFT JOIN users u ON u.role_id=r.id
     GROUP BY r.id ORDER BY r.id"
)->fetchAll(PDO::FETCH_ASSOC);

// Load all permissions grouped by module
$permsRaw = $db->query(
    "SELECT * FROM permissions ORDER BY module, action"
)->fetchAll(PDO::FETCH_ASSOC);

$permsByModule = [];
foreach ($permsRaw as $p) {
    $permsByModule[$p['module']][] = $p;
}

// Load current role_permissions for all roles
$rpRows = $db->query("SELECT role_id, permission_id FROM role_permissions")
    ->fetchAll(PDO::FETCH_ASSOC);
$rpMap = []; // $rpMap[role_id][perm_id] = true
foreach ($rpRows as $rp) {
    $rpMap[$rp['role_id']][$rp['permission_id']] = true;
}

require get_layout('admin-head');
?>

<style>
.roles-grid{display:grid;grid-template-columns:280px 1fr;gap:1.25rem;align-items:start}
.role-item{
  padding:.75rem 1rem;border-radius:var(--radius-md);cursor:pointer;
  border:1.5px solid var(--border);background:var(--white);
  transition:var(--transition);margin-bottom:.4rem;
}
.role-item:hover{border-color:var(--green-400);background:var(--green-50)}
.role-item.active{border-color:var(--green-500);background:var(--green-50);box-shadow:0 0 0 3px rgba(45,154,78,.1)}
.role-item .role-name{font-weight:700;font-size:.9rem;color:var(--slate-800)}
.role-item .role-count{font-size:.75rem;color:var(--text-muted);margin-top:.15rem}
.perm-module{margin-bottom:1.1rem}
.perm-module-title{
  font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
  color:var(--green-700);padding:.4rem 0;border-bottom:1px solid var(--border);
  margin-bottom:.6rem;display:flex;align-items:center;gap:.4rem;
}
.perm-check-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem}
.perm-check{
  display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;
  border-radius:var(--radius-sm);font-size:.83rem;cursor:pointer;
  transition:background var(--transition);
}
.perm-check:hover{background:var(--green-50)}
.perm-check input[type=checkbox]{
  width:15px;height:15px;accent-color:var(--green-600);cursor:pointer;flex-shrink:0;
}
.perm-check .perm-label{color:var(--slate-700);line-height:1.3}
.perm-check .perm-key{font-size:.7rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace}
</style>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/users-management') ?>">Users</a>
      <span>/</span><span>Roles & Permissions</span>
    </div>
    <h1 class="page-title">Roles & Permissions</h1>
    <p class="page-sub">Manage what each role can do in SFAS</p>
  </div>
</div>

<div class="roles-grid">

  <!-- Roles Sidebar -->
  <div>
    <div class="sfas-card">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-shield-check-line"></i> Roles</span>
        <button class="sfas-btn sfas-btn-primary sfas-btn-sm" onclick="showCreateRole()">
          <i class="ri-add-line"></i> New
        </button>
      </div>
      <div class="sfas-card-body" style="padding:.75rem">
        <?php foreach ($roles as $role): ?>
        <div class="role-item <?= $role['id']===1?'active':'' ?>"
          id="roleItem<?= $role['id'] ?>"
          onclick="selectRole(<?= $role['id'] ?>,'<?= htmlspecialchars(addslashes($role['name'])) ?>')">
          <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <div>
              <div class="role-name">
                <?php if ($role['id']===1): ?><i class="ri-vip-crown-line" style="color:var(--gold-600)"></i> <?php endif; ?>
                <?= htmlspecialchars($role['name']) ?>
              </div>
              <div class="role-count">
                <?= $role['user_count'] ?> user<?= $role['user_count']!=1?'s':'' ?>
                <?php if ($role['description']): ?>
                <div style="margin-top:.15rem;color:var(--text-light);font-size:.72rem"><?= htmlspecialchars($role['description']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!in_array($role['id'],[1,2,3])): ?>
            <button class="sfas-btn sfas-btn-danger sfas-btn-sm"
              onclick="event.stopPropagation();deleteRole(<?= $role['id'] ?>,'<?= htmlspecialchars(addslashes($role['name'])) ?>')"
              title="Delete role" style="flex-shrink:0">
              <i class="ri-delete-bin-line"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Create Role Card -->
    <div class="sfas-card" id="createRoleCard" style="display:none;margin-top:.85rem">
      <div class="sfas-card-header">
        <span class="sfas-card-title"><i class="ri-add-circle-line"></i> New Role</span>
        <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="hideCreateRole()"><i class="ri-close-line"></i></button>
      </div>
      <div class="sfas-card-body">
        <div class="sfas-form-group">
          <label class="sfas-label">Role Name <span class="req">*</span></label>
          <input type="text" id="newRoleName" class="sfas-input" placeholder="e.g. Coordinator">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Description</label>
          <input type="text" id="newRoleDesc" class="sfas-input" placeholder="Brief description">
        </div>
        <button class="sfas-btn sfas-btn-primary" onclick="createRole()">
          <i class="ri-shield-check-line"></i> Create
        </button>
      </div>
    </div>
  </div>

  <!-- Permissions Panel -->
  <div class="sfas-card" id="permPanel">
    <div class="sfas-card-header">
      <span class="sfas-card-title" id="permPanelTitle">
        <i class="ri-key-line"></i> Select a role to edit permissions
      </span>
      <div style="display:flex;gap:.5rem" id="permActions" style="display:none">
        <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="checkAll()"><i class="ri-checkbox-multiple-line"></i> All</button>
        <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="uncheckAll()"><i class="ri-checkbox-blank-line"></i> None</button>
        <button class="sfas-btn sfas-btn-primary sfas-btn-sm" id="savePermBtn" onclick="savePermissions()" style="display:none">
          <i class="ri-save-line"></i> Save Permissions
        </button>
      </div>
    </div>
    <div class="sfas-card-body" id="permBody">
      <div class="sfas-empty" style="padding:2.5rem">
        <i class="ri-shield-keyhole-line"></i>
        <h3>No role selected</h3>
        <p>Click a role on the left to view and edit its permissions.</p>
      </div>
    </div>
  </div>

</div>

<!-- All role permission data for JS -->
<script>
const B      = window.BASE_URL;
const RP_MAP = <?= json_encode($rpMap) ?>;
const PERMS  = <?= json_encode($permsRaw) ?>;
const PERMS_BY_MODULE = <?= json_encode($permsByModule) ?>;

let activeRoleId   = 1;
let activeRoleName = 'Admin';

// Auto-select first role
document.addEventListener('DOMContentLoaded', () => selectRole(1, 'Admin'));

function selectRole(roleId, roleName) {
  activeRoleId   = roleId;
  activeRoleName = roleName;

  // Highlight selected
  document.querySelectorAll('.role-item').forEach(el => el.classList.remove('active'));
  document.getElementById('roleItem'+roleId)?.classList.add('active');

  // Update title
  const isAdmin = roleId === 1;
  document.getElementById('permPanelTitle').innerHTML =
    `<i class="ri-key-line"></i> Permissions for: <strong>${roleName}</strong>
     ${isAdmin ? '<span class="sfas-badge badge-red" style="margin-left:.5rem">Super Admin — All Access</span>' : ''}`;

  const actions = document.getElementById('permActions');
  actions.style.display = 'flex';
  document.getElementById('savePermBtn').style.display = isAdmin ? 'none' : '';

  // Build permission checkboxes
  let html = '';
  const currentPerms = RP_MAP[roleId] || {};

  for (const [module, perms] of Object.entries(PERMS_BY_MODULE)) {
    const moduleIcons = {
      'Users':'ri-group-line','Farms':'ri-map-2-line','Advisory':'ri-lightbulb-line',
      'Alerts':'ri-alarm-warning-line','Market':'ri-price-tag-3-line','Reports':'ri-bar-chart-box-line',
      'AI':'ri-robot-2-line','Weather':'ri-cloud-line','Settings':'ri-settings-4-line'
    };
    html += `<div class="perm-module">
      <div class="perm-module-title">
        <i class="${moduleIcons[module]||'ri-key-line'}"></i> ${module}
      </div>
      <div class="perm-check-grid">`;

    perms.forEach(p => {
      const checked = isAdmin || !!currentPerms[p.id];
      html += `<label class="perm-check">
        <input type="checkbox" name="perm_${p.id}" value="${p.id}"
          ${checked ? 'checked' : ''} ${isAdmin ? 'disabled' : ''}>
        <div>
          <div class="perm-label">${p.action}</div>
          <div class="perm-key">${p.key}</div>
        </div>
      </label>`;
    });
    html += `</div></div>`;
  }

  document.getElementById('permBody').innerHTML = html;
}

function checkAll() {
  document.querySelectorAll('#permBody input[type=checkbox]:not(:disabled)').forEach(cb => cb.checked = true);
}
function uncheckAll() {
  document.querySelectorAll('#permBody input[type=checkbox]:not(:disabled)').forEach(cb => cb.checked = false);
}

async function savePermissions() {
  const permIds = [...document.querySelectorAll('#permBody input[type=checkbox]:checked')]
    .map(cb => parseInt(cb.value));

  const btn = document.getElementById('savePermBtn');
  btn.disabled = true; btn.innerHTML = '<span class="sfas-spinner"></span> Saving…';

  try {
    const r = await fetch(B+'/api/roles?action=save-role-permissions', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ role_id: activeRoleId, permission_ids: permIds })
    });
    const d = await r.json();
    if (d.success) mgSuccess('Saved!', d.message);
    else mgError('Error', d.message);
  } catch(e) { mgError('Error', 'Network error.'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Permissions'; }
}

function showCreateRole() { document.getElementById('createRoleCard').style.display='block'; }
function hideCreateRole() { document.getElementById('createRoleCard').style.display='none'; }

async function createRole() {
  const name = document.getElementById('newRoleName').value.trim();
  const desc = document.getElementById('newRoleDesc').value.trim();
  if (!name) { mgError('Required','Role name is required.'); return; }
  const r = await fetch(B+'/api/roles?action=create-role', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({name, description: desc||null})
  });
  const d = await r.json();
  if (d.success) mgSuccess('Created!', d.message, ()=>location.reload());
  else mgError('Error', d.message);
}

function deleteRole(id, name) {
  mgConfirm('Delete Role?', `Delete the role "${name}"? Users assigned to it must be reassigned first.`, ()=>{
    fetch(B+'/api/roles?action=delete-role', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      if (d.success) mgSuccess('Deleted', d.message, ()=>location.reload());
      else mgError('Error', d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>