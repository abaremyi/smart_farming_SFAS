<?php
/**
 * SFAS — Market Prices View (FIXED)
 * File: modules/Advisory/views/market.php
 *
 * Fixed issues:
 *  1. price_rwf input was type="number" but sent as string — API now handles both
 *  2. Table update after add is now live (no page reload)
 *  3. Filter by crop now works client-side
 *  4. Delete removes row from DOM immediately
 */
$pageTitle   = 'Market Prices';
$currentPage = 'market';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 1) . '/models/AdvisoryModel.php';

$db     = Database::getConnection();
$model  = new AdvisoryModel($db);
$prices = $model->getAllPrices([]);
$crops  = $model->getAllCrops();

// Group latest price per crop for summary cards
$latestByCrop = [];
foreach ($prices as $p) {
    $cid = $p['crop_id'];
    if (!isset($latestByCrop[$cid])) $latestByCrop[$cid] = $p;
}

require get_layout('admin-head');
?>

<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Market Prices</span>
    </div>
    <h1 class="page-title">Market Prices</h1>
    <p class="page-sub">Crop prices from markets across Rwanda (RWF per kg)</p>
  </div>
  <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
  <button class="sfas-btn sfas-btn-primary" onclick="toggleForm()">
    <i class="ri-add-line"></i> Add Price
  </button>
  <?php endif; ?>
</div>

