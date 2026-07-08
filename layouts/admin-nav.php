<?php
/**
 * SFAS — Admin Navigation (UPDATED)
 * File: layouts/admin-nav.php
 *
 * CHANGES:
 *  - Added Settings nav item (visible only to admin/settings.manage)
 *  - Avatar dropdown now links to /admin/settings (system settings)
 *  - /admin/profile-settings now links to the profile page
 *  - All existing items and JS behaviour unchanged
 */

$navItems = [
  [
    'label' => 'Dashboard',
    'url'   => url('admin/dashboard'),
    'icon'  => 'ri-dashboard-3-line',
    'page'  => 'dashboard',
    'perm'  => '',
  ],
  [
    'label' => 'Farms',
    'url'   => url('admin/farms'),
    'icon'  => 'ri-map-2-line',
    'page'  => 'farms',
    'perm'  => 'farms.view',
    'submenu' => [
      ['label'=>'All Farms',  'url'=>url('admin/farms'),        'icon'=>'ri-list-check-2'],
      ['label'=>'Add Farm',   'url'=>url('admin/farms/create'), 'icon'=>'ri-map-pin-add-line'],
      ['label'=>'Crops',      'url'=>url('admin/crops'),        'icon'=>'ri-seedling-line'],
    ],
  ],
  [
    'label' => 'Advisory',
    'url'   => url('admin/advisory'),
    'icon'  => 'ri-lightbulb-line',
    'page'  => 'advisory',
    'perm'  => 'advisory.view',
    'submenu' => [
      ['label'=>'All Tips',    'url'=>url('admin/advisory'),        'icon'=>'ri-article-line'],
      ['label'=>'Add Tip',     'url'=>url('admin/advisory/create'), 'icon'=>'ri-quill-pen-line'],
      ['label'=>'Pest Alerts', 'url'=>url('admin/alerts'),          'icon'=>'ri-alarm-warning-line'],
    ],
  ],
  [
    'label' => 'Market',
    'url'   => url('admin/market'),
    'icon'  => 'ri-price-tag-3-line',
    'page'  => 'market',
    'perm'  => 'market.manage',
  ],
  [
    'label' => 'AI Chat',
    'url'   => url('admin/ai-assistant'),
    'icon'  => 'ri-robot-2-line',
    'page'  => 'ai',
    'perm'  => 'ai.use',
  ],
  [
    'label' => 'IoT',
    'url'   => url('admin/iot-dashboard'),
    'icon'  => 'ri-device-line',
    'page'  => 'iot',
    'perm'  => 'iot.view',
  ],
  [
    'label' => 'Weather',
    'url'   => url('admin/weather'),
    'icon'  => 'ri-cloud-windy-line',
    'page'  => 'weather',
    'perm'  => 'weather.view',
  ],
  [
    'label' => 'Reports',
    'url'   => url('admin/reports'),
    'icon'  => 'ri-bar-chart-box-line',
    'page'  => 'reports',
    'perm'  => 'reports.view',
  ],
  [
    'label' => 'Users',
    'url'   => '#',
    'icon'  => 'ri-team-line',
    'page'  => 'users',
    'perm'  => 'users.view',
    'submenu' => [
      ['label'=>'All Users',           'url'=>url('admin/users-management'),              'icon'=>'ri-group-line'],
      ['label'=>'Add User',            'url'=>url('admin/users-add-user'),                'icon'=>'ri-user-add-line'],
      ['label'=>'Roles & Permissions', 'url'=>url('admin/roles-permissions-management'), 'icon'=>'ri-shield-check-line'],
    ],
  ],
  [
    // Settings — only visible to super-admins and users with settings.manage permission
    'label' => 'Settings',
    'url'   => url('admin/settings'),
    'icon'  => 'ri-settings-4-line',
    'page'  => 'settings',
    'perm'  => 'settings.manage',
  ],
];

$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
?>

<div class="sfas-nav-overlay" id="sfasNavOverlay" onclick="closeMobileNav()"></div>

