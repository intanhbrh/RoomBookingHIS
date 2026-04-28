<?php
// admin/all_bookings.php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

// Handle cancel
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$id");
    header("Location: all_bookings.php?success=cancelled"); exit();
}

$success = $_GET['success'] ?? '';

// Filters
$filter_date = $_GET['date']   ?? '';
$filter_room = $_GET['room']   ?? '';
$filter_user = $_GET['user']   ?? '';

// Build query
$where = ["b.status != 'cancelled'"];
$params = []; $types = '';
if ($filter_date) { $where[] = "b.booking_date = ?"; $params[] = $filter_date; $types .= 's'; }
if ($filter_room) { $where[] = "b.room_id = ?";      $params[] = $filter_room; $types .= 'i'; }
if ($filter_user) { $where[] = "b.user_id = ?";      $params[] = $filter_user; $types .= 'i'; }

$sql = "SELECT b.*, u.first_name, u.last_name, u.username, r.room_name, r.category
        FROM bookings b
        JOIN users u ON b.user_id=u.id
        JOIN rooms r ON b.room_id=r.id
        WHERE ".implode(' AND ',$where)."
        ORDER BY b.booking_date DESC, b.start_time ASC
        LIMIT 100";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$rooms = $conn->query("SELECT id,room_name FROM rooms WHERE is_active=1 ORDER BY room_name")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT id,first_name,last_name FROM users WHERE role='user' ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

$setup_labels = ['assembly_all'=>'Assembly – All','assembly_teachers'=>'Assembly – Teachers','booth'=>'Booth','classroom'=>'Classroom','exam'=>'Exam','group_12'=>'Group of 12','group_6'=>'Group of 6','group_8'=>'Group of 8','theatre'=>'Theatre','no_setup'=>'No Setup','other'=>'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Bookings — Admin</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>

<div class="main-content">
  <header class="topbar">
    <div class="topbar-left"><div><p class="topbar-title">All Bookings</p><p class="topbar-date"><?=date('l, j F Y')?></p></div></div>
    <div class="topbar-right">
      <span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span>
    </div>
  </header>

  <div class="page-body">
    <?php if($success==='cancelled'):?><div class="alert alert-success">✅ Booking cancelled.</div><?php endif;?>

    <!-- FILTERS -->
    <div class="card" style="margin-bottom:20px;">
      <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:140px;">
          <label>Date</label>
          <input type="date" name="date" value="<?=htmlspecialchars($filter_date)?>">
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:160px;">
          <label>Room</label>
          <select name="room">
            <option value="">All Rooms</option>
            <?php foreach($rooms as $r): ?>
            <option value="<?=$r['id']?>" <?=$filter_room==$r['id']?'selected':''?>><?=htmlspecialchars($r['room_name'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:160px;">
          <label>Staff Member</label>
          <select name="user">
            <option value="">All Staff</option>
            <?php foreach($users as $u): ?>
            <option value="<?=$u['id']?>" <?=$filter_user==$u['id']?'selected':''?>><?=htmlspecialchars($u['first_name'].' '.$u['last_name'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <button type="submit" class="btn-primary" style="margin-bottom:16px;">🔍 Filter</button>
        <a href="all_bookings.php" class="btn-secondary" style="margin-bottom:16px;">Clear</a>
      </form>
    </div>

    <!-- BOOKINGS TABLE -->
    <div class="card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <p class="card-title" style="margin:0;">📋 Bookings
          <span style="font-size:13px;color:var(--grey);font-weight:400;margin-left:6px;"><?=count($bookings)?> results</span>
        </p>
      </div>

      <?php if(empty($bookings)):?>
      <div class="empty-state"><div class="empty-icon">📭</div><h3>No bookings found</h3><p>Try adjusting your filters above.</p></div>
      <?php else:?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Staff</th>
              <th>Room</th>
              <th>Date</th>
              <th>Time</th>
              <th>Purpose</th>
              <th>Setup</th>
              <th>Students</th>
              <th>Adults</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($bookings as $b):
            $isPast = $b['booking_date'] < date('Y-m-d');
          ?>
          <tr>
            <td>
              <strong><?=htmlspecialchars($b['first_name'].' '.$b['last_name'])?></strong><br>
              <span style="font-size:11px;color:var(--grey);">@<?=htmlspecialchars($b['username'])?></span>
            </td>
            <td><?=htmlspecialchars($b['room_name'])?></td>
            <td><?=date('d M Y',strtotime($b['booking_date']))?></td>
            <td style="white-space:nowrap;"><?=substr($b['start_time'],0,5)?> – <?=substr($b['end_time'],0,5)?></td>
            <td><?=htmlspecialchars($b['title']??'—')?></td>
            <td style="font-size:12px;"><?=$setup_labels[$b['setup_type']??'']??'—'?></td>
            <td style="text-align:center;"><?=$b['student_count']??0?></td>
            <td style="text-align:center;"><?=$b['adult_count']??1?></td>
            <td>
              <span class="badge <?=$isPast?'badge-grey':'badge-green'?>">
                <?=$isPast?'Past':ucfirst($b['status'])?>
              </span>
            </td>
            <td>
              <?php if(!$isPast):?>
              <a href="all_bookings.php?cancel=<?=$b['id']?>"
                 onclick="return confirm('Cancel this booking by <?=htmlspecialchars($b['first_name'])?> for <?=htmlspecialchars($b['room_name'])?>?')"
                 style="color:var(--red);font-size:12px;font-weight:600;text-decoration:none;">Cancel</a>
              <?php endif;?>
            </td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>
</body>
</html>