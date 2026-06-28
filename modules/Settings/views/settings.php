<?php
/**
 * SFAS — System Settings
 * File: modules/Settings/views/settings.php
 *
 * Admin-only (requires settings.manage permission).
 * Sections:
 *   1. AI Provider — pick Gemini / Groq / OpenRouter / Claude + get key guide
 *   2. Email (SMTP) — test send, Gmail app-password guide
 *   3. System Diagnostics — PHP, DB, upload dir, weather API status
 *   4. Database Stats — live row counts
 *   5. Quick Actions — shortcuts to common admin tasks
 */
$pageTitle          = 'System Settings';
$currentPage        = 'settings';
$requiredPermission = 'settings.manage';
require_once dirname(__DIR__, 3) . '/helpers/admin-base.php';
require_once dirname(__DIR__, 3) . '/config/database.php';

$db = Database::getConnection();

/* ── Live system checks ─────────────────────────────── */
$uploadOk      = is_dir(UPLOADS_PATH)          && is_writable(UPLOADS_PATH);
$uploadUsersOk = is_dir(UPLOADS_PATH.'/users') && is_writable(UPLOADS_PATH.'/users');
$smtpSet       = defined('SMTP_USER')          && !empty(SMTP_USER);
$aiKeySet      = defined('ANTHROPIC_API_KEY')  && !empty(ANTHROPIC_API_KEY);

// Detect which provider the current key belongs to
$aiKeyPrefix  = '';
$detectedProv = 'none';
if ($aiKeySet) {
    $k = ANTHROPIC_API_KEY;
    $aiKeyPrefix = substr($k, 0, 8) . '…';
    if (str_starts_with($k, 'AIza'))   $detectedProv = 'gemini';
    elseif (str_starts_with($k, 'gsk_'))    $detectedProv = 'groq';
    elseif (str_starts_with($k, 'sk-or-'))  $detectedProv = 'openrouter';
    elseif (str_starts_with($k, 'sk-ant-')) $detectedProv = 'anthropic';
    else                                     $detectedProv = 'unknown';
}