<!-- ── ADD PRICE FORM ─────────────────────────────────── -->
<?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
<div id="addForm" style="display:none;margin-bottom:1.5rem">
  <div class="sfas-card">
    <div class="sfas-card-header">
      <span class="sfas-card-title"><i class="ri-price-tag-3-line"></i> Record New Market Price</span>
      <button class="sfas-btn sfas-btn-ghost sfas-btn-sm" onclick="toggleForm()">
        <i class="ri-close-line"></i> Cancel
      </button>
    </div>
    <div class="sfas-card-body">

      <div id="addFormAlert" style="display:none"></div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.85rem">

        <div class="sfas-form-group">
          <label class="sfas-label">Crop <span class="req">*</span></label>
          <select id="fCropId" class="sfas-select">
            <option value="">— Select crop —</option>
            <?php foreach ($crops as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?>
              <?php if ($c['local_name']): ?>(<?= htmlspecialchars($c['local_name']) ?>)<?php endif; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="sfas-form-group">
          <label class="sfas-label">Market Name <span class="req">*</span></label>
          <input type="text" id="fMarket" class="sfas-input"
                 placeholder="e.g. Nyagatare Main Market"
                 list="marketSuggestions">
          <datalist id="marketSuggestions">
            <option value="Nyagatare Main Market">
            <option value="Kigali Kimironko Market">
            <option value="Musanze Market">
            <option value="Huye Market">
            <option value="Rubavu Market">
            <option value="Karongi Market">
            <option value="Gatsibo Market">
          </datalist>
        </div>

        <div class="sfas-form-group">
          <label class="sfas-label">District</label>
          <select id="fDistrict" class="sfas-select">
            <option value="">— Select —</option>
            <?php foreach(['Nyagatare','Gatsibo','Kayonza','Kirehe','Ngoma','Rwamagana','Musanze','Huye','Kigali','Rubavu','Karongi','Muhanga'] as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="sfas-form-group">
          <label class="sfas-label">Price (RWF) <span class="req">*</span></label>
          <input type="number" id="fPrice" class="sfas-input"
                 placeholder="e.g. 350" step="1" min="0">
        </div>

        <div class="sfas-form-group">
          <label class="sfas-label">Per Unit</label>
          <select id="fUnit" class="sfas-select">
            <option value="kg">per kg</option>
            <option value="tonne">per tonne</option>
            <option value="bag (50kg)">per bag (50kg)</option>
            <option value="crate">per crate</option>
            <option value="bunch">per bunch</option>
          </select>
        </div>

        <div class="sfas-form-group">
          <label class="sfas-label">Price Date</label>
          <input type="date" id="fDate" class="sfas-input" value="<?= date('Y-m-d') ?>">
        </div>

      </div>

      <div class="sfas-form-group" style="max-width:240px">
        <label class="sfas-label">Source</label>
        <select id="fSource" class="sfas-select">
          <option value="Field Survey">Field Survey</option>
          <option value="REMA Data">REMA Data</option>
          <option value="MINAGRI">MINAGRI</option>
          <option value="Trader Interview">Trader Interview</option>
          <option value="Market Observation">Market Observation</option>
        </select>
      </div>

      <div style="display:flex;gap:.6rem">
        <button class="sfas-btn sfas-btn-primary" id="savePriceBtn" onclick="savePrice()">
          <i class="ri-save-line"></i> Save Price Record
        </button>
        <button class="sfas-btn sfas-btn-ghost" onclick="toggleForm()">Cancel</button>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── LATEST PRICE SUMMARY CARDS ───────────────────────── -->
<?php if (!empty($latestByCrop)): ?>
<div style="margin-bottom:1.5rem">
  <div style="font-size:.82rem;font-weight:600;color:var(--text-muted);
    text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem">
    Latest Price Per Crop
  </div>
  <div class="sfas-grid sfas-grid-4">
    <?php foreach ($latestByCrop as $p): ?>
    <div class="sfas-card" style="border-top:3px solid var(--green-400)">
      <div class="sfas-card-body" style="padding:.85rem 1rem">
        <div style="font-weight:700;font-size:.9rem;color:var(--slate-800)">
          <?= htmlspecialchars($p['crop_name']) ?>
        </div>
        <?php if (!empty($p['local_name'])): ?>
        <div style="font-size:.73rem;color:var(--green-600);font-style:italic">
          <?= htmlspecialchars($p['local_name']) ?>
        </div>
        <?php endif; ?>
        <div style="font-size:1.3rem;font-weight:700;color:var(--green-700);
          font-family:'JetBrains Mono',monospace;margin:.4rem 0 .15rem">
          <?= number_format((float)$p['price_rwf'], 0) ?>
          <span style="font-size:.7rem;font-weight:400;color:var(--text-muted)">RWF/<?= htmlspecialchars($p['unit']) ?></span>
        </div>
        <div style="font-size:.73rem;color:var(--text-muted)">
          <i class="ri-store-2-line"></i> <?= htmlspecialchars($p['market']) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── FULL PRICE TABLE ───────────────────────────────── -->
<div class="sfas-card">
  <div class="sfas-card-header">
    <span class="sfas-card-title">
      <i class="ri-table-line"></i>
      All Price Records
      <span id="priceCount" style="font-size:.8rem;font-weight:400;color:var(--text-muted)">
        (<?= count($prices) ?>)
      </span>
    </span>
    <!-- Filter -->
    <div style="display:flex;gap:.5rem;align-items:center">
      <select id="cropFilter" class="sfas-select" style="width:160px;font-size:.8rem" onchange="filterPrices()">
        <option value="">All Crops</option>
        <?php foreach ($crops as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="sfas-table-wrap">
    <table class="sfas-table">
      <thead>
        <tr>
          <th>Crop</th>
          <th>Market</th>
          <th>District</th>
          <th style="text-align:right">Price (RWF)</th>
          <th>Unit</th>
          <th>Date</th>
          <th>Source</th>
          <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
          <th style="width:60px"></th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody id="pricesTableBody">
        <?php if (empty($prices)): ?>
        <tr id="emptyRow">
          <td colspan="8" style="text-align:center;padding:2.5rem;color:var(--text-muted)">
            <i class="ri-price-tag-line" style="font-size:2rem;display:block;opacity:.25;margin-bottom:.5rem"></i>
            No price records yet.
            <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
            <a href="#" onclick="toggleForm()" style="color:var(--green-600)">Add the first price record</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($prices as $p): ?>
        <tr id="priceRow<?= $p['id'] ?>" data-crop-id="<?= (int)$p['crop_id'] ?>">
          <td>
            <div style="font-weight:600"><?= htmlspecialchars($p['crop_name']) ?></div>
            <?php if ($p['local_name']): ?>
            <div style="font-size:.72rem;color:var(--green-600)"><?= htmlspecialchars($p['local_name']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p['market']) ?></td>
          <td><?= htmlspecialchars($p['district'] ?? '—') ?></td>
          <td style="text-align:right;font-family:'JetBrains Mono',monospace;
              font-weight:700;color:var(--green-700)">
            <?= number_format((float)$p['price_rwf'], 0) ?>
          </td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($p['unit']) ?></td>
          <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($p['price_date'])) ?></td>
          <td><span class="sfas-badge badge-slate" style="font-size:.68rem"><?= htmlspecialchars($p['source']) ?></span></td>
          <?php if ($isSuperAdmin || hasPermission($userPermissions,'market.manage')): ?>
          <td>
            <button class="sfas-btn sfas-btn-danger sfas-btn-sm"
              onclick="deletePrice(<?= (int)$p['id'] ?>)" title="Delete">
              <i class="ri-delete-bin-line"></i>
            </button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const B     = window.BASE_URL;
const canMgr= <?= ($isSuperAdmin||hasPermission($userPermissions,'market.manage'))?'true':'false' ?>;

/* ── Toggle add form ──────────────────────────────────── */
function toggleForm() {
  var f = document.getElementById('addForm');
  f.style.display = f.style.display==='none' ? 'block' : 'none';
  if (f.style.display==='block') {
    document.getElementById('addFormAlert').style.display='none';
    document.getElementById('fCropId').focus();
  }
}

/* ── Save price ───────────────────────────────────────── */
async function savePrice() {
  var alertEl = document.getElementById('addFormAlert');
  alertEl.style.display='none';

  var crop_id   = document.getElementById('fCropId').value;
  var market    = document.getElementById('fMarket').value.trim();
  var price_rwf = document.getElementById('fPrice').value.trim();
  var district  = document.getElementById('fDistrict').value;
  var unit      = document.getElementById('fUnit').value;
  var price_date= document.getElementById('fDate').value;
  var source    = document.getElementById('fSource').value;

  // Client-side validation
  if (!crop_id) {
    alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Please select a crop.</span></div>';
    alertEl.style.display='block'; return;
  }
  if (!market) {
    alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Market name is required.</span></div>';
    alertEl.style.display='block'; return;
  }
  if (!price_rwf || isNaN(parseFloat(price_rwf)) || parseFloat(price_rwf) < 0) {
    alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Please enter a valid price (numbers only).</span></div>';
    alertEl.style.display='block'; return;
  }

  var btn=document.getElementById('savePriceBtn');
  btn.disabled=true; btn.innerHTML='<span class="sfas-spinner"></span> Saving…';

  try {
    var r = await fetch(B+'/api/market?action=add', {
      method: 'POST', credentials: 'include',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        crop_id:    parseInt(crop_id),
        market:     market,
        district:   district || null,
        price_rwf:  parseFloat(price_rwf),
        unit:       unit,
        price_date: price_date || new Date().toISOString().slice(0,10),
        source:     source,
      })
    });
    var d = await r.json();

    if (d.success) {
      // Remove empty-state row if present
      var emptyRow = document.getElementById('emptyRow');
      if (emptyRow) emptyRow.remove();

      // Inject new row at TOP of table without page reload
      if (d.row) {
        var tbody = document.getElementById('pricesTableBody');
        var row   = d.row;
        var localName = row.local_name ? '<div style="font-size:.72rem;color:var(--green-600)">' + esc(row.local_name) + '</div>' : '';
        var delBtn = canMgr ? '<td><button class="sfas-btn sfas-btn-danger sfas-btn-sm" onclick="deletePrice(' + row.id + ')" title="Delete"><i class="ri-delete-bin-line"></i></button></td>' : '';
        var tr = document.createElement('tr');
        tr.id = 'priceRow' + row.id;
        tr.setAttribute('data-crop-id', row.crop_id);
        tr.innerHTML =
          '<td><div style="font-weight:600">' + esc(row.crop_name) + '</div>' + localName + '</td>' +
          '<td>' + esc(row.market) + '</td>' +
          '<td>' + esc(row.district || '—') + '</td>' +
          '<td style="text-align:right;font-family:\'JetBrains Mono\',monospace;font-weight:700;color:var(--green-700)">' +
            parseInt(row.price_rwf).toLocaleString() + '</td>' +
          '<td style="color:var(--text-muted)">' + esc(row.unit) + '</td>' +
          '<td style="color:var(--text-muted)">' + formatDate(row.price_date) + '</td>' +
          '<td><span class="sfas-badge badge-slate" style="font-size:.68rem">' + esc(row.source) + '</span></td>' +
          delBtn;
        tbody.insertBefore(tr, tbody.firstChild);

        // Update count
        var countEl = document.getElementById('priceCount');
        if (countEl) {
          var cur = parseInt(countEl.textContent.replace(/\D/g,'')) || 0;
          countEl.textContent = '(' + (cur+1) + ')';
        }
      }

      // Reset form fields
      document.getElementById('fCropId').value  = '';
      document.getElementById('fMarket').value  = '';
      document.getElementById('fPrice').value   = '';
      document.getElementById('fDistrict').value= '';

      // Show brief success in form
      alertEl.innerHTML='<div class="sfas-alert alert-success"><i class="ri-check-circle-line"></i><span>' + d.message + '</span></div>';
      alertEl.style.display='block';
      setTimeout(function(){ alertEl.style.display='none'; }, 3000);

    } else {
      alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>' + (d.message||'Save failed') + '</span></div>';
      alertEl.style.display='block';
    }
  } catch(err) {
    alertEl.innerHTML='<div class="sfas-alert alert-danger"><i class="ri-error-warning-line"></i><span>Network error — check your connection.</span></div>';
    alertEl.style.display='block';
  }
  btn.disabled=false; btn.innerHTML='<i class="ri-save-line"></i> Save Price Record';
}

