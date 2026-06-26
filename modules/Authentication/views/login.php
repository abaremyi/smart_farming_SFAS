<?php
/**
 * SFAS — Login View
 * File: modules/Authentication/views/login.php
 */
require_once dirname(__DIR__,3).'/config/paths.php';

// Already logged in? Redirect
if (isset($_COOKIE['auth_token'])) {
    header('Location: '.url('admin/dashboard')); exit;
}

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | SFAS — Smart Farming Advisory</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Sora:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/portal.css">
  <script>window.BASE_URL="<?= BASE_URL ?>";</script>
</head>
<body>

<div class="sfas-login-bg" id="loginBg">

  <!-- Decorative floating leaves -->
  <div style="position:absolute;top:8%;left:6%;opacity:.12;font-size:4rem;pointer-events:none">🌿</div>
  <div style="position:absolute;top:70%;right:4%;opacity:.10;font-size:5rem;pointer-events:none">🌱</div>
  <div style="position:absolute;bottom:5%;left:15%;opacity:.08;font-size:6rem;pointer-events:none">🌾</div>

  <div class="sfas-login-card" id="loginCard">

    <!-- Card Header -->
    <div class="sfas-login-header">
      <div class="brand">
        <div class="brand-leaf"><i class="ri-plant-line"></i></div>
        SFAS
      </div>
      <p>Smart Farming Advisory System</p>
      <p style="font-size:.78rem;opacity:.6;margin-top:.2rem;">Nyagatare District · Anny Green Harvest</p>
    </div>

    <!-- Login Body -->
    <div class="sfas-login-body" id="loginPanel">
      <div id="loginAlert" style="display:none"></div>

      <div class="sfas-form-group" style="margin-top:1.5rem">
        <label class="sfas-label" for="identifier">Email or Phone</label>
        <input type="text" id="identifier" class="sfas-input" placeholder="Enter your email or phone" autocomplete="username">
      </div>

      <div class="sfas-form-group" style="position:relative">
        <label class="sfas-label" for="password">Password</label>
        <input type="password" id="password" class="sfas-input" placeholder="Enter your password" autocomplete="current-password">
        <button type="button" id="togglePwd" onclick="togglePwd()"
          style="position:absolute;right:.75rem;bottom:.7rem;background:none;border:none;color:var(--text-muted);font-size:1rem;cursor:pointer">
          <i class="ri-eye-line" id="pwdIcon"></i>
        </button>
      </div>

      <div style="text-align:right;margin-top:-.5rem;margin-bottom:1rem">
        <a href="#" onclick="showForgot()" style="font-size:.83rem;color:var(--green-600);font-weight:500">Forgot password?</a>
      </div>

      <button class="sfas-btn sfas-btn-primary" id="loginBtn" onclick="doLogin()">
        <i class="ri-login-box-line"></i> Sign In
      </button>

      <p style="text-align:center;font-size:.8rem;color:var(--text-muted);margin-top:1.25rem">
        New farmer? <a href="#" style="color:var(--green-600);font-weight:600" onclick="showRegister()">Create account</a>
      </p>
    </div>

    <!-- Forgot Password Panel -->
    <div class="sfas-login-body" id="forgotPanel" style="display:none">
      <button onclick="showLogin()" style="background:none;border:none;color:var(--green-600);font-size:.83rem;cursor:pointer;padding:0;display:flex;align-items:center;gap:.3rem;margin-bottom:1rem">
        <i class="ri-arrow-left-line"></i> Back to login
      </button>
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.25rem">Reset Password</h3>
      <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1.25rem">Enter your email — we'll send you a 6-digit code.</p>
      <div id="forgotAlert" style="display:none"></div>
      <div class="sfas-form-group">
        <label class="sfas-label">Email Address</label>
        <input type="email" id="forgotEmail" class="sfas-input" placeholder="your@email.com">
      </div>
      <button class="sfas-btn sfas-btn-primary" onclick="doForgot()"><i class="ri-mail-send-line"></i> Send OTP</button>
    </div>

    <!-- OTP Panel -->
    <div class="sfas-login-body" id="otpPanel" style="display:none">
      <button onclick="showForgot()" style="background:none;border:none;color:var(--green-600);font-size:.83rem;cursor:pointer;padding:0;display:flex;align-items:center;gap:.3rem;margin-bottom:1rem">
        <i class="ri-arrow-left-line"></i> Change email
      </button>
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.25rem">Check Your Email</h3>
      <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1.25rem">Enter the 6-digit code sent to <strong id="otpEmailDisplay"></strong></p>
      <div id="otpAlert" style="display:none"></div>
      <div class="sfas-form-group">
        <label class="sfas-label">OTP Code</label>
        <input type="text" id="otpCode" class="sfas-input mono" maxlength="6" placeholder="000000" style="letter-spacing:8px;font-size:1.4rem;text-align:center">
      </div>
      <button class="sfas-btn sfas-btn-primary" onclick="doVerifyOtp()"><i class="ri-shield-check-line"></i> Verify Code</button>
    </div>

    <!-- New Password Panel -->
    <div class="sfas-login-body" id="newPwdPanel" style="display:none">
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.25rem">New Password</h3>
      <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1.25rem">Set a new password for your account.</p>
      <div id="newPwdAlert" style="display:none"></div>
      <div class="sfas-form-group">
        <label class="sfas-label">New Password</label>
        <input type="password" id="newPwd" class="sfas-input" placeholder="Min 8 characters">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Confirm Password</label>
        <input type="password" id="confirmPwd" class="sfas-input" placeholder="Repeat password">
      </div>
      <button class="sfas-btn sfas-btn-primary" onclick="doResetPwd()"><i class="ri-lock-line"></i> Update Password</button>
    </div>

    <!-- Register Panel -->
    <div class="sfas-login-body" id="registerPanel" style="display:none">
      <button onclick="showLogin()" style="background:none;border:none;color:var(--green-600);font-size:.83rem;cursor:pointer;padding:0;display:flex;align-items:center;gap:.3rem;margin-bottom:1rem">
        <i class="ri-arrow-left-line"></i> Back to login
      </button>
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem">Create Farmer Account</h3>
      <div id="regAlert" style="display:none"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="sfas-form-group">
          <label class="sfas-label">First Name <span class="req">*</span></label>
          <input type="text" id="regFirst" class="sfas-input" placeholder="Firstname">
        </div>
        <div class="sfas-form-group">
          <label class="sfas-label">Last Name <span class="req">*</span></label>
          <input type="text" id="regLast" class="sfas-input" placeholder="Lastname">
        </div>
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Email <span class="req">*</span></label>
        <input type="email" id="regEmail" class="sfas-input" placeholder="farmer@email.com">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Phone</label>
        <input type="text" id="regPhone" class="sfas-input" placeholder="+250 7XX XXX XXX">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">District</label>
        <select id="regDistrict" class="sfas-select">
          <option value="Nyagatare">Nyagatare</option>
          <option value="Musanze">Musanze</option>
          <option value="Kigali">Kigali</option>
          <option value="Huye">Huye</option>
          <option value="Rubavu">Rubavu</option>
        </select>
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Password <span class="req">*</span></label>
        <input type="password" id="regPwd" class="sfas-input" placeholder="Min 8 characters">
      </div>
      <div class="sfas-form-group">
        <label class="sfas-label">Confirm Password <span class="req">*</span></label>
        <input type="password" id="regConfirm" class="sfas-input" placeholder="Repeat password">
      </div>
      <button class="sfas-btn sfas-btn-primary" onclick="doRegister()">
        <i class="ri-user-add-line"></i> Create Account
      </button>
      <p style="font-size:.77rem;color:var(--text-muted);margin-top:.75rem;text-align:center">
        Your account will be activated by an administrator.
      </p>
    </div>

    <!-- Verify OTP after register -->
    <div class="sfas-login-body" id="verifyRegPanel" style="display:none">
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:.25rem">Verify Your Email</h3>
      <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:1.25rem">We sent a 6-digit code to your email.</p>
      <div id="verifyRegAlert" style="display:none"></div>
      <div class="sfas-form-group">
        <label class="sfas-label">OTP Code</label>
        <input type="text" id="verifyRegOtp" class="sfas-input mono" maxlength="6" placeholder="000000" style="letter-spacing:8px;font-size:1.4rem;text-align:center">
      </div>
      <button class="sfas-btn sfas-btn-primary" onclick="doVerifyReg()"><i class="ri-shield-check-line"></i> Verify Email</button>
      <p style="font-size:.8rem;text-align:center;margin-top:.75rem">
        <a href="#" onclick="doResendOtp()" style="color:var(--green-600)">Resend code</a>
      </p>
    </div>

  </div><!-- /sfas-login-card -->
