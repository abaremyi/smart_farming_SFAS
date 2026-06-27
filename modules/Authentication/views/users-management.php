<?php
/**
 * SFAS — User Management
 * File: modules/Authentication/views/users-management.php
 */
$pageTitle   = 'User Management';
$currentPage = 'users';
$requiredPermission = 'users.view';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/UserModel.php';

$db    = Database::getConnection();
$model = new UserModel($db);

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'role_id'=> (int)($_GET['role_id'] ?? 0) ?: null,
];
$users = $model->getAllUsers($filters);
$stats = $model->getUserStats();
$roles = $model->getAllRoles();

$statusColor = ['active'=>'badge-green','pending'=>'badge-amber','inactive'=>'badge-slate','suspended'=>'badge-red'];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Users</span>
    </div>
    <h1 class="page-title">User Management</h1>
    <p class="page-sub">Manage farmers, agronomists, and administrators</p>
  </div>
  <?php if ($isSuperAdmin || hasPermission($userPermissions,'users.create')): ?>
  <a href="<?= url('admin/users-add-user') ?>" class="sfas-btn sfas-btn-primary">
    <i class="ri-user-add-line"></i> Add User
  </a>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="sfas-stat-grid" style="margin-bottom:1.5rem">
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
    <div class="stat-icon"><i class="ri-group-line"></i></div>
    <div class="stat-val"><?= $stats['total'] ?></div>
    <div class="stat-label">Total Users</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
    <div class="stat-icon"><i class="ri-user-follow-line"></i></div>
    <div class="stat-val"><?= $stats['active'] ?></div>
    <div class="stat-label">Active</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--amber-500);--stat-bg:var(--amber-100)">
    <div class="stat-icon"><i class="ri-time-line"></i></div>
    <div class="stat-val"><?= $stats['pending'] ?></div>
    <div class="stat-label">Pending Activation</div>
  </div>
  <div class="sfas-stat" style="--stat-accent:var(--red-600);--stat-bg:var(--red-100)">
    <div class="stat-icon"><i class="ri-user-forbid-line"></i></div>
    <div class="stat-val"><?= (int)$stats['suspended'] + (int)$stats['inactive'] ?></div>
    <div class="stat-label">Suspended / Inactive</div>
  </div>
</div>