/* ── Delete price ─────────────────────────────────────── */
function deletePrice(id) {
  mgConfirm('Delete Price Record?', 'This price entry will be permanently removed.', function() {
    fetch(B+'/api/market?action=delete', {
      method:'POST', credentials:'include',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id: id})
    })
    .then(function(r){ return r.json(); })
    .then(function(d) {
      if (d.success) {
        var row = document.getElementById('priceRow'+id);
        if (row) row.remove();
        var countEl = document.getElementById('priceCount');
        if (countEl) {
          var cur = parseInt(countEl.textContent.replace(/\D/g,'')) || 1;
          countEl.textContent = '(' + Math.max(0,cur-1) + ')';
        }
      } else {
        mgError('Error', d.message || 'Delete failed');
      }
    })
    .catch(function(){ mgError('Error','Network error.'); });
  });
}

/* ── Filter by crop ───────────────────────────────────── */
function filterPrices() {
  var cropId = document.getElementById('cropFilter').value;
  document.querySelectorAll('#pricesTableBody tr[data-crop-id]').forEach(function(row){
    row.style.display = (!cropId || row.dataset.cropId===cropId) ? '' : 'none';
  });
}

/* ── Helpers ──────────────────────────────────────────── */
function esc(s) {
  if (!s) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatDate(d) {
  if (!d) return '—';
  var dt = new Date(d + 'T00:00:00');
  return dt.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
}
</script>

<?php require get_layout('admin-scripts'); ?>