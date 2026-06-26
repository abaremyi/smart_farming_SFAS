<?php
/**
 * SFAS — Admin Navigation
 * File: layouts/admin-nav.php
 */
$navItems = [
  ['label'=>'Dashboard',  'url'=>url('admin/dashboard'),  'icon'=>'ri-dashboard-3-line',  'page'=>'dashboard',  'perm'=>''],
  ['label'=>'Farms',      'url'=>url('admin/farms'),       'icon'=>'ri-map-2-line',        'page'=>'farms',      'perm'=>'farms.view',
    'submenu'=>[
      ['label'=>'All Farms',  'url'=>url('admin/farms'),         'icon'=>'ri-list-check-2'],
      ['label'=>'Add Farm',   'url'=>url('admin/farms/create'),  'icon'=>'ri-map-pin-add-line'],
      ['label'=>'Crops',      'url'=>url('admin/crops'),         'icon'=>'ri-seedling-line'],
    ]
  ],
  ['label'=>'Advisory',   'url'=>url('admin/advisory'),   'icon'=>'ri-lightbulb-line',    'page'=>'advisory',   'perm'=>'advisory.view',
    'submenu'=>[
      ['label'=>'All Tips',    'url'=>url('admin/advisory'),        'icon'=>'ri-article-line'],
      ['label'=>'Add Tip',     'url'=>url('admin/advisory/create'), 'icon'=>'ri-quill-pen-line'],
      ['label'=>'Pest Alerts', 'url'=>url('admin/alerts'),          'icon'=>'ri-alarm-warning-line'],
    ]
  ],
  ['label'=>'Market',     'url'=>url('admin/market'),     'icon'=>'ri-price-tag-3-line',  'page'=>'market',     'perm'=>'market.manage'],
  ['label'=>'AI Chat',    'url'=>url('admin/ai-assistant'),'icon'=>'ri-robot-2-line',     'page'=>'ai',         'perm'=>'ai.use'],
  ['label'=>'Weather',    'url'=>url('admin/weather'),    'icon'=>'ri-cloud-windy-line',   'page'=>'weather',    'perm'=>'weather.view'],
  ['label'=>'Reports',    'url'=>url('admin/reports'),    'icon'=>'ri-bar-chart-box-line', 'page'=>'reports',    'perm'=>'reports.view'],
  ['label'=>'Users',      'url'=>'#',                     'icon'=>'ri-team-line',          'page'=>'users',      'perm'=>'users.view',
    'submenu'=>[
      ['label'=>'All Users',  'url'=>url('admin/users-management'), 'icon'=>'ri-group-line'],
      ['label'=>'Add User',   'url'=>url('admin/users-add-user'),   'icon'=>'ri-user-add-line'],
      ['label'=>'Roles',      'url'=>url('admin/roles-permissions-management'), 'icon'=>'ri-shield-check-line'],
    ]
  ],
];
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
?>

<div class="sfas-nav-overlay" id="sfasNavOverlay" onclick="closeMobileNav()"></div>