<nav class="sfas-nav">

  <!-- Logo -->
  <a class="sfas-nav-logo" href="<?= url('admin/dashboard') ?>">
    <div class="logo-leaf"><i class="ri-plant-line"></i></div>
    SFAS
  </a>

  <!-- Main nav links -->
  <div class="sfas-nav-links" id="sfasNavLinks">
    <?php foreach ($navItems as $item):

      // Permission check
      if (!empty($item['perm']) && !$isSuperAdmin && !hasPermission($userPermissions, $item['perm'])) continue;

      $isActive = (isset($currentPage) && $currentPage === $item['page']);
      $hasSub   = !empty($item['submenu']);

      // Auto-activate parent when a submenu child URL is active
      if ($hasSub && !$isActive) {
        foreach ($item['submenu'] as $sub) {
          $sp = parse_url($sub['url'], PHP_URL_PATH) ?? '';
          if ($sp && str_contains($currentUrl, $sp)) { $isActive = true; break; }
        }
      }
    ?>

      <?php if ($hasSub): ?>
        <!-- Nav item WITH submenu -->
        <div class="sfas-nav-item<?= $isActive ? ' open' : '' ?>" data-has-submenu>
          <button class="sfas-nav-link<?= $isActive ? ' active' : '' ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <?= htmlspecialchars($item['label']) ?>
            <i class="ri-arrow-down-s-line sub-caret"></i>
          </button>
          <div class="sfas-submenu">
            <?php foreach ($item['submenu'] as $sub):
              $sp = parse_url($sub['url'], PHP_URL_PATH) ?? '';
              $sa = $sp && str_contains($currentUrl, $sp);
            ?>
              <a href="<?= $sub['url'] ?>" class="<?= $sa ? 'sub-active' : '' ?>">
                <i class="<?= $sub['icon'] ?>"></i>
                <?= htmlspecialchars($sub['label']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

      <?php else: ?>
        <!-- Nav item WITHOUT submenu -->
        <a href="<?= $item['url'] ?>"
           class="sfas-nav-link<?= $isActive ? ' active' : '' ?>">
          <i class="<?= $item['icon'] ?>"></i>
          <?= htmlspecialchars($item['label']) ?>
        </a>

      <?php endif; ?>

    <?php endforeach; ?>
  </div><!-- /sfas-nav-links -->

  <!-- Right side: avatar + hamburger -->
  <div class="sfas-nav-right">

    <!-- Avatar & dropdown -->
    <div id="sfasAvatarWrap" style="position:relative">

      <div class="sfas-nav-avatar" id="sfasAvatarBtn" title="<?= $userFullName ?>">
        <?php if (!empty($userPhoto)): ?>
          <img src="<?= upload_url( $userPhoto) ?>" alt="<?= htmlspecialchars($userFullName) ?>">
        <?php else: ?>
          <?= $userInitials ?>
        <?php endif; ?>
      </div>

      <div class="sfas-avatar-dropdown" id="sfasAvatarMenu">

        <!-- User info header -->
        <div class="dd-header">
          <div class="dd-name"><?= $userFullName ?></div>
          <div class="dd-email"><?= htmlspecialchars($currentUser->email ?? '') ?></div>
          <div class="dd-role">
            <i class="ri-seedling-line"></i>
            <?= htmlspecialchars($currentUser->role_name ?? 'User') ?>
            <?php if ($isSuperAdmin): ?>
              · <i class="ri-vip-crown-line" style="color:var(--gold-500)"></i> Super Admin
            <?php endif; ?>
          </div>
        </div>

        <!-- Dropdown links -->
        <a href="<?= url('admin/profile') ?>">
          <i class="ri-user-line"></i> My Profile
        </a>

        <!-- Settings — shown to admins and settings.manage users -->
        <?php if ($isSuperAdmin || hasPermission($userPermissions, 'settings.manage')): ?>
        <a href="<?= url('admin/settings') ?>">
          <i class="ri-settings-4-line"></i> System Settings
        </a>
        <?php endif; ?>

        <div class="dd-divider"></div>

        <a href="#" class="danger" id="sfasLogoutBtn">
          <i class="ri-logout-box-r-line"></i> Sign Out
        </a>

      </div><!-- /sfas-avatar-dropdown -->
    </div><!-- /sfasAvatarWrap -->

    <!-- Hamburger (mobile only) -->
    <button class="sfas-hamburger" id="sfasHamburger"
            aria-label="Toggle navigation" aria-expanded="false">
      <i class="ri-menu-3-line"></i>
    </button>

  </div><!-- /sfas-nav-right -->

</nav>

<main class="sfas-main" id="sfasMain">

<script>
(function () {

  /* ══════════════════════════════════════════════════════
     SUBMENUS
  ══════════════════════════════════════════════════════ */
  function closeAllSubs() {
    document.querySelectorAll('.sfas-nav-item[data-has-submenu]')
      .forEach(function (i) { i.classList.remove('open'); });
  }

  document.querySelectorAll('.sfas-nav-item[data-has-submenu]').forEach(function (item) {
    var btn = item.querySelector('button.sfas-nav-link');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var wasOpen = item.classList.contains('open');
      closeAllSubs();
      if (!wasOpen) item.classList.add('open');
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.sfas-nav-item[data-has-submenu]') && window.innerWidth >= 901) {
      closeAllSubs();
    }
  });

  /* ══════════════════════════════════════════════════════
     MOBILE NAV
  ══════════════════════════════════════════════════════ */
  var _mobOpen  = false;
  var hamburger = document.getElementById('sfasHamburger');
  var navLinks  = document.getElementById('sfasNavLinks');
  var overlay   = document.getElementById('sfasNavOverlay');

  function openMob() {
    _mobOpen = true;
    navLinks.classList.add('mobile-open');
    overlay.classList.add('visible');
    hamburger.setAttribute('aria-expanded', 'true');
    hamburger.querySelector('i').className = 'ri-close-line';
    document.body.style.overflow = 'hidden';
  }
  function closeMob() {
    _mobOpen = false;
    navLinks.classList.remove('mobile-open');
    overlay.classList.remove('visible');
    hamburger.setAttribute('aria-expanded', 'false');
    hamburger.querySelector('i').className = 'ri-menu-3-line';
    document.body.style.overflow = '';
  }
  window.closeMobileNav = closeMob;
  hamburger.addEventListener('click', function () { _mobOpen ? closeMob() : openMob(); });

  /* ══════════════════════════════════════════════════════
     AVATAR DROPDOWN
  ══════════════════════════════════════════════════════ */
  var avatarBtn  = document.getElementById('sfasAvatarBtn');
  var avatarWrap = document.getElementById('sfasAvatarWrap');
  var avatarMenu = document.getElementById('sfasAvatarMenu');
  var _avOpen    = false;

  function openAvatar()  { _avOpen = true;  avatarMenu.classList.add('open'); }
  function closeAvatar() { _avOpen = false; avatarMenu.classList.remove('open'); }

  avatarBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    _avOpen ? closeAvatar() : openAvatar();
  });
  document.addEventListener('click', function (e) {
    if (_avOpen && !avatarWrap.contains(e.target)) closeAvatar();
  });

  /* ══════════════════════════════════════════════════════
     LOGOUT
  ══════════════════════════════════════════════════════ */
  document.getElementById('sfasLogoutBtn').addEventListener('click', function (e) {
    e.preventDefault();

    if (typeof Swal === 'undefined') {
      if (confirm('Sign out of SFAS?')) {
        fetch(window.BASE_URL + '/api/auth?action=logout', { method: 'POST', credentials: 'include' })
          .finally(function () { window.location.href = window.BASE_URL + '/logout'; });
      }
      return;
    }

    Swal.fire({
      icon: 'question',
      title: 'Sign Out?',
      text: 'You will be signed out of SFAS.',
      showCancelButton: true,
      confirmButtonColor: '#b91c1c',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Yes, sign out',
      cancelButtonText: 'Cancel',
    }).then(function (r) {
      if (r.isConfirmed) {
        fetch(window.BASE_URL + '/api/auth?action=logout', { method: 'POST', credentials: 'include' })
          .finally(function () { window.location.href = window.BASE_URL + '/logout'; });
      }
    });
  });

  /* ══════════════════════════════════════════════════════
     HEARTBEAT — keeps JWT session alive
  ══════════════════════════════════════════════════════ */
  setInterval(function () {
    fetch(window.BASE_URL + '/api/auth?action=heartbeat', {
      method: 'POST', credentials: 'include'
    }).catch(function () {});
  }, 10 * 60 * 1000); // every 10 minutes

  /* ══════════════════════════════════════════════════════
     GLOBAL SWAL HELPERS
     mgSuccess, mgError, mgConfirm, mgLoading
  ══════════════════════════════════════════════════════ */
  if (typeof Swal !== 'undefined') {
    window.mgSuccess = function (t, m, cb) {
      Swal.fire({ icon: 'success', title: t, text: m, confirmButtonColor: '#2d9a4e' })
        .then(function (r) { if (r.isConfirmed && cb) cb(); });
    };
    window.mgError = function (t, m) {
      Swal.fire({ icon: 'error', title: t || 'Error', text: m, confirmButtonColor: '#b91c1c' });
    };
    window.mgConfirm = function (t, m, cb) {
      Swal.fire({
        icon: 'warning', title: t, text: m,
        showCancelButton: true,
        confirmButtonColor: '#2d9a4e', cancelButtonColor: '#64748b',
        confirmButtonText: 'Confirm', cancelButtonText: 'Cancel',
      }).then(function (r) { if (r.isConfirmed) cb(); });
    };
    window.mgLoading = function (t) {
      Swal.fire({ title: t || 'Processing…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
    };
  }

  /* ══════════════════════════════════════════════════════
     DATA-FILL progress bars (animate on load)
  ══════════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-fill]').forEach(function (el) {
      var target = el.getAttribute('data-fill');
      el.style.width = '0';
      requestAnimationFrame(function () {
        el.style.transition = 'width 0.9s cubic-bezier(.4,0,.2,1)';
        el.style.width = target;
      });
    });
  });

})();
</script>