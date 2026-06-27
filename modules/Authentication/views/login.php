<?php
/**
 * SFAS — Login Page
 * File: modules/Authentication/views/login.php
 *
 * Elegant green farming theme.
 * Exact GuardReport JS pattern — proven to work.
 * Panels: login | forgot-step1 | forgot-step2 (OTP) | forgot-step3 (new pwd) | register | verify-reg
 */
require_once dirname(__DIR__, 3) . '/config/paths.php';
require_once dirname(__DIR__, 3) . '/config/config.php';

// Redirect already-logged-in users
if (!empty($_COOKIE['auth_token'])) {
    require_once dirname(__DIR__, 3) . '/helpers/JWTHandler.php';
    $jwt = new JWTHandler();
    if ($jwt->validateToken($_COOKIE['auth_token'])) {
        header('Location: ' . url('admin/dashboard')); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | SFAS Smart Farming</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Sora:wght@700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>window.BASE_URL = "<?= BASE_URL ?>";</script>
<style>
/* ── Reset ────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'DM Sans',system-ui,sans-serif;
  min-height:100vh;
  background:linear-gradient(135deg,#0d2b1a 0%,#1a5c2a 40%,#2d9a4e 75%,#3dbf68 100%);
  display:flex;align-items:center;justify-content:center;
  padding:1.5rem;
  position:relative;overflow:hidden;
}

/* Decorative background circles */
body::before{
  content:'';position:fixed;
  width:600px;height:600px;border-radius:50%;
  background:rgba(255,255,255,.04);
  top:-200px;right:-200px;pointer-events:none;
}
body::after{
  content:'';position:fixed;
  width:400px;height:400px;border-radius:50%;
  background:rgba(255,255,255,.04);
  bottom:-150px;left:-150px;pointer-events:none;
}

/* Floating leaf decorations */
.leaf{
  position:fixed;font-size:3rem;opacity:.08;pointer-events:none;
  animation:float 8s ease-in-out infinite;
}
.leaf:nth-child(1){top:5%;left:8%;animation-delay:0s}
.leaf:nth-child(2){top:15%;right:6%;font-size:4rem;animation-delay:2s}
.leaf:nth-child(3){bottom:10%;left:12%;font-size:5rem;animation-delay:4s}
.leaf:nth-child(4){bottom:20%;right:8%;font-size:2.5rem;animation-delay:6s}
@keyframes float{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-20px) rotate(5deg)}}

/* ── Card ─────────────────────────────────────────────── */
.login-card{
  background:#fff;
  border-radius:20px;
  box-shadow:0 25px 80px rgba(0,0,0,.25), 0 0 0 1px rgba(255,255,255,.1);
  width:100%;max-width:420px;
  position:relative;z-index:1;
  overflow:hidden;
  animation:slideUp .5s cubic-bezier(.4,0,.2,1);
}
@keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

