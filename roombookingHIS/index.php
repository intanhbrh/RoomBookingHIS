<?php
// index.php - HIS Room Booking System Login Page
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location admin_login.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_school_email':
            $error = 'Please use your school email.';
            break;
        case 'google_auth_failed':
            $error = 'Google sign-in failed. Please try again.';
            break;
        case 'auth_error':
            $error = 'Authentication error. Please contact your administrator.';
            break;
        case 'session_expired':
            $error = 'Your session has expired. Please sign in again.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/img/help-logo.png">


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HIS Room Booking — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --red:        #C0001A;
    --red-dark:   #8B0013;
    --red-light:  #E8001F;
    --cream:      #FDF6F0;
    --white:      #FFFFFF;
    --charcoal:   #1A1A1A;
    --grey:       #6B6B6B;
    --grey-light: #E8E2DC;
    --shadow:     rgba(192,0,26,0.18);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--cream);
    min-height: 100vh;
    display: flex;
    overflow: hidden;
  }

  /* ── LEFT PANEL ─────────────────────────────── */
  .left-panel {
    width: 52%;
    background: var(--red);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 52px 56px;
    overflow: hidden;
  }

  /* Decorative geometric background */
  .left-panel::before {
    content: '';
    position: absolute;
    top: -120px; right: -120px;
    width: 480px; height: 480px;
    border-radius: 50%;
    border: 80px solid rgba(255,255,255,0.06);
    pointer-events: none;
  }
  .left-panel::after {
    content: '';
    position: absolute;
    bottom: -80px; left: -60px;
    width: 340px; height: 340px;
    border-radius: 50%;
    border: 60px solid rgba(255,255,255,0.05);
    pointer-events: none;
  }

  .geo-line {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    pointer-events: none;
    opacity: 0.07;
    background-image:
      repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.5) 0px,
        rgba(255,255,255,0.5) 1px,
        transparent 1px,
        transparent 60px
      );
  }

  .brand {
    position: relative;
    z-index: 2;
  }

  .brand-badge {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 100px;
    padding: 8px 18px 8px 8px;
    backdrop-filter: blur(8px);
    margin-bottom: 48px;
  }

  .brand-badge .dot {
    width: 32px; height: 32px;
    background: var(--white);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
  }

  .brand-badge .dot span {
    font-family: 'Playfair Display', serif;
    font-weight: 900;
    font-size: 14px;
    color: var(--red);
    line-height: 1;
  }

  .brand-badge p {
    color: rgba(255,255,255,0.9);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .hero-text {
    position: relative;
    z-index: 2;
  }

  .hero-text h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(42px, 4.5vw, 68px);
    font-weight: 900;
    color: var(--white);
    line-height: 1.05;
    margin-bottom: 24px;
    letter-spacing: -0.02em;
  }

  .hero-text h1 em {
    font-style: italic;
    color: rgba(255,255,255,0.65);
  }

  .hero-text p {
    color: rgba(255,255,255,0.7);
    font-size: 16px;
    font-weight: 300;
    line-height: 1.7;
    max-width: 380px;
  }

  .features {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 14px;
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    font-weight: 400;
  }

  .feature-item .icon {
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
  }

  /* ── RIGHT PANEL ─────────────────────────────── */
  .right-panel {
    width: 90%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 52px;
    position: relative;
    background: var(--cream);
  }

  /* Subtle noise texture */
  .right-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    opacity: 0.4;
  }

  .login-card {
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 2;
    animation: fadeUp 0.6s ease both;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .login-card .greeting {
    font-size: 20px;
    font-weight: 500;
    color: var(--black);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .login-card h2 {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 700;
    color: var(--charcoal);
    margin-bottom: 6px;
    letter-spacing: -0.02em;
  }

  .login-card .subtitle {
    color: var(--grey);
    font-size: 15px;
    margin-bottom: 36px;
    font-weight: 300;
  }

  /* ── ERROR BOX ── */
  .error-box {
    background: #FFF0F1;
    border: 1px solid #FFCDD2;
    border-left: 3px solid var(--red);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 24px;
    color: var(--red-dark);
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: shake 0.4s ease;
  }
  @keyframes shake {
    0%,100%{ transform:translateX(0) }
    25%{ transform:translateX(-6px) }
    75%{ transform:translateX(6px) }
  }

  /* ── GOOGLE BUTTON ── */
  .btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 15px 24px;
    background: var(--white);
    border: 1.5px solid var(--grey-light);
    border-radius: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 500;
    color: var(--charcoal);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    position: relative;
    overflow: hidden;
  }

  .btn-google::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(192,0,26,0.04), transparent);
    opacity: 0;
    transition: opacity 0.2s;
  }

  .btn-google:hover {
    border-color: var(--red);
    box-shadow: 0 4px 16px var(--shadow);
    transform: translateY(-1px);
  }
  .btn-google:hover::before { opacity: 1; }

  .btn-google:active { transform: translateY(0); }

  .btn-google svg {
    width: 20px; height: 20px;
    flex-shrink: 0;
  }

  /* ── DIVIDER ── */
  .divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 28px 0;
  }
  .divider::before,.divider::after {
    content:'';
    flex:1;
    height:1px;
    background: var(--grey-light);
  }
  .divider span {
    color: var(--grey);
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  /* ── FORM ── */
  .form-group {
    margin-bottom: 18px;
  }

  .form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--charcoal);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .input-wrap {
    position: relative;
  }

  .input-wrap .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--grey);
    font-size: 16px;
    pointer-events: none;
    transition: color 0.2s;
  }

  .input-wrap .email-suffix {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    color: var(--grey);
    pointer-events: none;
    font-weight: 400;
  }

  .form-group input {
    width: 100%;
    padding: 14px 120px 14px 42px;
    border: 1.5px solid var(--grey-light);
    border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    color: var(--charcoal);
    background: var(--white);
    outline: none;
    transition: all 0.2s ease;
  }

  .form-group input.no-suffix {
    padding: 14px 14px 14px 42px;
  }

  .form-group input:focus {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(192,0,26,0.08);
  }

  .form-group input:focus ~ .input-icon,
  .input-wrap:focus-within .input-icon {
    color: var(--red);
  }

  /* ── FORGOT LINK ── */
  .forgot-row {
    display: flex;
    justify-content: flex-end;
    margin-top: -8px;
    margin-bottom: 24px;
  }

  .forgot-link {
    font-size: 13px;
    color: var(--red);
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s;
  }
  .forgot-link:hover { opacity: 0.7; }

  /* ── LOGIN BUTTON ── */
  .btn-login {
    width: 100%;
    padding: 15px;
    background: var(--red);
    color: var(--white);
    border: none;
    border-radius: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    letter-spacing: 0.02em;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
  }

  .btn-login::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
    pointer-events: none;
  }

  .btn-login:hover {
    background: var(--red-light);
    box-shadow: 0 6px 20px var(--shadow);
    transform: translateY(-1px);
  }
  .btn-login:active { transform: translateY(0); box-shadow: none; }

  /* ── ADMIN LINK ── */
  .admin-link-row {
    text-align: center;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--grey-light);
  }

  .admin-link-row p {
    font-size: 13px;
    color: var(--grey);
  }

  .admin-link-row a {
    color: var(--red);
    font-weight: 600;
    text-decoration: none;
  }
  .admin-link-row a:hover { text-decoration: underline; }

  /* ── VIEW AVAILABILITY ── */
  .view-avail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 13px;
    color: var(--grey);
    text-decoration: none;
    transition: color 0.2s;
  }
  .view-avail:hover { color: var(--red); }
  .view-avail span { font-size: 15px; }

  /* ── MODAL: FORGOT PASSWORD ── */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(26,26,26,0.55);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay.active { display: flex; }

  .modal {
    background: var(--white);
    border-radius: 20px;
    padding: 40px;
    width: 90%;
    max-width: 440px;
    position: relative;
    animation: modalIn 0.3s ease;
    box-shadow: 0 24px 60px rgba(0,0,0,0.15);
  }

  @keyframes modalIn {
    from { opacity:0; transform: scale(0.94) translateY(12px); }
    to   { opacity:1; transform: scale(1) translateY(0); }
  }

  .modal-close {
    position: absolute;
    top: 16px; right: 20px;
    background: none; border: none;
    font-size: 22px; cursor: pointer;
    color: var(--grey);
    line-height: 1;
    transition: color 0.2s;
  }
  .modal-close:hover { color: var(--charcoal); }

  .modal h3 {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 700;
    color: var(--charcoal);
    margin-bottom: 8px;
  }

  .modal .modal-sub {
    font-size: 14px;
    color: var(--grey);
    line-height: 1.6;
    margin-bottom: 24px;
  }

  .modal .notice {
    background: #FFF8E7;
    border: 1px solid #FFE082;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    color: #8B6914;
    margin-bottom: 20px;
    line-height: 1.5;
  }

  .modal .notice strong { display: block; margin-bottom: 2px; }

  .modal .form-group input { padding: 13px 14px 13px 42px; }
  .modal .form-group input.no-suffix { padding: 13px 14px 13px 42px; }

  .btn-reset {
    width: 100%;
    padding: 14px;
    background: var(--red);
    color: var(--white);
    border: none;
    border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
    transition: all 0.2s;
  }
  .btn-reset:hover { background: var(--red-light); box-shadow: 0 4px 14px var(--shadow); }

  /* ── FOOTER ── */
  .login-footer {
    text-align: center;
    margin-top: 32px;
    font-size: 11.5px;
    color: var(--grey);
    letter-spacing: 0.03em;
  }

  @media (max-width: 768px) {
    body { flex-direction: column; overflow: auto; }
    .left-panel { width: 100%; min-height: 220px; padding: 36px 28px; }
    .right-panel { width: 100%; padding: 40px 24px; }
    .hero-text h1 { font-size: 36px; }
    .features { display: none; }
  }
