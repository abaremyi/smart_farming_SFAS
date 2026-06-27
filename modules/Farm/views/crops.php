<?php
/**
 * SFAS — Crops Reference View
 * File: modules/Farm/views/crops.php
 */
$pageTitle   = 'Crop Reference';
$currentPage = 'farms';
require_once dirname(__DIR__,3).'/helpers/admin-base.php';
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/FarmModel.php';

$db    = Database::getConnection();
$model = new FarmModel($db);
$crops = $model->getAllCrops();

$catColors = [
    'Cereal'     => 'badge-amber',
    'Legume'     => 'badge-green',
    'Vegetable'  => 'badge-blue',
    'Root'       => 'badge-amber',
    'Fruit'      => 'badge-green',
    'Cash Crop'  => 'badge-red',
    'Forage'     => 'badge-slate',
];

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><a href="<?= url('admin/farms') ?>">Farms</a>
      <span>/</span><span>Crop Reference</span>
    </div>
    <h1 class="page-title">Crop Reference Guide</h1>
    <p class="page-sub">All crops registered in SFAS for Rwanda</p>
  </div>
</div>

<div class="sfas-grid sfas-grid-3">
  <?php foreach ($crops as $crop): ?>
  <div class="sfas-card" style="transition:var(--transition)">
    <div class="sfas-card-header" style="background:var(--green-50)">
      <div>
        <span class="sfas-badge <?= $catColors[$crop['category']] ?? 'badge-slate' ?>" style="margin-bottom:.4rem">
          <?= htmlspecialchars($crop['category'] ?? '—') ?>
        </span>
        <div style="font-weight:700;font-size:1rem;color:var(--slate-800)"><?= htmlspecialchars($crop['name']) ?></div>
        <?php if ($crop['local_name']): ?>
        <div style="font-size:.8rem;color:var(--green-600);font-style:italic"><?= htmlspecialchars($crop['local_name']) ?></div>
        <?php endif; ?>
      </div>
      <i class="ri-seedling-line" style="font-size:2rem;color:var(--green-300)"></i>
    </div>
    <div class="sfas-card-body">
      <?php if ($crop['description']): ?>
      <p style="font-size:.85rem;color:var(--text-muted);line-height:1.6;margin-bottom:.85rem">
        <?= htmlspecialchars($crop['description']) ?>
      </p>
      <?php endif; ?>
      <div style="display:flex;flex-direction:column;gap:.4rem">
        <?php if ($crop['growing_season']): ?>
        <div style="font-size:.8rem;display:flex;gap:.5rem;align-items:center">
          <i class="ri-calendar-line" style="color:var(--green-500)"></i>
          <span style="color:var(--text-muted)">Season:</span>
          <strong><?= htmlspecialchars($crop['growing_season']) ?></strong>
        </div>
        <?php endif; ?>
        <?php if ($crop['min_rainfall_mm'] && $crop['max_rainfall_mm']): ?>
        <div style="font-size:.8rem;display:flex;gap:.5rem;align-items:center">
          <i class="ri-drop-line" style="color:var(--blue-500)"></i>
          <span style="color:var(--text-muted)">Rainfall:</span>
          <strong><?= $crop['min_rainfall_mm'] ?>–<?= $crop['max_rainfall_mm'] ?> mm</strong>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require get_layout('admin-scripts'); ?>