<!-- Pending Approval Banner -->
<?php if ((int)$stats['pending'] > 0): ?>
<div class="sfas-alert alert-warning" style="margin-bottom:1.25rem">
  <i class="ri-time-line"></i>
  <span>
    <strong><?= $stats['pending'] ?> user<?= $stats['pending']>1?'s':'' ?></strong> waiting for account activation.
    Review and activate below.
  </span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="sfas-card" style="margin-bottom:1.25rem">
  <div class="sfas-card-body" style="padding:.85rem 1.25rem">
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <input type="text" id="searchInput" class="sfas-input" placeholder="Search name, email, phone…"
        value="<?= htmlspecialchars($filters['search']) ?>" style="flex:1;max-width:280px">
      <select id="statusFilter" class="sfas-select" style="max-width:160px">
        <option value="">All Statuses</option>
        <?php foreach(['active','pending','inactive','suspended'] as $s): ?>
        <option value="<?= $s ?>" <?= $filters['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="roleFilter" class="sfas-select" style="max-width:160px">
        <option value="">All Roles</option>
        <?php foreach($roles as $r): ?>
        <option value="<?= $r['id'] ?>" <?= $filters['role_id']==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="sfas-btn sfas-btn-primary" onclick="applyFilters()"><i class="ri-search-line"></i> Filter</button>
      <button class="sfas-btn sfas-btn-ghost" onclick="window.location.href='<?= url('admin/users-management') ?>'"><i class="ri-refresh-line"></i></button>
    </div>
  </div>
</div>

<!-- Users Table -->
<div class="sfas-card">
  <div class="sfas-card-header">
    <span class="sfas-card-title"><i class="ri-group-line"></i> Users (<?= count($users) ?>)</span>
  </div>
  <div class="sfas-table-wrap">
    <table class="sfas-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email / Phone</th>
          <th>Role</th>
          <th>District</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted)">
          No users found matching the current filters.
        </td></tr>
        <?php else: ?>
        <?php foreach ($users as $i => $u): ?>
        <tr id="user<?= $u['id'] ?>">
          <td style="color:var(--text-muted)"><?= $i+1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.6rem">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--green-100);
                color:var(--green-700);display:flex;align-items:center;justify-content:center;
                font-size:.75rem;font-weight:700;flex-shrink:0">
                <?= strtoupper(substr($u['firstname'],0,1).substr($u['lastname'],0,1)) ?>
              </div>
              <div>
                <div style="font-weight:600"><?= htmlspecialchars($u['firstname'].' '.$u['lastname']) ?></div>
                <?php if (!empty($u['is_super_admin'])): ?>
                <span class="sfas-badge badge-red" style="font-size:.65rem">Super Admin</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <div><?= htmlspecialchars($u['email']) ?></div>
            <?php if ($u['phone']): ?><div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($u['phone']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php $rc=['Admin'=>'badge-red','Agronomist'=>'badge-blue','Farmer'=>'badge-green']; ?>
            <span class="sfas-badge <?= $rc[$u['role_display']]??'badge-slate' ?>">
              <?= htmlspecialchars($u['role_display'] ?? $u['role_name'] ?? '—') ?>
            </span>
          </td>
          <td><?= htmlspecialchars($u['district'] ?? '—') ?></td>
          <td>
            <span class="sfas-badge <?= $statusColor[$u['account_status']] ?? 'badge-slate' ?>">
              <?= ucfirst($u['account_status']) ?>
            </span>
          </td>
          <td style="color:var(--text-muted);font-size:.82rem">
            <?= $u['last_login'] ? date('d M Y H:i',strtotime($u['last_login'])) : '—' ?>
          </td>
          <td>
            <div style="display:flex;gap:.3rem;flex-wrap:wrap">
              <?php if ((int)$u['id'] !== (int)$currentUser->user_id && !$u['is_super_admin']): ?>

              <?php if ($u['account_status'] !== 'active'): ?>
              <button class="sfas-btn sfas-btn-outline sfas-btn-sm" style="color:var(--green-600)"
                onclick="changeStatus(<?= $u['id'] ?>,'active','<?= htmlspecialchars(addslashes($u['firstname'])) ?>')" title="Activate">
                <i class="ri-user-follow-line"></i>
              </button>
              <?php endif; ?>

              <?php if ($u['account_status'] === 'active'): ?>
              <button class="sfas-btn sfas-btn-ghost sfas-btn-sm"
                onclick="changeStatus(<?= $u['id'] ?>,'suspended','<?= htmlspecialchars(addslashes($u['firstname'])) ?>')" title="Suspend">
                <i class="ri-user-forbid-line"></i>
              </button>
              <?php endif; ?>

              <?php if ($isSuperAdmin || hasPermission($userPermissions,'users.delete')): ?>
              <button class="sfas-btn sfas-btn-danger sfas-btn-sm"
                onclick="deleteUser(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['firstname'].' '.$u['lastname'])) ?>')" title="Delete">
                <i class="ri-delete-bin-line"></i>
              </button>
              <?php endif; ?>
              <?php else: ?>
              <span style="font-size:.75rem;color:var(--text-muted);padding:.35rem">You</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const B = window.BASE_URL;

function applyFilters() {
  const p = new URLSearchParams();
  const s  = document.getElementById('searchInput').value.trim();
  const st = document.getElementById('statusFilter').value;
  const r  = document.getElementById('roleFilter').value;
  if(s) p.set('search',s); if(st) p.set('status',st); if(r) p.set('role_id',r);
  window.location.href = B+'/admin/users-management'+(p.toString()?'?'+p:'');
}
document.getElementById('searchInput').addEventListener('keydown',e=>{ if(e.key==='Enter') applyFilters(); });

function changeStatus(id, status, name) {
  const labels = {active:'Activate',suspended:'Suspend',inactive:'Deactivate'};
  const label  = labels[status] || 'Update';
  mgConfirm(label+' User?', `${label} account for ${name}?`, ()=>{
    mgLoading('Updating…');
    fetch(B+'/api/users?action=update-status',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id, status})
    }).then(r=>r.json()).then(d=>{
      Swal.close();
      if(d.success) mgSuccess('Done!',d.message,()=>location.reload());
      else mgError('Error',d.message);
    });
  });
}

function deleteUser(id, name) {
  mgConfirm('Delete User?',`"${name}" will be permanently deleted. This cannot be undone.`,()=>{
    mgLoading('Deleting…');
    fetch(B+'/api/users?action=delete',{
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id})
    }).then(r=>r.json()).then(d=>{
      Swal.close();
      if(d.success){ document.getElementById('user'+id)?.remove(); mgSuccess('Deleted',d.message); }
      else mgError('Error',d.message);
    });
  });
}
</script>

<?php require get_layout('admin-scripts'); ?>