</div><!-- /sfas-login-bg -->

<script>
const B = window.BASE_URL;
let _regUserId = null;

/* ── Panel nav ────────────────────────────────────────── */
const panels = ['loginPanel','forgotPanel','otpPanel','newPwdPanel','registerPanel','verifyRegPanel'];
function showPanel(id){ panels.forEach(p=>{ document.getElementById(p).style.display=(p===id?'':'none'); }); }
function showLogin()    { showPanel('loginPanel'); }
function showForgot()   { showPanel('forgotPanel'); }
function showRegister() { showPanel('registerPanel'); }

/* ── Alert helpers ────────────────────────────────────── */
function setAlert(id,msg,type='danger'){
  const el=document.getElementById(id);
  el.innerHTML=`<div class="sfas-alert alert-${type}" style="margin-bottom:.75rem">
    <i class="ri-${type==='success'?'check-circle':'error-warning'}-line"></i><span>${msg}</span></div>`;
  el.style.display='block';
}
function clearAlert(id){ document.getElementById(id).style.display='none'; }

/* ── Password toggle ──────────────────────────────────── */
function togglePwd(){
  const i=document.getElementById('password');
  const icon=document.getElementById('pwdIcon');
  i.type=i.type==='password'?'text':'password';
  icon.className=i.type==='password'?'ri-eye-line':'ri-eye-off-line';
}