/* ── Card Header ──────────────────────────────────────── */
.card-header{
  background:linear-gradient(135deg,#14421f 0%,#22773a 100%);
  padding:2rem 2rem 1.75rem;
  text-align:center;position:relative;overflow:hidden;
}
.card-header::after{
  content:'';position:absolute;
  width:200px;height:200px;border-radius:50%;
  background:rgba(255,255,255,.05);
  bottom:-80px;right:-60px;
}
.brand{
  display:inline-flex;align-items:center;gap:.6rem;
  font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:800;
  color:#fff;letter-spacing:1px;position:relative;z-index:1;
}
.brand-icon{
  width:40px;height:40px;
  background:rgba(255,255,255,.15);
  border-radius:50% 50% 50% 0;
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;
}
.card-header p{
  color:rgba(255,255,255,.72);font-size:.82rem;margin-top:.4rem;
  position:relative;z-index:1;
}
.card-header .sub{
  color:rgba(255,255,255,.5);font-size:.72rem;margin-top:.2rem;
  position:relative;z-index:1;
}

/* ── Card Body ────────────────────────────────────────── */
.card-body{padding:1.75rem 2rem 2rem}

/* ── Alert ────────────────────────────────────────────── */
.sfas-alert{
  display:none;align-items:flex-start;gap:.5rem;
  padding:.7rem .9rem;border-radius:8px;
  font-size:.83rem;margin-bottom:1rem;line-height:1.4;
}
.sfas-alert.show{display:flex}
.sfas-alert.danger {background:#fee2e2;color:#7f1d1d;border-left:3px solid #dc2626}
.sfas-alert.success{background:#dcfce7;color:#14532d;border-left:3px solid #16a34a}
.sfas-alert.info   {background:#dbeafe;color:#1e3a8a;border-left:3px solid #3b82f6}
.sfas-alert i{font-size:1rem;flex-shrink:0;margin-top:.05rem}

/* ── Tabs ─────────────────────────────────────────────── */
.tabs{display:flex;margin-bottom:1.25rem;border-bottom:2px solid #f0f0f0}
.tab{
  flex:1;padding:.65rem .5rem;text-align:center;
  font-size:.83rem;font-weight:600;cursor:pointer;color:#94a3b8;
  border-bottom:2px solid transparent;margin-bottom:-2px;
  transition:all .2s;
}
.tab.active{color:#2d9a4e;border-bottom-color:#2d9a4e}
.tab:hover:not(.active){color:#475569}

/* ── Form ─────────────────────────────────────────────── */
.form-group{margin-bottom:1rem}
.form-label{
  display:block;font-size:.8rem;font-weight:600;
  color:#374151;margin-bottom:.4rem;
}
.form-label .req{color:#dc2626;margin-left:2px}
.input-wrap{position:relative}
.input-wrap .icon{
  position:absolute;left:.85rem;top:50%;transform:translateY(-50%);
  color:#9ca3af;font-size:1rem;pointer-events:none;
}
.sfas-input{
  width:100%;padding:.65rem .9rem .65rem 2.5rem;
  border:1.5px solid #e5e7eb;border-radius:9px;
  font-size:.9rem;font-family:inherit;color:#111827;
  background:#fff;transition:all .2s;
  outline:none;
}
.sfas-input.no-icon{padding-left:.9rem}
.sfas-input:focus{border-color:#2d9a4e;box-shadow:0 0 0 3px rgba(45,154,78,.12)}
.sfas-input.error{border-color:#dc2626}
.pwd-toggle{
  position:absolute;right:.75rem;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  color:#9ca3af;font-size:1rem;padding:2px;
  transition:color .2s;
}
.pwd-toggle:hover{color:#374151}

/* OTP input special style */
.otp-input{
  text-align:center;font-size:2rem;font-weight:800;
  letter-spacing:12px;font-family:'Courier New',monospace;
  padding:.75rem !important;
}

/* ── Button ───────────────────────────────────────────── */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:.45rem;
  padding:.7rem 1.25rem;border-radius:9px;
  font-size:.875rem;font-weight:600;
  border:none;cursor:pointer;font-family:inherit;
  transition:all .2s;width:100%;
}
.btn:disabled{opacity:.6;cursor:not-allowed}
.btn-primary{background:#2d9a4e;color:#fff}
.btn-primary:hover:not(:disabled){background:#1a5c2a;transform:translateY(-1px)}
.btn-ghost{background:transparent;color:#2d9a4e;border:1.5px solid #2d9a4e}
.btn-ghost:hover:not(:disabled){background:#f0fdf4}
.btn+.btn{margin-top:.6rem}

/* ── Links ────────────────────────────────────────────── */
.link-row{text-align:center;margin-top:1rem;font-size:.8rem;color:#6b7280}
.link-row a,.text-link{color:#2d9a4e;font-weight:600;cursor:pointer;text-decoration:none}
.text-link:hover{text-decoration:underline}

/* ── Back link ────────────────────────────────────────── */
.back-link{
  display:inline-flex;align-items:center;gap:.3rem;
  font-size:.8rem;color:#2d9a4e;cursor:pointer;
  margin-bottom:1rem;font-weight:500;
}
.back-link:hover{text-decoration:underline}

/* ── Step indicator ───────────────────────────────────── */
.step-info{
  background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;
  padding:.65rem .9rem;font-size:.82rem;color:#14532d;
  margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;
}
.step-info i{color:#16a34a;flex-shrink:0}

/* ── Divider ──────────────────────────────────────────── */
.divider{
  display:flex;align-items:center;gap:.75rem;
  margin:1rem 0;color:#d1d5db;font-size:.75rem;
}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}

/* ── Demo credentials ─────────────────────────────────── */
.demo-box{
  background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;
  padding:.65rem .9rem;font-size:.78rem;color:#64748b;margin-top:1rem;
  text-align:center;
}
.demo-box strong{color:#374151}

/* ── Spinner ──────────────────────────────────────────── */
.spinner{
  display:inline-block;width:16px;height:16px;
  border:2px solid rgba(255,255,255,.4);
  border-top-color:#fff;border-radius:50%;
  animation:spin .6s linear infinite;flex-shrink:0;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Responsive ───────────────────────────────────────── */
@media(max-width:480px){
  .login-card{border-radius:16px}
  .card-body{padding:1.4rem 1.4rem 1.75rem}
  .card-header{padding:1.5rem 1.5rem 1.4rem}
}
</style>
</head>
<body>

<!-- Decorative leaves -->
<div class="leaf">🌿</div>
<div class="leaf">🌱</div>
<div class="leaf">🌾</div>
<div class="leaf">🍃</div>

<div class="login-card">

  <!-- Header -->
  <div class="card-header">
    <div class="brand">
      <div class="brand-icon"><i class="ri-plant-line"></i></div>
      SFAS
    </div>
    <p>Smart Farming Advisory System</p>
    <p class="sub">Nyagatare District · Anny Green Harvest · Rwanda</p>
  </div>

  <!-- Body -->
  <div class="card-body">

    <!-- Global alert (shared by all panels) -->
    <div class="sfas-alert" id="mainAlert">
      <i class="ri-error-warning-line" id="alertIcon"></i>
      <span id="alertMsg"></span>
    </div>

    <!-- ═══════════ PANEL: LOGIN ═══════════ -->
    <div id="panelLogin">

      <div class="tabs">
        <div class="tab active" onclick="showLogin()">Sign In</div>
        <div class="tab" onclick="showForgot1()">Forgot Password</div>
      </div>

      <div class="form-group">
        <label class="form-label">Email or Phone</label>
        <div class="input-wrap">
          <i class="ri-user-line icon"></i>
          <input type="text" id="loginId" class="sfas-input" placeholder="Enter your email or phone" autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <i class="ri-lock-line icon"></i>
          <input type="password" id="loginPwd" class="sfas-input" placeholder="Enter your password" autocomplete="current-password" style="padding-right:2.75rem">
          <button type="button" class="pwd-toggle" onclick="togglePwd('loginPwd','pwdEye')">
            <i class="ri-eye-line" id="pwdEye"></i>
          </button>
        </div>
      </div>

      <button class="btn btn-primary" id="loginBtn" onclick="doLogin()">
        <i class="ri-login-box-line"></i> Sign In
      </button>

      <div class="link-row">
        New farmer? <a onclick="showRegister()" class="text-link">Create account</a>
      </div>

      <div class="demo-box">
        <strong>Demo:</strong> admin@sfas.rw &nbsp;/&nbsp; Admin@1234
      </div>

    </div><!-- /panelLogin -->

    <!-- ═══════════ PANEL: REGISTER ═══════════ -->
    <div id="panelRegister" style="display:none">

      <span class="back-link" onclick="showLogin()">
        <i class="ri-arrow-left-line"></i> Back to sign in
      </span>
      <div style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:1.1rem">Create Farmer Account</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="form-group">
          <label class="form-label">First Name <span class="req">*</span></label>
          <input type="text" id="regFirst" class="sfas-input no-icon" placeholder="First name">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name <span class="req">*</span></label>
          <input type="text" id="regLast" class="sfas-input no-icon" placeholder="Last name">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="ri-mail-line icon"></i>
          <input type="email" id="regEmail" class="sfas-input" placeholder="farmer@example.com">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <div class="input-wrap">
          <i class="ri-phone-line icon"></i>
          <input type="text" id="regPhone" class="sfas-input" placeholder="+250 7XX XXX XXX">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">District</label>
        <select id="regDistrict" class="sfas-input no-icon" style="cursor:pointer">
          <option value="Nyagatare">Nyagatare</option>
          <option value="Gatsibo">Gatsibo</option>
          <option value="Kayonza">Kayonza</option>
          <option value="Musanze">Musanze</option>
          <option value="Huye">Huye</option>
          <option value="Kigali">Kigali</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="ri-lock-line icon"></i>
          <input type="password" id="regPwd" class="sfas-input" placeholder="Min 8 characters" style="padding-right:2.75rem">
          <button type="button" class="pwd-toggle" onclick="togglePwd('regPwd','regPwdEye')">
            <i class="ri-eye-line" id="regPwdEye"></i>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <div class="input-wrap">
          <i class="ri-lock-line icon"></i>
          <input type="password" id="regConfirm" class="sfas-input" placeholder="Repeat password">
        </div>
      </div>

      <button class="btn btn-primary" id="regBtn" onclick="doRegister()">
        <i class="ri-user-add-line"></i> Create Account
      </button>

      <p style="font-size:.75rem;color:#9ca3af;text-align:center;margin-top:.75rem">
        Your account needs administrator activation before you can log in.
      </p>

    </div><!-- /panelRegister -->

    <!-- ═══════════ PANEL: VERIFY REGISTRATION OTP ═══════════ -->
    <div id="panelVerifyReg" style="display:none">

      <div class="step-info">
        <i class="ri-mail-check-line"></i>
        We sent a 6-digit code to your email. Enter it below.
      </div>

      <div class="form-group">
        <label class="form-label" style="text-align:center;display:block">Verification Code</label>
        <input type="text" id="verifyRegOtp" class="sfas-input no-icon otp-input" maxlength="6" placeholder="000000">
      </div>

      <button class="btn btn-primary" id="verifyRegBtn" onclick="doVerifyReg()">
        <i class="ri-shield-check-line"></i> Verify Email
      </button>
      <button class="btn btn-ghost" onclick="doResendOtp()">
        <i class="ri-refresh-line"></i> Resend Code
      </button>

    </div><!-- /panelVerifyReg -->

    <!-- ═══════════ PANEL: FORGOT — STEP 1 (enter email) ═══════════ -->
    <div id="panelForgot1" style="display:none">

      <span class="back-link" onclick="showLogin()">
        <i class="ri-arrow-left-line"></i> Back to sign in
      </span>
      <div style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:.3rem">Reset Password</div>
      <p style="font-size:.82rem;color:#6b7280;margin-bottom:1.1rem">Enter your email — we'll send you a reset code.</p>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
          <i class="ri-mail-line icon"></i>
          <input type="email" id="fpEmail" class="sfas-input" placeholder="your@email.com">
        </div>
      </div>

      <button class="btn btn-primary" id="fpSendBtn" onclick="doForgot()">
        <i class="ri-send-plane-line"></i> Send Reset Code
      </button>

    </div><!-- /panelForgot1 -->

    <!-- ═══════════ PANEL: FORGOT — STEP 2 (enter OTP) ═══════════ -->
    <div id="panelForgot2" style="display:none">

      <span class="back-link" onclick="showForgot1()">
        <i class="ri-arrow-left-line"></i> Change email
      </span>
      <div class="step-info">
        <i class="ri-mail-check-line"></i>
        Code sent to <strong id="fpEmailDisplay"></strong>. Check your inbox.
      </div>

      <div class="form-group">
        <label class="form-label" style="text-align:center;display:block">6-Digit Reset Code</label>
        <input type="text" id="fpOtp" class="sfas-input no-icon otp-input" maxlength="6" placeholder="000000">
      </div>

      <button class="btn btn-primary" id="fpVerifyBtn" onclick="doVerifyOtp()">
        <i class="ri-key-line"></i> Verify Code
      </button>

    </div><!-- /panelForgot2 -->

    <!-- ═══════════ PANEL: FORGOT — STEP 3 (new password) ═══════════ -->
    <div id="panelForgot3" style="display:none">

      <div style="font-weight:700;font-size:1rem;color:#111827;margin-bottom:.3rem">Set New Password</div>
      <p style="font-size:.82rem;color:#6b7280;margin-bottom:1.1rem">Choose a strong password for your account.</p>

      <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-wrap">
          <i class="ri-lock-line icon"></i>
          <input type="password" id="fpPwd1" class="sfas-input" placeholder="Min 8 characters" style="padding-right:2.75rem">
          <button type="button" class="pwd-toggle" onclick="togglePwd('fpPwd1','fpPwdEye')">
            <i class="ri-eye-line" id="fpPwdEye"></i>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="input-wrap">
          <i class="ri-lock-line icon"></i>
          <input type="password" id="fpPwd2" class="sfas-input" placeholder="Repeat new password">
        </div>
      </div>

      <button class="btn btn-primary" id="fpResetBtn" onclick="doReset()">
        <i class="ri-lock-password-line"></i> Update Password
      </button>

    </div><!-- /panelForgot3 -->

  </div><!-- /card-body -->
</div><!-- /login-card -->

<script>
var BASE_URL  = window.BASE_URL;
var _regUid   = 0;
var _fpEmail  = '';

/* ── Alert helpers ────────────────────────────────────── */
function showAlert(msg, type) {
  type = type || 'danger';
  var a = document.getElementById('mainAlert');
  var icons = {danger:'ri-error-warning-line', success:'ri-check-circle-line', info:'ri-information-line'};
  document.getElementById('alertIcon').className = icons[type] || icons.danger;
  document.getElementById('alertMsg').textContent = msg;
  a.className = 'sfas-alert show ' + type;
}
function hideAlert() {
  document.getElementById('mainAlert').className = 'sfas-alert';
}

/* ── Panel switchers ──────────────────────────────────── */
var panels = ['panelLogin','panelRegister','panelVerifyReg','panelForgot1','panelForgot2','panelForgot3'];
function showPanel(id) {
  hideAlert();
  panels.forEach(function(p){ document.getElementById(p).style.display = p===id ? '' : 'none'; });
}
function showLogin()    { showPanel('panelLogin'); }
function showRegister() { showPanel('panelRegister'); }
function showForgot1()  { showPanel('panelForgot1'); }

/* ── Password toggle ──────────────────────────────────── */
function togglePwd(inputId, iconId) {
  var inp = document.getElementById(inputId);
  var ic  = document.getElementById(iconId);
  if (inp.type === 'password') { inp.type='text'; ic.className='ri-eye-off-line'; }
  else                          { inp.type='password'; ic.className='ri-eye-line'; }
}

/* ── Tab click ────────────────────────────────────────── */
function updateTabs(active) {
  document.querySelectorAll('.tab').forEach(function(t,i){
    t.classList.toggle('active', i===(active==='login'?0:1));
  });
}

/* ── Button loading ───────────────────────────────────── */
function setBtnLoading(id, isLoading, originalHtml) {
  var btn = document.getElementById(id);
  if (!btn) return;
  btn.disabled = isLoading;
  if (isLoading) btn.innerHTML = '<span class="spinner"></span> Please wait…';
  else if (originalHtml) btn.innerHTML = originalHtml;
}

/* ════════════════════════════════════════════════════════
   LOGIN
════════════════════════════════════════════════════════ */
function doLogin() {
  hideAlert();
  var id  = document.getElementById('loginId').value.trim();
  var pwd = document.getElementById('loginPwd').value;
  if (!id || !pwd) { showAlert('Please enter your email/phone and password.'); return; }
  setBtnLoading('loginBtn', true);
  fetch(BASE_URL + '/api/auth?action=login', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({identifier: id, password: pwd})
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      window.location.href = d.redirect || BASE_URL + '/admin/dashboard';
    } else {
      showAlert(d.message || 'Login failed. Please try again.');
      setBtnLoading('loginBtn', false, '<i class="ri-login-box-line"></i> Sign In');
    }
  })
  .catch(function() {
    showAlert('Network error. Check your connection and try again.');
    setBtnLoading('loginBtn', false, '<i class="ri-login-box-line"></i> Sign In');
  });
}

/* ════════════════════════════════════════════════════════
   REGISTER
════════════════════════════════════════════════════════ */
function doRegister() {
  hideAlert();
  var first   = document.getElementById('regFirst').value.trim();
  var last    = document.getElementById('regLast').value.trim();
  var email   = document.getElementById('regEmail').value.trim();
  var phone   = document.getElementById('regPhone').value.trim();
  var dist    = document.getElementById('regDistrict').value;
  var pwd     = document.getElementById('regPwd').value;
  var confirm = document.getElementById('regConfirm').value;

  if (!first || !last) { showAlert('First and last name are required.'); return; }
  if (!email)          { showAlert('Email address is required.'); return; }
  if (!pwd)            { showAlert('Password is required.'); return; }
  if (pwd.length < 8)  { showAlert('Password must be at least 8 characters.'); return; }
  if (pwd !== confirm) { showAlert('Passwords do not match.'); return; }

  setBtnLoading('regBtn', true);
  fetch(BASE_URL + '/api/auth?action=register', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      firstname: first, lastname: last, email: email,
      phone: phone || null, district: dist,
      password: pwd, confirm_password: confirm
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      _regUid = d.user_id;
      showPanel('panelVerifyReg');
      showAlert('Account created! Check your email for the verification code.', 'success');
    } else {
      showAlert(d.message || 'Registration failed.');
      setBtnLoading('regBtn', false, '<i class="ri-user-add-line"></i> Create Account');
    }
  })
  .catch(function() {
    showAlert('Network error. Please try again.');
    setBtnLoading('regBtn', false, '<i class="ri-user-add-line"></i> Create Account');
  });
}

/* ── Verify registration OTP ──────────────────────────── */
function doVerifyReg() {
  hideAlert();
  var otp = document.getElementById('verifyRegOtp').value.trim();
  if (!otp || otp.length !== 6) { showAlert('Please enter the 6-digit code.'); return; }
  if (!_regUid) { showAlert('Session expired. Please register again.'); return; }
  setBtnLoading('verifyRegBtn', true);
  fetch(BASE_URL + '/api/auth?action=verify-registration-otp', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({user_id: _regUid, otp: otp})
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      showAlert(d.message, 'success');
      setTimeout(function() { showLogin(); }, 3000);
    } else {
      showAlert(d.message || 'Invalid code.');
      setBtnLoading('verifyRegBtn', false, '<i class="ri-shield-check-line"></i> Verify Email');
    }
  })
  .catch(function() {
    showAlert('Network error.');
    setBtnLoading('verifyRegBtn', false, '<i class="ri-shield-check-line"></i> Verify Email');
  });
}

function doResendOtp() {
  if (!_regUid) { showAlert('Session expired.'); return; }
  fetch(BASE_URL + '/api/auth?action=resend-otp', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({user_id: _regUid})
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    showAlert(d.message, d.success ? 'info' : 'danger');
  })
  .catch(function() { showAlert('Network error.'); });
}

/* ════════════════════════════════════════════════════════
   FORGOT PASSWORD — 3 STEPS
════════════════════════════════════════════════════════ */

/* Step 1 — send OTP to email */
function doForgot() {
  hideAlert();
  _fpEmail = document.getElementById('fpEmail').value.trim();
  if (!_fpEmail) { showAlert('Please enter your email address.'); return; }
  setBtnLoading('fpSendBtn', true);
  fetch(BASE_URL + '/api/auth?action=forgot-password', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({email: _fpEmail})
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    // Always shows step 2 regardless (prevents email enumeration)
    document.getElementById('fpEmailDisplay').textContent = _fpEmail;
    showPanel('panelForgot2');
    showAlert(d.message, 'info');
  })
  .catch(function() {
    showAlert('Network error. Please try again.');
    setBtnLoading('fpSendBtn', false, '<i class="ri-send-plane-line"></i> Send Reset Code');
  });
}

/* Step 2 — verify OTP */
function doVerifyOtp() {
  hideAlert();
  var otp = document.getElementById('fpOtp').value.trim();
  if (!otp || otp.length !== 6) { showAlert('Please enter the 6-digit code.'); return; }
  setBtnLoading('fpVerifyBtn', true);
  fetch(BASE_URL + '/api/auth?action=verify-otp', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({email: _fpEmail, otp: otp})
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      showPanel('panelForgot3');
      hideAlert();
    } else {
      showAlert(d.message || 'Invalid or expired code.');
      setBtnLoading('fpVerifyBtn', false, '<i class="ri-key-line"></i> Verify Code');
    }
  })
  .catch(function() {
    showAlert('Network error.');
    setBtnLoading('fpVerifyBtn', false, '<i class="ri-key-line"></i> Verify Code');
  });
}

/* Step 3 — set new password */
function doReset() {
  hideAlert();
  var otp  = document.getElementById('fpOtp').value.trim();
  var pwd1 = document.getElementById('fpPwd1').value;
  var pwd2 = document.getElementById('fpPwd2').value;
  if (!pwd1)          { showAlert('Please enter a new password.'); return; }
  if (pwd1.length < 8){ showAlert('Password must be at least 8 characters.'); return; }
  if (pwd1 !== pwd2)  { showAlert('Passwords do not match.'); return; }
  setBtnLoading('fpResetBtn', true);
  fetch(BASE_URL + '/api/auth?action=reset-password', {
    method: 'POST', credentials: 'include',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      email: _fpEmail, otp: otp,
      password: pwd1, confirm_password: pwd2
    })
  })
  .then(function(r) { return r.json(); })
  .then(function(d) {
    if (d.success) {
      showAlert(d.message, 'success');
      setTimeout(function() { showLogin(); }, 2500);
    } else {
      showAlert(d.message || 'Reset failed. Please request a new code.');
      setBtnLoading('fpResetBtn', false, '<i class="ri-lock-password-line"></i> Update Password');
    }
  })
  .catch(function() {
    showAlert('Network error.');
    setBtnLoading('fpResetBtn', false, '<i class="ri-lock-password-line"></i> Update Password');
  });
}

/* ── Enter key on login ───────────────────────────────── */
document.addEventListener('keydown', function(e) {
  if (e.key !== 'Enter') return;
  var loginPanel = document.getElementById('panelLogin');
  if (loginPanel && loginPanel.style.display !== 'none') doLogin();
});

/* ── Auto-focus OTP fields ────────────────────────────── */
document.getElementById('verifyRegOtp').addEventListener('input', function(){
  if (this.value.length === 6) document.getElementById('verifyRegBtn').focus();
});
document.getElementById('fpOtp').addEventListener('input', function(){
  if (this.value.length === 6) document.getElementById('fpVerifyBtn').focus();
});
</script>
</body>
</html>