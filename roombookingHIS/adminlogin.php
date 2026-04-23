<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php"); exit();
}
$error = '';
if (isset($_GET['error'])) {
    $error = $_GET['error'] === 'invalid' ? 'Invalid admin credentials. Please try again.' : 'Access denied.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HIS Room Booking — Admin Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --red:#C0001A; --red-dark:#8B0013; --red-light:#E8001F;
    --cream:#FDF6F0; --white:#FFFFFF; --charcoal:#1A1A1A;
    --grey:#6B6B6B; --grey-light:#E8E2DC; --shadow:rgba(192,0,26,0.18);
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'DM Sans',sans-serif;background:var(--charcoal);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}

  /* Dark geometric bg */
  body::before{content:'';position:fixed;inset:0;
    background-image:radial-gradient(circle at 20% 20%, rgba(192,0,26,0.15) 0%, transparent 50%),
                     radial-gradient(circle at 80% 80%, rgba(192,0,26,0.10) 0%, transparent 50%);
    pointer-events:none;}

  .grid-bg{position:fixed;inset:0;pointer-events:none;opacity:0.04;
    background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),
                     linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);
    background-size:60px 60px;}

  .admin-card{
    width:90%;max-width:420px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:24px;
    padding:44px 40px;
    backdrop-filter:blur(20px);
    position:relative;
    animation:fadeUp .5s ease both;
    box-shadow:0 32px 80px rgba(0,0,0,0.4);
  }

  @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

  .admin-badge{
    display:inline-flex;align-items:center;gap:10px;
    background:rgba(192,0,26,0.2);border:1px solid rgba(192,0,26,0.4);
    border-radius:100px;padding:6px 16px 6px 6px;margin-bottom:32px;
  }
  .admin-badge .dot{width:28px;height:28px;background:var(--red);border-radius:50%;display:flex;align-items:center;justify-content:center;}
  .admin-badge .dot span{font-family:'Playfair Display',serif;font-weight:900;font-size:11px;color:#fff;}
  .admin-badge p{color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;}

  .admin-card h2{font-family:'Playfair Display',serif;font-size:32px;font-weight:700;color:#fff;margin-bottom:4px;letter-spacing:-.02em;}
  .admin-card .sub{font-size:14px;color:rgba(255,255,255,0.45);margin-bottom:32px;font-weight:300;}

  .error-box{background:rgba(192,0,26,0.15);border:1px solid rgba(192,0,26,0.4);border-radius:10px;padding:12px 14px;margin-bottom:20px;color:#FF8A9A;font-size:13.5px;display:flex;align-items:center;gap:8px;}

  .form-group{margin-bottom:18px;}
  .form-group label{display:block;font-size:11.5px;font-weight:600;color:rgba(255,255,255,0.5);letter-spacing:.08em;text-transform:uppercase;margin-bottom:8px;}
  .input-wrap{position:relative;}
  .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.3);font-size:15px;pointer-events:none;transition:color .2s;}
  .form-group input{width:100%;padding:14px 14px 14px 42px;background:rgba(255,255,255,0.06);border:1.5px solid rgba(255,255,255,0.1);border-radius:12px;font-family:'DM Sans',sans-serif;font-size:15px;color:#fff;outline:none;transition:all .2s;}
  .form-group input::placeholder{color:rgba(255,255,255,0.25);}
  .form-group input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(192,0,26,0.12);}
  .form-group input:focus+.input-icon,.input-wrap:focus-within .input-icon{color:var(--red);}

  .forgot-row{display:flex;justify-content:flex-end;margin-top:-8px;margin-bottom:24px;}
  .forgot-link{font-size:13px;color:rgba(192,0,26,0.8);text-decoration:none;font-weight:500;transition:opacity .2s;}
  .forgot-link:hover{opacity:.7;}

  .btn-admin{width:100%;padding:15px;background:var(--red);color:#fff;border:none;border-radius:12px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;position:relative;overflow:hidden;}
  .btn-admin::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1),transparent);pointer-events:none;}
  .btn-admin:hover{background:var(--red-light);box-shadow:0 6px 20px var(--shadow);transform:translateY(-1px);}
  .btn-admin:active{transform:translateY(0);}

  .back-link{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:24px;font-size:13px;color:rgba(255,255,255,0.4);text-decoration:none;transition:color .2s;}
  .back-link:hover{color:rgba(255,255,255,0.7);}

  /* FORGOT MODAL */
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;}
  .modal-overlay.active{display:flex;}
  .modal{background:#1E1E1E;border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:36px;width:90%;max-width:420px;position:relative;animation:modalIn .3s ease;box-shadow:0 24px 60px rgba(0,0,0,0.5);}
  @keyframes modalIn{from{opacity:0;transform:scale(.94) translateY(12px)}to{opacity:1;transform:scale(1) translateY(0)}}
  .modal-close{position:absolute;top:14px;right:18px;background:none;border:none;font-size:22px;cursor:pointer;color:rgba(255,255,255,0.4);line-height:1;transition:color .2s;}
  .modal-close:hover{color:#fff;}
  .modal h3{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#fff;margin-bottom:8px;}
  .modal .modal-sub{font-size:13.5px;color:rgba(255,255,255,0.45);line-height:1.6;margin-bottom:20px;}
  .modal .notice{background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:10px;padding:12px 14px;font-size:13px;color:#FFD54F;margin-bottom:18px;line-height:1.5;}
  .modal .notice strong{display:block;margin-bottom:2px;}
  .modal .form-group input{background:rgba(255,255,255,0.06);border-color:rgba(255,255,255,0.1);color:#fff;}
  .modal .form-group input::placeholder{color:rgba(255,255,255,0.25);}
  .btn-reset{width:100%;padding:13px;background:var(--red);color:#fff;border:none;border-radius:12px;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:600;cursor:pointer;margin-top:8px;transition:all .2s;}
  .btn-reset:hover{background:var(--red-light);box-shadow:0 4px 14px var(--shadow);}
</style>
</head>
<body>
<div class="grid-bg"></div>

<div class="admin-card">
  <div class="admin-badge">
    <div class="dot"><span>HIS</span></div>
    <p>Administrator Access</p>
  </div>

  <h2>Admin Login</h2>
  <p class="sub">Restricted access — authorised personnel only</p>

  <?php if ($error): ?>
  <div class="error-box">🚫 <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="includes/admin_login_handler.php">
    <div class="form-group">
      <label>Username</label>
      <div class="input-wrap">
        <span class="input-icon">👤</span>
        <input type="text" name="username" placeholder="Admin username" autocomplete="username" required>
      </div>
    </div>

    <div class="form-group">
      <label>Password</label>
      <div class="input-wrap">
        <span class="input-icon">🔒</span>
        <input type="password" name="password" placeholder="Admin password" autocomplete="current-password" required>
      </div>
    </div>

    <div class="forgot-row">
      <a href="#" class="forgot-link" onclick="openForgot(event)">Forgot your password?</a>
    </div>

    <button type="submit" class="btn-admin">Sign In as Administrator</button>
  </form>

  <a href="index.php" class="back-link">← Back to Staff Login</a>
</div>

<!-- FORGOT MODAL -->
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
          <input type="text" name="reset_username" placeholder="Admin username" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input type="email" name="reset_email" placeholder="enter your email" required>
        </div>
      </div>
      <button type="submit" class="btn-reset">Send Password Reset</button>
    </form>
  </div>
</div>

<script>
function openForgot(e){e.preventDefault();document.getElementById('forgotModal').classList.add('active');}
function closeForgot(){document.getElementById('forgotModal').classList.remove('active');}
document.getElementById('forgotModal').addEventListener('click',function(e){if(e.target===this)closeForgot();});
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeForgot();});
</script>
</body>
</html>