/* ── Login ────────────────────────────────────────────── */
async function doLogin(){
  clearAlert('loginAlert');
  const id=document.getElementById('identifier').value.trim();
  const pwd=document.getElementById('password').value;
  if(!id||!pwd){setAlert('loginAlert','Please enter your email/phone and password.');return;}
  const btn=document.getElementById('loginBtn');
  btn.disabled=true;btn.innerHTML='<span class="sfas-spinner"></span> Signing in…';
  try{
    const r=await fetch(B+'/api/auth?action=login',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({identifier:id,password:pwd})
    });
    const d=await r.json();
    if(d.success){ window.location.href=d.redirect||B+'/admin/dashboard'; }
    else{ setAlert('loginAlert',d.message); }
  }catch(e){ setAlert('loginAlert','Network error. Please try again.'); }
  finally{ btn.disabled=false;btn.innerHTML='<i class="ri-login-box-line"></i> Sign In'; }
}

/* ── Forgot password ──────────────────────────────────── */
async function doForgot(){
  clearAlert('forgotAlert');
  const email=document.getElementById('forgotEmail').value.trim();
  if(!email){setAlert('forgotAlert','Please enter your email.');return;}
  const r=await fetch(B+'/api/auth?action=forgot-password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})});
  const d=await r.json();
  if(d.success){
    document.getElementById('otpEmailDisplay').textContent=email;
    showPanel('otpPanel');
  } else { setAlert('forgotAlert',d.message); }
}

async function doVerifyOtp(){
  clearAlert('otpAlert');
  const email=document.getElementById('forgotEmail').value.trim();
  const otp=document.getElementById('otpCode').value.trim();
  const r=await fetch(B+'/api/auth?action=verify-otp',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,otp})});
  const d=await r.json();
  if(d.success){ showPanel('newPwdPanel'); }
  else { setAlert('otpAlert',d.message); }
}

async function doResetPwd(){
  clearAlert('newPwdAlert');
  const email=document.getElementById('forgotEmail').value.trim();
  const otp=document.getElementById('otpCode').value.trim();
  const password=document.getElementById('newPwd').value;
  const confirm_password=document.getElementById('confirmPwd').value;
  const r=await fetch(B+'/api/auth?action=reset-password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email,otp,password,confirm_password})});
  const d=await r.json();
  if(d.success){ Swal.fire({icon:'success',title:'Password Updated',text:d.message}).then(()=>showLogin()); }
  else { setAlert('newPwdAlert',d.message); }
}

/* ── Register ─────────────────────────────────────────── */
async function doRegister(){
  clearAlert('regAlert');
  const body={
    firstname:document.getElementById('regFirst').value.trim(),
    lastname:document.getElementById('regLast').value.trim(),
    email:document.getElementById('regEmail').value.trim(),
    phone:document.getElementById('regPhone').value.trim(),
    district:document.getElementById('regDistrict').value,
    password:document.getElementById('regPwd').value,
    confirm_password:document.getElementById('regConfirm').value,
  };
  if(!body.firstname||!body.lastname||!body.email||!body.password)
    {setAlert('regAlert','Please fill all required fields.');return;}
  const r=await fetch(B+'/api/auth?action=register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
  const d=await r.json();
  if(d.success){
    _regUserId=d.user_id;
    showPanel('verifyRegPanel');
  } else { setAlert('regAlert',d.message); }
}

async function doVerifyReg(){
  clearAlert('verifyRegAlert');
  const otp=document.getElementById('verifyRegOtp').value.trim();
  const r=await fetch(B+'/api/auth?action=verify-registration-otp',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:_regUserId,otp})});
  const d=await r.json();
  if(d.success){
    Swal.fire({icon:'success',title:'Email Verified!',text:d.message,confirmButtonColor:'#2d9a4e'}).then(()=>showLogin());
  } else { setAlert('verifyRegAlert',d.message); }
}

async function doResendOtp(){
  const r=await fetch(B+'/api/auth?action=resend-otp',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:_regUserId})});
  const d=await r.json();
  Swal.fire({icon:d.success?'success':'error',title:d.success?'Code Sent':'Error',text:d.message});
}

/* ── Enter key ────────────────────────────────────────── */
document.getElementById('password').addEventListener('keydown',e=>{if(e.key==='Enter')doLogin();});
</script>
</body>
</html>
