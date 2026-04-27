<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = ''; $success = ''; $mode = $_GET['mode'] ?? 'login';
$hospitals = getAllHospitals();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token mismatch. Please try again.';
    } else {
        $mode = $_POST['mode'] ?? 'login';
        if ($mode === 'login') {
            $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? '';
            if (!$email || !$password) { $error = 'Please fill in all fields.'; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; }
            else {
                $result = loginUser($email, $password);
                if (isset($result['success'])) {
                    if (!empty($result['mfa_required'])) {
                        $showMfa = true;
                        $success = 'Verification code sent to your email.';
                    } else {
                        header('Location: dashboard.php');
                        exit;
                    }
                } else { $error = $result['error']; }
            }
        } elseif ($mode === 'verify_mfa') {
            $code = trim($_POST['mfa_code'] ?? '');
            if (!$code) { $error = 'Please enter the verification code.'; }
            else {
                $mfaResult = verifyMfaAndFinalize($code);
                if (isset($mfaResult['success'])) {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = $mfaResult['error'];
                    $showMfa = true; // Keep the overlay open on error
                }
            }
        } elseif ($mode === 'register') {
            $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; $confirm = $_POST['confirm_password'] ?? '';
            $pin = $_POST['pin'] ?? ''; $hospitalId = (int)($_POST['hospital_id'] ?? 0);
            if (!$name || !$email || !$password || !$confirm || !$pin || !$hospitalId) { $error = 'Please fill in all fields.'; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email address.'; }
            elseif (strlen($password) < 8) { $error = 'Password must be at least 8 characters.'; }
            elseif (strlen($pin) !== 4 || !is_numeric($pin)) { $error = 'PIN must be exactly 4 digits.'; }
            elseif ($password !== $confirm) { $error = 'Passwords do not match.'; }
            else {
                $result = registerUser($name, $email, $password, $hospitalId, $pin);
                if (isset($result['success'])) { $success = 'Account created! You can now sign in.'; $mode = 'login'; }
                else { $error = $result['error']; }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RUCU — Sign In</title>
  <meta name="description" content="Secure Digital Exchange System for Inter-Hospital Patient Transfer in Tanzania."/>
  <link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body style="background:var(--bg)">

<div class="auth-bg">

  <!-- ══ LEFT PANEL — Hero Image + Branding ══ -->
  <div class="auth-left">
    <img src="assets/tanzania_doctors_hero.png" class="active" alt="RUCU Medical 1"/>
    <img src="assets/tanzania_doctor_2.png" alt="RUCU Medical 2"/>
    <img src="assets/tanzania_doctor_3.png" alt="RUCU Medical 3"/>
    <img src="assets/tanzania_doctor_4.png" alt="RUCU Medical 4"/>
    <div class="auth-left-overlay"></div>
    <div class="auth-left-content">

      <!-- Badge -->
      <div class="auth-brand-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <span style="font-size:.75rem;font-weight:600;color:var(--txt)">Securing Tanzania's Medical Future</span>
      </div>

      <h1 style="font-size:2.5rem;font-weight:800;letter-spacing:-.04em;line-height:1.05;color:var(--txt);margin-bottom:14px">
        Advanced Healthcare,<br/>Secured for All.
      </h1>
      <p style="font-size:1rem;color:var(--muted);max-width:420px;line-height:1.7">
        RUCU provides an immutable, encrypted bridge for Tanzanian hospitals to share critical patient data with absolute confidence.
      </p>

      <div class="auth-stats">
        <div>
          <div class="auth-stat-num" style="color:var(--orange)">4+</div>
          <div class="auth-stat-lbl">Hospitals Connected</div>
        </div>
        <div style="width:1px;background:var(--border)"></div>
        <div>
          <div class="auth-stat-num">256</div>
          <div class="auth-stat-lbl">AES Bit Encryption</div>
        </div>
        <div style="width:1px;background:var(--border)"></div>
        <div>
          <div class="auth-stat-num" style="color:var(--teal)">100%</div>
          <div class="auth-stat-lbl">Audit Logged</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ══ RIGHT PANEL — Auth Form ══ -->
  <div class="auth-right" style="position: relative; overflow: hidden;">
    <!-- Animated DNA sketch -->
    <svg style="position: absolute; top: -80px; right: -120px; width: 500px; height: 500px; opacity: 0.05; pointer-events: none; animation: floatDNA 15s ease-in-out infinite;" viewBox="0 0 200 400" fill="none" stroke="var(--orange)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path stroke-dasharray="1000" stroke-dashoffset="1000" style="animation: dashDNA 20s linear infinite;" d="M50,0 Q150,50 50,100 T50,200 T50,300 T50,400" />
      <path stroke-dasharray="1000" stroke-dashoffset="1000" style="animation: dashDNA 20s linear infinite reverse;" d="M150,0 Q50,50 150,100 T150,200 T150,300 T150,400" />
      <g opacity="0.4">
        <line x1="75" y1="12" x2="125" y2="12" /><line x1="120" y1="38" x2="80" y2="38" />
        <line x1="135" y1="50" x2="65" y2="50" /><line x1="120" y1="62" x2="80" y2="62" />
        <line x1="75" y1="88" x2="125" y2="88" /><line x1="65" y1="100" x2="135" y2="100" />
        <line x1="120" y1="138" x2="80" y2="138" /><line x1="135" y1="150" x2="65" y2="150" />
        <line x1="75" y1="188" x2="125" y2="188" /><line x1="65" y1="200" x2="135" y2="200" />
        <line x1="120" y1="238" x2="80" y2="238" /><line x1="135" y1="250" x2="65" y2="250" />
        <line x1="75" y1="288" x2="125" y2="288" /><line x1="65" y1="300" x2="135" y2="300" />
        <line x1="120" y1="338" x2="80" y2="338" /><line x1="75" y1="388" x2="125" y2="388" />
      </g>
    </svg>

    <div class="auth-form-wrap" style="position: relative; z-index: 10;">

      <!-- Logo Mark -->
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:40px">
        <div style="width:44px;height:44px;background:var(--orange);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 20px rgba(249,115,22,0.3)">
          <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div>
          <div style="font-size:1.2rem;font-weight:800;color:var(--txt);letter-spacing:-.03em">RUCU System</div>
          <div style="font-size:.75rem;color:var(--muted);font-weight:500">Secure Inter-Hospital Exchange</div>
        </div>
      </div>

      <!-- Page title -->
      <h2 style="font-size:1.6rem;font-weight:800;margin-bottom:6px;letter-spacing:-.03em;color:var(--txt)"><?= $mode === 'login' ? 'Welcome back 👋' : 'Join the network' ?></h2>
      <p style="font-size:.88rem;color:var(--muted);margin-bottom:28px;line-height:1.5">
        <?= $mode === 'login' ? 'Sign in to access your hospital\'s secure portal.' : 'Register as a certified doctor to begin secure transfers.' ?>
      </p>

      <!-- Tab Switcher -->
      <div class="tab-bar" style="margin-bottom: 28px;">
        <button class="tab-btn <?= $mode==='login'?'active':'' ?>" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn <?= $mode==='register'?'active':'' ?>" id="tab-register" onclick="switchTab('register')">Create Account</button>
      </div>

      <!-- Alerts -->
      <?php if ($error): ?>
        <div class="alert alert-error">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <!-- ── LOGIN FORM ── -->
      <form id="form-login" method="POST" class="<?= $mode!=='login'?'hidden':'' ?>">
        <input type="hidden" name="mode" value="login"/>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"/>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-icon-wrap">
            <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <input type="email" name="email" class="form-input" placeholder="doctor@hospital.tz" required autocomplete="email"/>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon-wrap">
            <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password"/>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:.95rem;font-weight:700;border-radius:var(--r-xl);margin-top:4px">
          Sign In to Portal →
        </button>
      </form>

      <!-- ── REGISTER FORM ── -->
      <form id="form-register" method="POST" class="<?= $mode!=='register'?'hidden':'' ?>">
        <input type="hidden" name="mode" value="register"/>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"/>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:.72rem">Full Name</label>
            <div class="input-icon-wrap">
              <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <input type="text" name="name" class="form-input" placeholder="Dr. Amina" required style="font-size:.82rem;"/>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:.72rem">Email</label>
            <div class="input-icon-wrap">
              <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <input type="email" name="email" class="form-input" placeholder="doctor@hosp.tz" required style="font-size:.82rem;"/>
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label" style="font-size:.72rem">Assigned Hospital</label>
          <select name="hospital_id" class="form-select" required style="font-size:.82rem;">
            <option value="">— Select hospital —</option>
            <?php foreach ($hospitals as $h): ?>
              <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 90px;gap:14px;margin-bottom:16px">
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:.72rem">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Min 8 chars" minlength="8" required style="font-size:.82rem;"/>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label" style="font-size:.72rem">🔑 PIN</label>
            <input type="password" name="pin" class="form-input" style="text-align:center;letter-spacing:.3em;font-size:.82rem;" placeholder="••••" maxlength="4" pattern="\d{4}" required/>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label" style="font-size:.72rem">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required style="font-size:.82rem;"/>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:.95rem;font-weight:700;border-radius:var(--r-xl)">
          Create Doctor Account →
        </button>
      </form>

      <!-- Footer -->
      <p style="margin-top:32px;text-align:center;font-size:.78rem;color:var(--muted)">
        🔒 Tanzania Health Network · Certified Secure
      </p>
    </div>
  </div>

  <!-- ══ MFA OVERLAY ══ -->
  <?php if (!empty($showMfa)): ?>
  <div id="mfa-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.8);backdrop-filter:blur(10px);z-index:9999;display:flex;align-items:center;justify-content:center;animation:fadeIn 0.3s ease;">
    <div class="glass-card" style="width:100%;max-width:400px;padding:40px;text-align:center;position:relative;border:1px solid var(--orange);">
      <div style="width:60px;height:60px;background:var(--orange-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
        <svg width="30" height="30" fill="none" stroke="var(--orange)" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:8px;">Verify Identity</h2>
      <p style="font-size:.875rem;color:var(--muted);margin-bottom:32px;">Enter the 6-digit security code we sent to your certified email address.</p>

      <?php if (isset($_SESSION['debug_mfa_code'])): ?>
        <div style="background:rgba(20,184,166,0.1); border:1px dashed var(--teal); padding:10px; border-radius:var(--r-sm); margin-bottom:24px; color:var(--teal); font-size:.8rem; font-weight:700;">
          🛠️ DEBUG: Your code is <span style="font-size:1.1rem; letter-spacing:2px;"><?= $_SESSION['debug_mfa_code'] ?></span>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="mode" value="verify_mfa"/>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"/>
        
        <div class="form-group" style="margin-bottom:32px;">
          <input type="text" name="mfa_code" maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus
                 style="font-size:2rem;letter-spacing:0.4em;text-align:center;font-weight:700;background:var(--bg-hover);border:2px solid var(--orange);padding:15px;">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:16px;border-radius:var(--r-xl);">
          Complete Sign In →
        </button>
        
        <a href="login.php" style="display:block;margin-top:20px;font-size:.8rem;color:var(--muted);font-weight:600;">Cancel and Return</a>
      </form>
    </div>
  </div>
  <style>
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  </style>
  <?php endif; ?>
</div>

<script src="assets/js/app.js"></script>
<script>
function switchTab(tab) {
  const loginForm = document.getElementById('form-login');
  const regForm   = document.getElementById('form-register');
  const btnLogin  = document.getElementById('tab-login');
  const btnReg    = document.getElementById('tab-register');
  if (tab === 'login') {
    loginForm.classList.remove('hidden'); regForm.classList.add('hidden');
    btnLogin.classList.add('active'); btnReg.classList.remove('active');
  } else {
    regForm.classList.remove('hidden'); loginForm.classList.add('hidden');
    btnReg.classList.add('active'); btnLogin.classList.remove('active');
  }
}

// Image Slider
const images = document.querySelectorAll('.auth-left img');
let currentIdx = 0;
setInterval(() => {
  images[currentIdx].classList.remove('active');
  let nextIdx;
  do { nextIdx = Math.floor(Math.random() * images.length); } while(nextIdx === currentIdx);
  currentIdx = nextIdx;
  images[currentIdx].classList.add('active');
}, 5000);
</script>
</body>
</html>