<nav class="sfas-nav">
  <a class="sfas-nav-logo" href="<?= url('admin/dashboard') ?>">
    <div class="logo-leaf"><i class="ri-plant-line"></i></div>
    SFAS
  </a>

  <div class="sfas-nav-links" id="sfasNavLinks">
    <?php foreach($navItems as $item):
      if(!empty($item['perm']) && !$isSuperAdmin && !hasPermission($userPermissions,$item['perm'])) continue;
      $isActive = (isset($currentPage) && $currentPage === $item['page']);
      $hasSub   = !empty($item['submenu']);
      if($hasSub && !$isActive){
        foreach($item['submenu'] as $sub){
          $sp = parse_url($sub['url'],PHP_URL_PATH)??'';
          if($sp && str_contains($currentUrl,$sp)){ $isActive=true; break; }
        }
      }
    ?>
      <?php if($hasSub): ?>
        <div class="sfas-nav-item<?= $isActive?' open':'' ?>" data-has-submenu>
          <button class="sfas-nav-link<?= $isActive?' active':'' ?>">
            <i class="<?= $item['icon'] ?>"></i>
            <?= htmlspecialchars($item['label']) ?>
            <i class="ri-arrow-down-s-line sub-caret"></i>
          </button>
          <div class="sfas-submenu">
            <?php foreach($item['submenu'] as $sub):
              $sp=parse_url($sub['url'],PHP_URL_PATH)??'';
              $sa=$sp && str_contains($currentUrl,$sp);
            ?>
              <a href="<?= $sub['url'] ?>" class="<?= $sa?'sub-active':'' ?>">
                <i class="<?= $sub['icon'] ?>"></i><?= htmlspecialchars($sub['label']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= $item['url'] ?>" class="sfas-nav-link<?= $isActive?' active':'' ?>">
          <i class="<?= $item['icon'] ?>"></i><?= htmlspecialchars($item['label']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="sfas-nav-right">
    <div id="sfasAvatarWrap" style="position:relative">
      <div class="sfas-nav-avatar" id="sfasAvatarBtn" title="<?= $userFullName ?>">
        <?php if(!empty($userPhoto)): ?>
          <img src="<?= upload_url('users/'.$userPhoto) ?>" alt="">
        <?php else: ?><?= $userInitials ?><?php endif; ?>
      </div>
      <div class="sfas-avatar-dropdown" id="sfasAvatarMenu">
        <div class="dd-header">
          <div class="dd-name"><?= $userFullName ?></div>
          <div class="dd-email"><?= htmlspecialchars($currentUser->email??'') ?></div>
          <div class="dd-role"><i class="ri-seedling-line"></i> <?= htmlspecialchars($currentUser->role_name??'User') ?></div>
        </div>
        <a href="<?= url('admin/profile') ?>"><i class="ri-user-line"></i> My Profile</a>
        <a href="<?= url('admin/profile-settings') ?>"><i class="ri-settings-4-line"></i> Settings</a>
        <div class="dd-divider"></div>
        <a href="#" class="danger" id="sfasLogoutBtn"><i class="ri-logout-box-r-line"></i> Sign Out</a>
      </div>
    </div>
    <button class="sfas-hamburger" id="sfasHamburger" aria-label="Toggle nav" aria-expanded="false">
      <i class="ri-menu-3-line"></i>
    </button>
  </div>
</nav>

<main class="sfas-main" id="sfasMain">

<script>
(function(){
  /* ── Submenus ─────────────────────────────────────────── */
  function closeAllSubs(){
    document.querySelectorAll('.sfas-nav-item[data-has-submenu]').forEach(i=>i.classList.remove('open'));
  }
  document.querySelectorAll('.sfas-nav-item[data-has-submenu]').forEach(function(item){
    var btn=item.querySelector('button.sfas-nav-link');
    if(!btn)return;
    btn.addEventListener('click',function(e){
      e.stopPropagation();
      var was=item.classList.contains('open');
      closeAllSubs();
      if(!was)item.classList.add('open');
    });
  });
  document.addEventListener('click',function(e){
    if(!e.target.closest('.sfas-nav-item[data-has-submenu]') && window.innerWidth>=901) closeAllSubs();
  });

  /* ── Mobile nav ───────────────────────────────────────── */
  var mob=false;
  var hm=document.getElementById('sfasHamburger');
  var nl=document.getElementById('sfasNavLinks');
  var ov=document.getElementById('sfasNavOverlay');
  function openMob(){mob=true;nl.classList.add('mobile-open');ov.classList.add('visible');
    hm.setAttribute('aria-expanded','true');hm.querySelector('i').className='ri-close-line';
    document.body.style.overflow='hidden';}
  function closeMob(){mob=false;nl.classList.remove('mobile-open');ov.classList.remove('visible');
    hm.setAttribute('aria-expanded','false');hm.querySelector('i').className='ri-menu-3-line';
    document.body.style.overflow='';}
  window.closeMobileNav=closeMob;
  hm.addEventListener('click',function(){mob?closeMob():openMob();});

  /* ── Avatar ───────────────────────────────────────────── */
  var ab=document.getElementById('sfasAvatarBtn');
  var aw=document.getElementById('sfasAvatarWrap');
  var am=document.getElementById('sfasAvatarMenu');
  var avOpen=false;
  ab.addEventListener('click',function(e){
    e.stopPropagation();
    avOpen=!avOpen;
    am.classList.toggle('open',avOpen);
  });
  document.addEventListener('click',function(e){
    if(avOpen&&!aw.contains(e.target)){avOpen=false;am.classList.remove('open');}
  });

  /* ── Logout ───────────────────────────────────────────── */
  document.getElementById('sfasLogoutBtn').addEventListener('click',function(e){
    e.preventDefault();
    if(typeof Swal==='undefined'){
      if(confirm('Sign out of SFAS?'))
        fetch(window.BASE_URL+'/api/auth?action=logout',{method:'POST',credentials:'include'})
          .finally(()=>{ window.location.href=window.BASE_URL+'/logout'; });
      return;
    }
    Swal.fire({icon:'question',title:'Sign Out?',text:'You will be signed out of SFAS.',
      showCancelButton:true,confirmButtonColor:'#b91c1c',cancelButtonColor:'#64748b',
      confirmButtonText:'Yes, sign out',cancelButtonText:'Cancel'})
    .then(function(r){
      if(r.isConfirmed)
        fetch(window.BASE_URL+'/api/auth?action=logout',{method:'POST',credentials:'include'})
          .finally(()=>{ window.location.href=window.BASE_URL+'/logout'; });
    });
  });

  /* ── Heartbeat ────────────────────────────────────────── */
  setInterval(function(){
    fetch(window.BASE_URL+'/api/auth?action=heartbeat',{method:'POST',credentials:'include'}).catch(()=>{});
  },10*60*1000);

  /* ── SWal global helpers ──────────────────────────────── */
  if(typeof Swal!=='undefined'){
    window.mgSuccess=(t,m,cb)=>Swal.fire({icon:'success',title:t,text:m,confirmButtonColor:'#2d9a4e'}).then(r=>{if(r.isConfirmed&&cb)cb();});
    window.mgError=(t,m)=>Swal.fire({icon:'error',title:t||'Error',text:m,confirmButtonColor:'#b91c1c'});
    window.mgConfirm=(t,m,cb)=>Swal.fire({icon:'warning',title:t,text:m,showCancelButton:true,
      confirmButtonColor:'#2d9a4e',cancelButtonColor:'#64748b',confirmButtonText:'Confirm',cancelButtonText:'Cancel'})
      .then(r=>{if(r.isConfirmed)cb();});
    window.mgLoading=t=>Swal.fire({title:t||'Processing…',allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
  }
})();
</script>
