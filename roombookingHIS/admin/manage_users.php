<?php
require_once '../includes/auth.php'; requireAdmin();
require_once '../includes/db.php';

// Handle role/status toggle
if (isset($_GET['toggle_role'])) {
    $id=intval($_GET['toggle_role']);
    $u=$conn->query("SELECT role FROM users WHERE id=$id")->fetch_assoc();
    $new = $u['role']==='admin' ? 'user' : 'admin';
    $conn->query("UPDATE users SET role='$new' WHERE id=$id");
    header("Location: manage_users.php"); exit();
}
if (isset($_GET['reset_pw'])) {
    $id=intval($_GET['reset_pw']);
    $hash=password_hash('HIS@2024',PASSWORD_BCRYPT);
    $s=$conn->prepare("UPDATE users SET password=? WHERE id=?");
    $s->bind_param("si",$hash,$id); $s->execute();
    header("Location: manage_users.php?success=reset"); exit();
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $sq="%$search%";
    $s=$conn->prepare("SELECT * FROM users WHERE (username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) ORDER BY first_name");
    $s->bind_param("ssss",$sq,$sq,$sq,$sq); $s->execute();
    $users = $s->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $users=$conn->query("SELECT * FROM users ORDER BY role DESC, first_name ASC")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Users — Admin</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left"><div><p class="topbar-title">Manage Users</p></div></div>
    <div class="topbar-right"><span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span></div>
  </header>
  <div class="page-body">
    <?php if(isset($_GET['success'])):?><div class="alert alert-success">✅ Password reset to HIS@2024</div><?php endif;?>

    <!-- SEARCH -->
    <div class="card" style="margin-bottom:18px;">
      <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;">
          <label>Search staff</label>
          <input type="text" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Name, username or email...">
        </div>
        <button type="submit" class="btn-primary" style="margin-bottom:16px;">🔍 Search</button>
        <?php if($search):?><a href="manage_users.php" class="btn-secondary" style="margin-bottom:16px;">Clear</a><?php endif;?>
      </form>
    </div>

    <div class="card">
      <p class="card-title">👥 All Users <span style="font-size:13px;color:var(--grey);font-weight:400;"><?=count($users)?> found</span></p>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $u):?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:32px;height:32px;border-radius:50%;background:<?=$u['role']==='admin'?'#111':'var(--red)'?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">
                  <?=strtoupper(substr($u['first_name'],0,1))?>
                </div>
                <?=htmlspecialchars($u['first_name'].' '.$u['last_name'])?>
              </div>
            </td>
            <td style="font-size:12px;color:var(--grey);">@<?=htmlspecialchars($u['username'])?></td>
            <td style="font-size:12px;"><?=htmlspecialchars($u['email'])?></td>
            <td><span class="badge <?=$u['role']==='admin'?'badge-red':'badge-blue'?>"><?=ucfirst($u['role'])?></span></td>
            <td style="white-space:nowrap;display:flex;gap:10px;">
              <?php if($u['id']!==$_SESSION['user_id']):?>
              <a href="manage_users.php?toggle_role=<?=$u['id']?>"
                 style="font-size:12px;font-weight:600;text-decoration:none;color:var(--grey-dark);"
                 onclick="return confirm('Change role for <?=htmlspecialchars($u['first_name'])?>?')">
                <?=$u['role']==='admin'?'→ User':'→ Admin'?>
              </a>
              <?php endif;?>
              <a href="manage_users.php?reset_pw=<?=$u['id']?>"
                 style="font-size:12px;font-weight:600;text-decoration:none;color:var(--red);"
                 onclick="return confirm('Reset password to HIS@2024 for <?=htmlspecialchars($u['first_name'])?>?')">
                Reset PW
              </a>
            </td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>