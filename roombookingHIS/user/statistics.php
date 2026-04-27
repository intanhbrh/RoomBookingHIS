<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$activePage = 'statistics';
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// My booking count
$my_count = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id AND status='confirmed'")->fetch_assoc()['c'];
$my_total = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE user_id=$user_id")->fetch_assoc()['c'];
$total_all = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='confirmed'")->fetch_assoc()['c'];
$total_rooms = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE is_active=1")->fetch_assoc()['c'];

// Most booked room
$top_room = $conn->query("
  SELECT r.room_name, COUNT(b.id) as cnt
  FROM bookings b JOIN rooms r ON b.room_id=r.id
  WHERE b.user_id=$user_id AND b.status='confirmed'
  GROUP BY b.room_id ORDER BY cnt DESC LIMIT 1
")->fetch_assoc();

// Monthly breakdown (last 6 months)
$monthly = $conn->query("
  SELECT DATE_FORMAT(booking_date,'%b %Y') as month,
         DATE_FORMAT(booking_date,'%Y-%m') as ym,
         COUNT(*) as cnt
  FROM bookings
  WHERE user_id=$user_id AND status='confirmed'
    AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY ym ORDER BY ym ASC
")->fetch_all(MYSQLI_ASSOC);

// All users bookings total
$user_stats = $conn->query("
  SELECT u.first_name, u.last_name, COUNT(b.id) as cnt
  FROM bookings b JOIN users u ON b.user_id=u.id
  WHERE b.status='confirmed'
  GROUP BY b.user_id ORDER BY cnt DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Statistics — HIS Room Booking</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <div><p class="topbar-title">Statistics</p><p class="topbar-date"><?= date('l, j F Y') ?></p></div>
    </div>
    <div class="topbar-right">
      <div class="user-avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
      <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </header>

  <div class="page-body">
    <div class="page-header">
      <h1>Your Statistics</h1>
      <p>Overview of your bookings and school-wide activity</p>
    </div>

    <!-- STAT CARDS -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-val"><?= $my_count ?></div>
        <div class="stat-label">Your active bookings</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-val"><?= $my_total ?></div>
        <div class="stat-label">Total bookings you've made</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏫</div>
        <div class="stat-val"><?= $total_all ?></div>
        <div class="stat-label">Total bookings school-wide</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🚪</div>
        <div class="stat-val"><?= $total_rooms ?></div>
        <div class="stat-label">Available rooms</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

      <!-- YOUR MONTHLY BOOKINGS -->
      <div class="card">
        <p class="card-title">📈 Your Bookings (Last 6 Months)</p>
        <?php if (empty($monthly)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><p>No bookings in the last 6 months.</p></div>
        <?php else: ?>
        <?php
        $max = max(array_column($monthly,'cnt')) ?: 1;
        foreach ($monthly as $m):
          $pct = round(($m['cnt']/$max)*100);
        ?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
            <span><?= $m['month'] ?></span>
            <strong><?= $m['cnt'] ?> booking<?= $m['cnt']!=1?'s':'' ?></strong>
          </div>
          <div style="height:8px;background:#f0ede8;border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:<?= $pct ?>%;background:var(--red);border-radius:4px;transition:width .4s;"></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($top_room): ?>
        <div style="margin-top:20px;padding:14px;background:var(--red-pale);border-radius:10px;border-left:3px solid var(--red);">
          <p style="font-size:11px;font-weight:600;color:var(--grey);text-transform:uppercase;letter-spacing:.06em;">Your Most Booked Room</p>
          <p style="font-size:15px;font-weight:600;color:var(--red);margin-top:4px;">🏆 <?= htmlspecialchars($top_room['room_name']) ?></p>
          <p style="font-size:12px;color:var(--grey);"><?= $top_room['cnt'] ?> booking<?= $top_room['cnt']!=1?'s':'' ?></p>
        </div>
        <?php endif; ?>
      </div>

      <!-- SCHOOL-WIDE TOP BOOKERS -->
      <div class="card">
        <p class="card-title">🏅 Most Active Staff (School-Wide)</p>
        <?php if (empty($user_stats)): ?>
        <div class="empty-state"><div class="empty-icon">📭</div><p>No data yet.</p></div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Name</th><th>Bookings</th></tr></thead>
            <tbody>
            <?php foreach ($user_stats as $i => $u): ?>
            <tr <?= $u['cnt'] && isset($_SESSION['first_name']) && $u['first_name']==$_SESSION['first_name'] ? 'style="background:var(--green-bg);"' : '' ?>>
              <td style="font-weight:700;color:var(--grey);"><?= $i+1 ?></td>
              <td><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
              <td><span class="badge badge-green"><?= $u['cnt'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<div class="modal-overlay" id="feedbackModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('feedbackModal')">×</button>
    <div class="modal-icon">💬</div>
    <h3>Send Feedback</h3>
    <p class="modal-desc">We'd love to hear your comments.</p>
    <form method="POST" action="feedback_handler.php">
      <textarea name="message" placeholder="Write your message here..." required></textarea>
      <button type="submit" class="btn-primary" style="width:100%;">Send Feedback</button>
    </form>
  </div>
</div>
<script>
function openFeedback(e){ e.preventDefault(); document.getElementById('feedbackModal').classList.add('active'); }
function closeModal(id){ document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(el=>{ el.addEventListener('click',e=>{ if(e.target===el) el.classList.remove('active'); }); });
</script>
</body>
</html>