/* ── DB stats ───────────────────────────────────────── */
$stats = [
    'users'    => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'farmers'  => (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id=3 AND account_status='active'")->fetchColumn(),
    'farms'    => (int)$db->query("SELECT COUNT(*) FROM farms")->fetchColumn(),
    'tips'     => (int)$db->query("SELECT COUNT(*) FROM advisory_tips WHERE is_active=1")->fetchColumn(),
    'alerts'   => (int)$db->query("SELECT COUNT(*) FROM pest_alerts WHERE is_active=1")->fetchColumn(),
    'prices'   => (int)$db->query("SELECT COUNT(*) FROM market_prices")->fetchColumn(),
    'ai_chats' => (int)$db->query("SELECT COUNT(*) FROM ai_chat_logs WHERE role='user'")->fetchColumn(),
    'pending'  => (int)$db->query("SELECT COUNT(*) FROM users WHERE account_status='pending'")->fetchColumn(),
];

require get_layout('admin-head');
?>

<style>
/* ── Settings layout ────────────────────────────────── */
.set-section { margin-bottom: 2rem; }
.set-title {
  font-family: 'Sora', sans-serif; font-size: .95rem; font-weight: 700;
  color: var(--green-800); display: flex; align-items: center; gap: .45rem;
  padding-bottom: .55rem; border-bottom: 2px solid var(--green-100);
  margin-bottom: 1rem;
}
.set-row {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 1rem; flex-wrap: wrap; padding: .9rem 1.15rem;
  border-bottom: 1px solid var(--border);
}
.set-row:last-child { border-bottom: none; }
.set-lbl { font-weight: 600; font-size: .875rem; color: var(--slate-800); }
.set-sub { font-size: .78rem; color: var(--text-muted); margin-top: .18rem; line-height: 1.5; }

/* Status pills */
.spill {
  display: inline-flex; align-items: center; gap: .35rem;
  font-size: .8rem; font-weight: 600;
  padding: .28rem .75rem; border-radius: var(--radius-full);
  white-space: nowrap; flex-shrink: 0;
}
.spill-green  { background: var(--green-100); color: var(--green-700); }
.spill-red    { background: var(--red-100);   color: var(--red-600);   }
.spill-amber  { background: var(--amber-100); color: #92400e;          }
.spill-blue   { background: var(--blue-100);  color: #1d4ed8;          }

/* AI provider cards */
.ai-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: .85rem; margin-top: .75rem;
}
.ai-card {
  border: 2px solid var(--border); border-radius: var(--radius-md);
  padding: 1rem 1.1rem; cursor: pointer; transition: var(--transition);
  background: var(--white); position: relative;
}
.ai-card:hover { border-color: var(--green-400); box-shadow: var(--shadow-sm); }
.ai-card.active-provider {
  border-color: var(--green-500); background: var(--green-50);
  box-shadow: 0 0 0 3px rgba(45,154,78,.12);
}
.ai-badge {
  position: absolute; top: .6rem; right: .6rem;
  font-size: .63rem; font-weight: 700; padding: .15rem .5rem;
  border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: .04em;
}
.ai-name  { font-weight: 700; font-size: .9rem; color: var(--slate-800); margin-bottom: .3rem; }
.ai-detail{ font-size: .76rem; color: var(--text-muted); line-height: 1.55; }
.ai-link  {
  display: inline-flex; align-items: center; gap: .2rem;
  font-size: .74rem; font-weight: 600; color: var(--green-600);
  text-decoration: none; margin-top: .5rem;
}
.ai-link:hover { text-decoration: underline; }
.ai-active-tick {
  display: none; position: absolute; bottom: .6rem; right: .6rem;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--green-500); color: white;
  align-items: center; justify-content: center; font-size: .75rem;
}
.ai-card.active-provider .ai-active-tick { display: flex; }

/* Code block */
.code-blk {
  background: #0f172a; border-radius: var(--radius-md);
  padding: 1rem 1.25rem; font-family: 'JetBrains Mono', monospace;
  font-size: .78rem; line-height: 2.1; overflow-x: auto; color: #e2e8f0;
}
.code-blk .c { color: #475569; }
.code-blk .k { color: #34d399; }
.code-blk .v { color: #fbbf24; }
.code-blk .u { color: #7dd3fc; }
</style>

<!-- Page Header -->
<div class="sfas-page-header">
  <div>
    <div class="sfas-breadcrumb">
      <a href="<?= url('admin/dashboard') ?>"><i class="ri-home-4-line"></i> Home</a>
      <span>/</span><span>Settings</span>
    </div>
    <h1 class="page-title">System Settings</h1>
    <p class="page-sub">Configure AI provider, email, and monitor system health</p>
  </div>
</div>

<div style="max-width:900px">

<!-- ══════════════════════════════════════════════════════
     SECTION 1 — AI ASSISTANT
══════════════════════════════════════════════════════ -->
<div class="set-section">
  <div class="set-title"><i class="ri-robot-2-line"></i> AI Farming Assistant</div>

  <div class="sfas-card">
    <div class="sfas-card-body">

      <p style="font-size:.875rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.25rem">
        SFAS supports <strong>four AI providers</strong> — three are <strong>completely free</strong>.
        The system auto-detects which provider you're using based on your API key prefix in <code>.env</code>.
        No code changes required — just swap the key value.
      </p>

      <!-- Current provider status -->
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div style="font-size:.83rem;font-weight:600;color:var(--slate-700)">Currently active:</div>
        <?php if ($aiKeySet && $detectedProv !== 'unknown'): ?>
          <?php $pNames=['gemini'=>'Google Gemini','groq'=>'Groq / Llama 3','openrouter'=>'OpenRouter','anthropic'=>'Anthropic Claude']; ?>
          <span class="spill spill-green">
            <i class="ri-checkbox-circle-line"></i>
            <?= $pNames[$detectedProv] ?? $detectedProv ?>
            (<?= $aiKeyPrefix ?>)
          </span>
        <?php elseif ($aiKeySet): ?>
          <span class="spill spill-amber"><i class="ri-alert-line"></i> Key set but provider unrecognised (<?= $aiKeyPrefix ?>)</span>
        <?php else: ?>
          <span class="spill spill-amber"><i class="ri-close-circle-line"></i> No key set — AI shows fallback message</span>
        <?php endif; ?>
        <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="testAI()">
          <i class="ri-test-tube-line"></i> Test AI Now
        </button>
      </div>

      <!-- 4 provider cards -->
      <div class="ai-grid">

        <!-- Google Gemini -->
        <div class="ai-card <?= $detectedProv==='gemini'?'active-provider':'' ?>" id="aiCardGemini">
          <div class="ai-active-tick"><i class="ri-check-line"></i></div>
          <span class="ai-badge" style="background:#dbeafe;color:#1d4ed8">100% FREE</span>
          <div class="ai-name">🌐 Google Gemini Flash</div>
          <div class="ai-detail">
            <strong>1 million tokens/day FREE</strong><br>
            No credit card. No limits for SFAS scale.<br>
            Great multilingual (English + Kinyarwanda).<br>
            Key starts with: <code>AIza...</code>
          </div>
          <a href="https://aistudio.google.com/app/apikey" target="_blank" class="ai-link">
            <i class="ri-external-link-line"></i> Get free key
          </a>
        </div>

        <!-- Groq -->
        <div class="ai-card <?= $detectedProv==='groq'?'active-provider':'' ?>" id="aiCardGroq">
          <div class="ai-active-tick"><i class="ri-check-line"></i></div>
          <span class="ai-badge" style="background:#dcfce7;color:#15803d">FREE TIER</span>
          <div class="ai-name">⚡ Groq (Llama 3 8B)</div>
          <div class="ai-detail">
            <strong>Ultra-fast</strong> — fastest of all options.<br>
            30 requests/min free. No card needed.<br>
            Runs Meta's Llama 3 open-source model.<br>
            Key starts with: <code>gsk_...</code>
          </div>
          <a href="https://console.groq.com" target="_blank" class="ai-link">
            <i class="ri-external-link-line"></i> Get free key
          </a>
        </div>

        <!-- OpenRouter -->
        <div class="ai-card <?= $detectedProv==='openrouter'?'active-provider':'' ?>" id="aiCardOR">
          <div class="ai-active-tick"><i class="ri-check-line"></i></div>
          <span class="ai-badge" style="background:#fef3c7;color:#92400e">FREE MODELS</span>
          <div class="ai-name">🔀 OpenRouter</div>
          <div class="ai-detail">
            One API — access 100+ models.<br>
            Free: Llama 3, Mistral, Gemma, Phi-3.<br>
            Uses model: <code>llama-3-8b-instruct:free</code><br>
            Key starts with: <code>sk-or-...</code>
          </div>
          <a href="https://openrouter.ai/keys" target="_blank" class="ai-link">
            <i class="ri-external-link-line"></i> Get free key
          </a>
        </div>

        <!-- Anthropic Claude -->
        <div class="ai-card <?= $detectedProv==='anthropic'?'active-provider':'' ?>" id="aiCardClaude">
          <div class="ai-active-tick"><i class="ri-check-line"></i></div>
          <span class="ai-badge" style="background:#fce7f3;color:#9d174d">$5 FREE</span>
          <div class="ai-name">🤖 Anthropic Claude</div>
          <div class="ai-detail">
            <strong>$5 free credit</strong> ≈ 500 farming chats.<br>
            Best quality responses of all options.<br>
            Currently used in chatApi.php by default.<br>
            Key starts with: <code>sk-ant-...</code>
          </div>
          <a href="https://console.anthropic.com" target="_blank" class="ai-link">
            <i class="ri-external-link-line"></i> Get $5 free
          </a>
        </div>

      </div><!-- /ai-grid -->

      <!-- .env reference box -->
      <div style="margin-top:1.25rem">
        <div style="font-size:.82rem;font-weight:600;color:var(--slate-700);
          display:flex;align-items:center;gap:.35rem;margin-bottom:.6rem">
          <i class="ri-file-code-line"></i>
          How to switch provider — edit <code>.env</code> in the project root:
        </div>

        <div class="code-blk">
<span class="c"># ── OPTION 1: Google Gemini Flash ── RECOMMENDED (completely free) ──</span><br>
<span class="k">ANTHROPIC_API_KEY</span>=<span class="v">AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXX</span><br>
<span class="u"># → aistudio.google.com/app/apikey  (sign in with Google → Get API Key)</span><br><br>
<span class="c"># ── OPTION 2: Groq / Llama 3 ── ultra-fast, free tier ───────────────</span><br>
<span class="k">ANTHROPIC_API_KEY</span>=<span class="v">gsk_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</span><br>
<span class="u"># → console.groq.com  (Create Account → API Keys → Create)</span><br><br>
<span class="c"># ── OPTION 3: OpenRouter ── many free models ─────────────────────────</span><br>
<span class="k">ANTHROPIC_API_KEY</span>=<span class="v">sk-or-v1-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</span><br>
<span class="u"># → openrouter.ai/keys  (Register free → Create Key)</span><br><br>
<span class="c"># ── OPTION 4: Anthropic Claude ── $5 free credit ─────────────────────</span><br>
<span class="k">ANTHROPIC_API_KEY</span>=<span class="v">sk-ant-api03-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX</span><br>
<span class="u"># → console.anthropic.com  (New account gets $5 free = ~500 chats)</span>
        </div>

        <div class="sfas-alert alert-info" style="margin-top:.85rem">
          <i class="ri-information-line"></i>
          <span>
            After editing <code>.env</code>, <strong>restart Apache</strong> in XAMPP Control Panel
            so PHP reloads the environment. The chatApi.php automatically detects the provider
            from the key prefix — <strong>no other code changes needed</strong>.
          </span>
        </div>
      </div>

    </div>
  </div>
</div><!-- /AI section -->


<!-- ══════════════════════════════════════════════════════
     SECTION 2 — EMAIL (SMTP)
══════════════════════════════════════════════════════ -->
<div class="set-section">
  <div class="set-title"><i class="ri-mail-send-line"></i> Email Configuration</div>
  <div class="sfas-card">

    <div class="set-row">
      <div>
        <div class="set-lbl">SMTP Status</div>
        <div class="set-sub">Used for OTP emails on registration and password reset</div>
      </div>
      <?php if ($smtpSet): ?>
        <span class="spill spill-green"><i class="ri-checkbox-circle-line"></i> Configured</span>
      <?php else: ?>
        <span class="spill spill-amber"><i class="ri-alert-line"></i> Not set in .env</span>
      <?php endif; ?>
    </div>

    <?php if ($smtpSet): ?>
    <div class="set-row">
      <div>
        <div class="set-lbl">SMTP Account</div>
        <div class="set-sub"><?= htmlspecialchars(SMTP_USER) ?> via <?= htmlspecialchars(SMTP_HOST) ?></div>
      </div>
      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="testEmail()">
        <i class="ri-send-plane-line"></i> Send Test Email to Myself
      </button>
    </div>
    <?php endif; ?>

    <div class="set-row">
      <div>
        <div class="set-lbl">Gmail App Password Setup</div>
        <div class="set-sub">
          1. Go to Google Account → Security → 2-Step Verification<br>
          2. Scroll to "App passwords" → Select "Mail" → Generate<br>
          3. Copy the 16-character password into <code>.env</code> as <code>SMTP_PASS</code>
        </div>
      </div>
      <a href="https://myaccount.google.com/apppasswords" target="_blank"
         class="sfas-btn sfas-btn-ghost sfas-btn-sm" style="flex-shrink:0">
        <i class="ri-external-link-line"></i> Google Account
      </a>
    </div>

    <div class="set-row">
      <div style="width:100%">
        <div class="set-lbl" style="margin-bottom:.5rem">
          <i class="ri-file-code-line"></i> .env email settings:
        </div>
        <div class="code-blk" style="font-size:.76rem;line-height:1.9">
<span class="k">SMTP_HOST</span>=<span class="v">smtp.gmail.com</span><br>
<span class="k">SMTP_USER</span>=<span class="v">your-gmail@gmail.com</span><br>
<span class="k">SMTP_PASS</span>=<span class="v">xxxx xxxx xxxx xxxx</span>   <span class="c"># 16-char app password (spaces OK)</span><br>
<span class="k">SMTP_FROM_NAME</span>=<span class="v">SFAS Smart Farming</span>
        </div>
      </div>
    </div>

  </div>
</div><!-- /Email section -->


<!-- ══════════════════════════════════════════════════════
     SECTION 3 — SYSTEM DIAGNOSTICS
══════════════════════════════════════════════════════ -->
<div class="set-section">
  <div class="set-title"><i class="ri-heart-pulse-line"></i> System Diagnostics</div>
  <div class="sfas-card">

    <div class="set-row">
      <div><div class="set-lbl">Database</div>
        <div class="set-sub">
          <?= htmlspecialchars($_ENV['DB_NAME'] ?? 'sfas_db') ?>
          on <?= htmlspecialchars($_ENV['DB_HOST'] ?? 'localhost') ?>:<?= htmlspecialchars($_ENV['DB_PORT'] ?? '3308') ?>
        </div>
      </div>
      <span class="spill spill-green"><i class="ri-database-2-line"></i> Connected</span>
    </div>

    <div class="set-row">
      <div><div class="set-lbl">PHP Version</div><div class="set-sub">Minimum required: PHP 8.0</div></div>
      <span class="spill <?= version_compare(PHP_VERSION,'8.0','>=') ? 'spill-green' : 'spill-red' ?>">
        <i class="ri-code-s-slash-line"></i> PHP <?= PHP_VERSION ?>
      </span>
    </div>

    <div class="set-row">
      <div>
        <div class="set-lbl">uploads/ Directory</div>
        <div class="set-sub"><?= htmlspecialchars(UPLOADS_PATH) ?></div>
      </div>
      <?php if ($uploadOk): ?>
        <span class="spill spill-green"><i class="ri-folder-open-line"></i> Writable</span>
      <?php else: ?>
        <span class="spill spill-red"><i class="ri-close-circle-line"></i> Not writable</span>
      <?php endif; ?>
    </div>

    <div class="set-row">
      <div>
        <div class="set-lbl">uploads/users/ (Profile photos)</div>
        <div class="set-sub">
          <?php if (!$uploadUsersOk && $uploadOk): ?>
            Will be created automatically on first photo upload.
          <?php else: ?>
            <?= htmlspecialchars(UPLOADS_PATH) ?>/users/
          <?php endif; ?>
        </div>
      </div>
      <?php if ($uploadUsersOk): ?>
        <span class="spill spill-green"><i class="ri-image-line"></i> Ready</span>
      <?php elseif ($uploadOk): ?>
        <span class="spill spill-amber"><i class="ri-folder-add-line"></i> Auto-created on use</span>
      <?php else: ?>
        <span class="spill spill-red"><i class="ri-close-circle-line"></i> Parent not writable</span>
      <?php endif; ?>
    </div>

    <?php if (!$uploadOk): ?>
    <div class="set-row">
      <div style="width:100%">
        <div class="set-lbl" style="color:var(--red-600)">⚠ Fix upload directory permissions</div>
        <div class="code-blk" style="margin-top:.5rem;font-size:.76rem;line-height:1.8">
<span class="c"># Run this in your SFAS project root (Git Bash or terminal):</span><br>
<span class="k">chmod</span> -R 755 uploads/<br>
<span class="c"># Or on Windows — right-click uploads/ → Properties → Security → give write permission</span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="set-row">
      <div><div class="set-lbl">Weather API (Open-Meteo)</div><div class="set-sub">Free, no key needed — fetches live Rwanda weather</div></div>
      <span class="spill spill-green"><i class="ri-cloud-line"></i> Always Free</span>
    </div>

    <div class="set-row">
      <div><div class="set-lbl">cURL Extension</div><div class="set-sub">Required for AI API calls to external providers</div></div>
      <span class="spill <?= function_exists('curl_init') ? 'spill-green' : 'spill-red' ?>">
        <?= function_exists('curl_init') ? '<i class="ri-checkbox-circle-line"></i> Enabled' : '<i class="ri-close-circle-line"></i> Disabled — enable in php.ini' ?>
      </span>
    </div>

  </div>
</div><!-- /Diagnostics section -->


<!-- ══════════════════════════════════════════════════════
     SECTION 4 — DATABASE STATS
══════════════════════════════════════════════════════ -->
<div class="set-section">
  <div class="set-title"><i class="ri-database-2-line"></i> Database Statistics</div>

  <div class="sfas-stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
    <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
      <div class="stat-icon"><i class="ri-group-line"></i></div>
      <div class="stat-val"><?= $stats['users'] ?></div>
      <div class="stat-label">Total Users</div>
      <?php if ($stats['pending']>0): ?>
      <div class="stat-change"><i class="ri-time-line"></i> <?= $stats['pending'] ?> pending</div>
      <?php endif; ?>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-50)">
      <div class="stat-icon"><i class="ri-user-heart-line"></i></div>
      <div class="stat-val"><?= $stats['farmers'] ?></div>
      <div class="stat-label">Active Farmers</div>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
      <div class="stat-icon"><i class="ri-map-2-line"></i></div>
      <div class="stat-val"><?= $stats['farms'] ?></div>
      <div class="stat-label">Registered Farms</div>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--green-500);--stat-bg:var(--green-100)">
      <div class="stat-icon"><i class="ri-lightbulb-line"></i></div>
      <div class="stat-val"><?= $stats['tips'] ?></div>
      <div class="stat-label">Advisory Tips</div>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--red-600);--stat-bg:var(--red-100)">
      <div class="stat-icon"><i class="ri-alarm-warning-line"></i></div>
      <div class="stat-val"><?= $stats['alerts'] ?></div>
      <div class="stat-label">Active Alerts</div>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--blue-500);--stat-bg:var(--blue-100)">
      <div class="stat-icon"><i class="ri-robot-2-line"></i></div>
      <div class="stat-val"><?= $stats['ai_chats'] ?></div>
      <div class="stat-label">AI Questions</div>
    </div>
    <div class="sfas-stat" style="--stat-accent:var(--gold-600);--stat-bg:var(--gold-100)">
      <div class="stat-icon"><i class="ri-price-tag-3-line"></i></div>
      <div class="stat-val"><?= $stats['prices'] ?></div>
      <div class="stat-label">Price Records</div>
    </div>
  </div>
</div><!-- /Stats section -->


<!-- ══════════════════════════════════════════════════════
     SECTION 5 — QUICK ACTIONS
══════════════════════════════════════════════════════ -->
<div class="set-section">
  <div class="set-title"><i class="ri-flashlight-line"></i> Quick Actions</div>
  <div class="sfas-card">
    <div class="sfas-card-body" style="display:flex;gap:.65rem;flex-wrap:wrap">

      <a href="<?= url('admin/users-management') ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
        <i class="ri-group-line"></i> Manage Users
        <?php if ($stats['pending']>0): ?>
          <span class="sfas-badge badge-amber" style="font-size:.65rem;padding:.1rem .4rem"><?= $stats['pending'] ?> pending</span>
        <?php endif; ?>
      </a>

      <a href="<?= url('admin/roles-permissions-management') ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
        <i class="ri-shield-check-line"></i> Roles & Permissions
      </a>

      <a href="<?= url('admin/market') ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
        <i class="ri-price-tag-3-line"></i> Update Market Prices
      </a>

      <a href="<?= url('admin/advisory/create') ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
        <i class="ri-lightbulb-line"></i> Add Advisory Tip
      </a>

      <a href="<?= url('admin/alerts') ?>" class="sfas-btn sfas-btn-outline sfas-btn-sm">
        <i class="ri-alarm-warning-line"></i> Manage Pest Alerts
      </a>

      <button class="sfas-btn sfas-btn-outline sfas-btn-sm" onclick="clearWeatherCache()">
        <i class="ri-refresh-line"></i> Clear Weather Cache
      </button>

    </div>
  </div>
</div>

</div><!-- /max-width wrapper -->

<script>
const B = window.BASE_URL;

/* ── Test AI ──────────────────────────────────────────── */
async function testAI() {
  mgLoading('Testing AI connection…');
  try {
    var r = await fetch(B + '/api/ai/chat', {
      method: 'POST', 
      credentials: 'include',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        message: 'Reply with exactly one sentence: "SFAS AI is working correctly!"',
        history: []
      })
    });
    var d = await r.json();
    Swal.close();
    if (d.success) {
      mgSuccess(
        '✅ AI Connected! (' + (d.provider || 'unknown') + ')',
        '"' + d.reply.slice(0, 120) + '"'
      );
    } else {
      mgError(
        'AI Not Working',
        (d.message || 'Unknown error') +
        '\n\nCheck ANTHROPIC_API_KEY in .env and restart Apache after editing.'
      );
    }
  } catch(e) {
    Swal.close();
    mgError('Network Error', 'Could not reach /api/ai/chat — check that the route exists in index.php');
  }
}

/* ── Test Email ───────────────────────────────────────── */
async function testEmail() {
  mgLoading('Sending test email to <?= addslashes($currentUser->email ?? '') ?>…');
  try {
    var r = await fetch(B + '/api/settings?action=test-email', {
      method: 'POST', 
      credentials: 'include',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({})
    });
    var d = await r.json();
    Swal.close();
    if (d.success) mgSuccess('✅ Email Sent!', d.message);
    else mgError('Email Failed', d.message);
  } catch(e) {
    Swal.close();
    mgError('Error', 'Network error reaching /api/settings');
  }
}

/* ── Clear weather cache ──────────────────────────────── */
function clearWeatherCache() {
  mgConfirm('Clear Weather Cache?',
    'Forces a fresh fetch from Open-Meteo on the next weather page visit.',
    async function() {
      try {
        var r = await fetch(B + '/api/settings?action=clear-weather-cache', {
          method: 'POST', 
          credentials: 'include',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({})
        });
        var d = await r.json();
        if (d.success) mgSuccess('Cache Cleared', d.message);
        else mgError('Error', d.message);
      } catch(e) { mgError('Error', 'Network error.'); }
    }
  );
}
</script>

<?php require get_layout('admin-scripts'); ?>