</style>
</head>
<body>

<!-- ── LEFT PANEL ─────────────────────────────── -->
<div class="left-panel">
  <div class="geo-line"></div>

  <div class="brand">
    <div class="brand-badge">
        
      <div class="dot"><span>HIS</span></div>
      <p>Room Booking System</p>
    </div>
  </div>

  <div class="hero-text">
    <h1>Room<br><em>Booking</em><br>HIS.</h1>
    <p>The smarter way to reserve rooms, manage resources, and keep the school running smoothly.</p>
  </div>

  <div class="features">
    <div class="feature-item">
      <div class="icon">📅</div>
      <span>Live calendar with real-time availability</span>
    </div>
    <div class="feature-item">
      <div class="icon">🔄</div>
      <span>Recurring bookings & transfer requests</span>
    </div>
    <div class="feature-item">
      <div class="icon">📊</div>
      <span>Statistics & usage analytics</span>
    </div>
    <div class="feature-item">
      <div class="icon">🎫</div>
      <span>Helpdesk ticket system for issues</span>
    </div>
  </div>
</div>

<!-- ── RIGHT PANEL ─────────────────────────────── -->
<div class="right-panel">
  <div class="login-card">
    <p class="greeting">HELP International School</p>
 
    <p class="subtitle">Access the HIS Room Booking System</p>

    <?php if ($error): ?>
    <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- GOOGLE SIGN IN -->
    <a href="includes/google_auth.php" class="btn-google">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
      </svg>
      Continue with Google 
    </a>

    <div class="divider"><span>or sign in with username</span></div>

    <!-- USERNAME / PASSWORD FORM -->
    <form method="POST" action="includes/login_handler.php">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
           <span class="input-icon">👤</span>
          <input
            type="text"
            name="username"
            placeholder="Enter your username "
            autocomplete="username"
            required
          >
          <span class="email-suffix">@kl.his.edu.my</span>
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input
            type="password"
            name="password"
            class="no-suffix"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
          >
        </div>
      </div>

      <div class="forgot-row">
        <a href="#" class="forgot-link" onclick="openForgot(event)">Forgot your password?</a>
      </div>

      <button type="submit" class="btn-login">Sign In</button>
    </form>

    <!-- VIEW AVAILABILITY (no login) -->
    <u><a href="public_availability.php" class="view-avail">
      <span></span> View room availability
    </a></u>

    <!-- ADMIN LOGIN LINK -->
    <div class="admin-link-row">
      <p>Are you an administrator? <a href="admin_login.php">Admin Login →</a></p>
    </div>

    <div class="login-footer">
      © <?= date('Y') ?> HELP International School · Room Booking System
    </div>
  </div>
</div>

<!-- ── FORGOT PASSWORD MODAL ── -->
<div class="modal-overlay" id="forgotModal">
  <div class="modal">
    <button class="modal-close" onclick="closeForgot()">×</button>
    <h3>Forgot Details</h3>
    <p class="modal-sub">Please enter your username &amp; email address and we'll send an email which will allow you to choose a new password.</p>

    <div class="notice">
      <strong>Forgot your username?</strong>
      Please contact your system administrator.
    </div>

    <form method="POST" action="includes/reset_handler.php">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" name="reset_username" class="no-suffix" placeholder="enter your username" required>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input type="email" name="reset_email" class="no-suffix" placeholder="your@kl.his.edu.my" required>
        </div>
      </div>

      <button type="submit" class="btn-reset">Send Password Reset</button>
    </form>
  </div>
</div>

<script>
function openForgot(e) {
  e.preventDefault();
  document.getElementById('forgotModal').classList.add('active');
}
function closeForgot() {
  document.getElementById('forgotModal').classList.remove('active');
}
// Close on overlay click
document.getElementById('forgotModal').addEventListener('click', function(e) {
  if (e.target === this) closeForgot();
});
// ESC key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeForgot();
});
</script>
</body>
</html>