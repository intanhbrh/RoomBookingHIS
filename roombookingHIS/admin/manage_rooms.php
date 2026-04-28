<?php
require_once '../includes/auth.php'; requireAdmin();
require_once '../includes/db.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['room_name']??'');
        $cat  = trim($_POST['category']??'');
        $cap  = intval($_POST['capacity']??0);
        $desc = trim($_POST['description']??'');
        if ($name && $cat) {
            $s=$conn->prepare("INSERT INTO rooms (room_name,category,capacity,description,is_active) VALUES (?,?,?,?,1)");
            $s->bind_param("ssis",$name,$cat,$cap,$desc); $s->execute();
        }
    } elseif ($action==='edit') {
        $id=$intval($_POST['id']??0);
        $name=trim($_POST['room_name']??'');
        $cat=trim($_POST['category']??'');
        $cap=intval($_POST['capacity']??0);
        $desc=trim($_POST['description']??'');
        $s=$conn->prepare("UPDATE rooms SET room_name=?,category=?,capacity=?,description=? WHERE id=?");
        $s->bind_param("ssisi",$name,$cat,$cap,$desc,$id); $s->execute();
    }
    header("Location: manage_rooms.php?success=1"); exit();
}
if (isset($_GET['toggle'])) {
    $id=intval($_GET['toggle']);
    $conn->query("UPDATE rooms SET is_active = NOT is_active WHERE id=$id");
    header("Location: manage_rooms.php"); exit();
}
if (isset($_GET['delete'])) {
    $id=intval($_GET['delete']);
    $conn->query("UPDATE rooms SET is_active=0 WHERE id=$id");
    header("Location: manage_rooms.php"); exit();
}

$rooms = $conn->query("SELECT * FROM rooms ORDER BY category, room_name")->fetch_all(MYSQLI_ASSOC);
$cats  = ['event_space'=>'Event Space','meeting_space'=>'Meeting Space','timetabled_space'=>'Timetabled Space'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Rooms — Admin</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left"><div><p class="topbar-title">Manage Rooms</p></div></div>
    <div class="topbar-right"><span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span></div>
  </header>
  <div class="page-body">
    <?php if(isset($_GET['success'])):?><div class="alert alert-success">✅ Room saved successfully.</div><?php endif;?>

    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:22px;align-items:start;">

      <!-- ADD ROOM FORM -->
      <div class="card" style="position:sticky;top:76px;">
        <p class="card-title">➕ Add New Room</p>
        <form method="POST">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label>Room Name</label>
            <input type="text" name="room_name" placeholder="e.g. L2 Corporate Lounge" required>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category" required>
              <option value="">— Select —</option>
              <?php foreach($cats as $val=>$lbl):?>
              <option value="<?=$val?>"><?=$lbl?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="form-group">
            <label>Capacity</label>
            <input type="number" name="capacity" min="0" placeholder="e.g. 30">
          </div>
          <div class="form-group">
            <label>Description (optional)</label>
            <textarea name="description" rows="2" placeholder="Any notes about this room..."></textarea>
          </div>
          <button type="submit" class="btn-primary" style="width:100%;">Save Room</button>
        </form>
      </div>

      <!-- ROOMS LIST -->
      <div class="card">
        <p class="card-title">🚪 All Rooms <span style="font-size:13px;color:var(--grey);font-weight:400;"><?=count($rooms)?> total</span></p>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Room</th><th>Category</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($rooms as $r):?>
            <tr style="<?=$r['is_active']?'':'opacity:.5;'?>">
              <td><strong><?=htmlspecialchars($r['room_name'])?></strong><?php if($r['description']):?><br><span style="font-size:11px;color:var(--grey);"><?=htmlspecialchars($r['description'])?></span><?php endif;?></td>
              <td><span class="badge <?=match($r['category']){'event_space'=>'badge-blue','meeting_space'=>'badge-green',default=>'badge-yellow'}?>"><?=$cats[$r['category']]??$r['category']?></span></td>
              <td><?=$r['capacity']?:'—'?></td>
              <td><span class="badge <?=$r['is_active']?'badge-green':'badge-grey'?>"><?=$r['is_active']?'Active':'Inactive'?></span></td>
              <td style="white-space:nowrap;">
                <a href="manage_rooms.php?toggle=<?=$r['id']?>"
                   style="font-size:12px;font-weight:600;text-decoration:none;color:<?=$r['is_active']?'var(--red)':'var(--green)'?>;">
                  <?=$r['is_active']?'Deactivate':'Activate'?>
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
</div>
</body>
</html>