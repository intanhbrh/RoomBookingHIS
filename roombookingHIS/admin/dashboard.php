<?php
// admin/dashboard.php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';

$admin_name = $_SESSION['first_name'];

// ── STATS ──────────────────────────────────────────
$total_bookings  = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
$today_bookings  = $conn->query("SELECT COUNT(*) FROM bookings WHERE booking_date=CURDATE() AND status='confirmed'")->fetch_row()[0];
$total_users     = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$total_rooms     = $conn->query("SELECT COUNT(*) FROM rooms WHERE is_active=1")->fetch_row()[0];
$open_tickets    = $conn->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetch_row()[0];
$pending_transfer= $conn->query("SELECT COUNT(*) FROM transfer_requests WHERE status='pending'")->fetch_row()[0];

// ── TODAY'S BOOKINGS ───────────────────────────────
$today_list = $conn->query("
    SELECT b.*, u.first_name, u.last_name, r.room_name
    FROM bookings b
    JOIN users u ON b.user_id=u.id
    JOIN rooms r ON b.room_id=r.id
    WHERE b.booking_date=CURDATE() AND b.status='confirmed'
    ORDER BY b.start_time ASC
    LIMIT 10
    
")->fetch_all(MYSQLI_ASSOC);

// ── RECENT BOOKINGS (last 7 days) ──────────────────
$recent = $conn->query("
    SELECT b.*, u.first_name, u.last_name, r.room_name
    FROM bookings b
    JOIN users u ON b.user_id=u.id
    JOIN rooms r ON b.room_id=r.id
    WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// ── MOST BOOKED ROOMS ──────────────────────────────
$top_rooms = $conn->query("
    SELECT r.room_name, r.category, COUNT(b.id) as cnt
    FROM bookings b JOIN rooms r ON b.room_id=r.id
    WHERE b.status='confirmed'
    GROUP BY b.room_id ORDER BY cnt DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── PENDING TRANSFERS ──────────────────────────────
$transfers = $conn->query("
    SELECT tr.*,
           uf.first_name as from_fn, uf.last_name as from_ln,
           ut.first_name as to_fn,   ut.last_name as to_ln,
           b.booking_date, b.start_time, b.end_time, r.room_name
    FROM transfer_requests tr
    JOIN users uf ON tr.from_user_id=uf.id
    JOIN users ut ON tr.to_user_id=ut.id
    JOIN bookings b ON tr.booking_id=b.id
    JOIN rooms r ON b.room_id=r.id
    WHERE tr.status='pending'
    ORDER BY tr.requested_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ── OPEN TICKETS ───────────────────────────────────
$tickets = $conn->query("
    SELECT t.*, u.first_name, u.last_name
    FROM tickets t JOIN users u ON t.user_id=u.id
    WHERE t.status='Open'
    ORDER BY t.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — HIS Room Booking</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* Admin sidebar override — slightly different accent */
.sidebar { background: #111111; }
.admin-topbar-badge {
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(192,0,26,0.12);border:1px solid rgba(192,0,26,0.3);
  border-radius:100px;padding:3px 10px;
  font-size:11px;font-weight:600;color:var(--red);letter-spacing:.04em;
}
.section-head {
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:14px;
}
.section-head h2 {
  font-family:'Playfair Display',serif;
  font-size:17px;font-weight:700;color:var(--charcoal);
}
.section-head a { font-size:13px;color:var(--red);text-decoration:none;font-weight:500; }
.section-head a:hover { text-decoration:underline; }

/* Quick action cards */
.action-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
  gap:12px;margin-bottom:28px;
}
.action-card {
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius);padding:18px 16px;
  text-decoration:none;transition:all .2s;
  display:flex;flex-direction:column;gap:10px;
}
.action-card:hover { transform:translateY(-2px);box-shadow:var(--shadow-md);border-color:var(--red); }
.action-card .ac-icon { font-size:24px; }
.action-card .ac-label { font-size:13px;font-weight:600;color:var(--charcoal); }
.action-card .ac-sub   { font-size:11.5px;color:var(--grey); }

/* Today timeline */
.timeline { display:flex;flex-direction:column;gap:10px; }
.tl-item {
  display:flex;align-items:flex-start;gap:12px;
  padding:10px 14px;background:var(--surface-2);
  border-radius:10px;border:1px solid var(--border);
}
.tl-time {
  font-size:11px;font-weight:700;color:var(--red);
  white-space:nowrap;min-width:52px;padding-top:2px;
}
.tl-room { font-size:13px;font-weight:600;color:var(--charcoal); }
.tl-who  { font-size:11.5px;color:var(--grey);margin-top:2px; }
</style>
</head>
<body>

<!-- ADMIN SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo"><span>HIS</span></div>
    <div class="brand-text">
      <p class="brand-title">Admin Panel</p>
      <p class="brand-sub">Room Booking System</p>
    </div>
  </div>

  <nav class="sidebar-nav">
    <p class="nav-section-label">Overview</p>
    <a href="dashboard.php" class="nav-item active">
      <span class="nav-icon">📊</span><span class="nav-label">Dashboard</span>
    </a>
    <a href="all_bookings.php" class="nav-item">
      <span class="nav-icon">📅</span><span class="nav-label">All Bookings</span>
    </a>

    <p class="nav-section-label">Manage</p>
    <a href="manage_rooms.php" class="nav-item">
      <span class="nav-icon">🚪</span><span class="nav-label">Rooms</span>
    </a>
    <a href="manage_users.php" class="nav-item">
      <span class="nav-icon">👥</span><span class="nav-label">Users</span>
    </a>
    <a href="manage_tickets.php" class="nav-item">
      <span class="nav-icon">🎫</span><span class="nav-label">Helpdesk Tickets</span>
      <?php if($open_tickets>0):?>
      <span class="nav-badge"><?=$open_tickets?></span>
      <?php endif;?>
    </a>
    <a href="manage_transfers.php" class="nav-item">
      <span class="nav-icon">🔁</span><span class="nav-label">Transfer Requests</span>
      <?php if($pending_transfer>0):?>
      <span class="nav-badge"><?=$pending_transfer?></span>
      <?php endif;?>
    </a>
    <a href="reports.php" class="nav-item">
      <span class="nav-icon">📈</span><span class="nav-label">Reports</span>
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="../index.php" class="nav-item">
      <span class="nav-icon">👤</span><span class="nav-label">User Login Page</span>
    </a>
    <a href="../logout.php" class="nav-item nav-signout">
      <span class="nav-icon">🚪</span><span class="nav-label">Sign Out</span>
    </a>
  </div>
</aside>

<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <div>
        <p class="topbar-title">Admin Dashboard</p>
        <p class="topbar-date" id="liveClock"></p>
      </div>
    </div>
    <div class="topbar-right">
      <span class="admin-topbar-badge">🔐 Administrator</span>
      <div class="user-avatar" style="background:#111;"><?=strtoupper(substr($admin_name,0,1))?></div>
      <span class="user-name"><?=htmlspecialchars($admin_name)?></span>
    </div>
  </header>

  <div class="page-body">

    <!-- STAT CARDS -->
    <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:24px;">
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-val"><?=$today_bookings?></div>
        <div class="stat-label">Bookings Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-val"><?=$total_bookings?></div>
        <div class="stat-label">Total Bookings</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-val"><?=$total_users?></div>
        <div class="stat-label">Staff Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🚪</div>
        <div class="stat-val"><?=$total_rooms?></div>
        <div class="stat-label">Active Rooms</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎫</div>
        <div class="stat-val"><?=$open_tickets?></div>
        <div class="stat-label">Open Tickets</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔁</div>
        <div class="stat-val"><?=$pending_transfer?></div>
        <div class="stat-label">Pending Transfers</div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-head"><h2>Quick Actions</h2></div>
    <div class="action-grid">
      <a href="manage_rooms.php?action=add" class="action-card">
        <span class="ac-icon">➕</span>
        <span class="ac-label">Add Room</span>
        <span class="ac-sub">Create a new room or resource</span>
      </a>
      <a href="manage_users.php" class="action-card">
        <span class="ac-icon">👤</span>
        <span class="ac-label">Manage Users</span>
        <span class="ac-sub">View, edit or disable staff</span>
      </a>
      <a href="all_bookings.php" class="action-card">
        <span class="ac-icon">📋</span>
        <span class="ac-label">All Bookings</span>
        <span class="ac-sub">View & cancel any booking</span>
      </a>
      <a href="manage_tickets.php" class="action-card">
        <span class="ac-icon">🎫</span>
        <span class="ac-label">Helpdesk</span>
        <span class="ac-sub"><?=$open_tickets?> open ticket<?=$open_tickets!=1?'s':''?></span>
      </a>
      <a href="manage_transfers.php" class="action-card">
        <span class="ac-icon">🔁</span>
        <span class="ac-label">Transfers</span>
        <span class="ac-sub"><?=$pending_transfer?> pending request<?=$pending_transfer!=1?'s':''?></span>
      </a>
      <a href="reports.php" class="action-card">
        <span class="ac-icon">📈</span>
        <span class="ac-label">Reports</span>
        <span class="ac-sub">Usage analytics & exports</span>
      </a>
    </div>

    <!-- MAIN GRID -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

      <!-- TODAY'S BOOKINGS -->
      <div class="card">
        <div class="section-head">
          <h2>📅 Today's Bookings</h2>
          <a href="all_bookings.php?date=<?=date('Y-m-d')?>">View all →</a>
        </div>
        <?php if(empty($today_list)):?>
        <div class="empty-state" style="padding:24px;">
          <div class="empty-icon">🎉</div>
          <p>No bookings today</p>
        </div>
        <?php else:?>
        <div class="timeline">
        <?php foreach($today_list as $t):?>
        <div class="tl-item">
          <div class="tl-time"><?=substr($t['start_time'],0,5)?></div>
          <div>
            <div class="tl-room"><?=htmlspecialchars($t['room_name'])?></div>
            <div class="tl-who"><?=htmlspecialchars($t['first_name'].' '.$t['last_name'])?> · <?=htmlspecialchars($t['title']??'—')?></div>
          </div>
        </div>
        <?php endforeach;?>
        </div>
        <?php endif;?>
      </div>

      <!-- TOP ROOMS -->
      <div class="card">
        <div class="section-head">
          <h2>🏆 Most Booked Rooms</h2>
          <a href="reports.php">Full report →</a>
        </div>
        <?php if(empty($top_rooms)):?>
        <div class="empty-state" style="padding:24px;"><p>No data yet</p></div>
        <?php else:
        $max=max(array_column($top_rooms,'cnt'))?:1;
        foreach($top_rooms as $i=>$r): $pct=round(($r['cnt']/$max)*100);?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
            <span style="font-weight:500;"><?=htmlspecialchars($r['room_name'])?></span>
            <strong style="color:var(--red);"><?=$r['cnt']?></strong>
          </div>
          <div style="height:7px;background:#f0ede8;border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:<?=$pct?>%;background:var(--red);border-radius:4px;transition:width .5s <?=$i*.08?>s;"></div>
          </div>
        </div>
        <?php endforeach;endif;?>
      </div>
    </div>

    <!-- BOTTOM GRID -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

      <!-- PENDING TRANSFERS -->
      <div class="card">
        <div class="section-head">
          <h2>🔁 Pending Transfers</h2>
          <a href="manage_transfers.php">View all →</a>
        </div>
        <?php if(empty($transfers)):?>
        <div class="empty-state" style="padding:20px;"><div class="empty-icon">✅</div><p>No pending transfers</p></div>
        <?php else:?>
        <div class="table-wrap">
        <table>
          <thead><tr><th>From</th><th>Room</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($transfers as $tr):?>
          <tr>
            <td><?=htmlspecialchars($tr['from_fn'].' '.$tr['from_ln'])?></td>
            <td><?=htmlspecialchars($tr['room_name'])?></td>
            <td><?=date('d M',strtotime($tr['booking_date']))?></td>
            <td>
              <a href="manage_transfers.php?approve=<?=$tr['id']?>" style="color:var(--green);font-size:12px;font-weight:600;text-decoration:none;">✅ Approve</a>
              &nbsp;
              <a href="manage_transfers.php?decline=<?=$tr['id']?>" style="color:var(--red);font-size:12px;font-weight:600;text-decoration:none;">❌ Decline</a>
            </td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        </div>
        <?php endif;?>
      </div>

      <!-- OPEN TICKETS -->
      <div class="card">
        <div class="section-head">
          <h2>🎫 Open Tickets</h2>
          <a href="manage_tickets.php">View all →</a>
        </div>
        <?php if(empty($tickets)):?>
        <div class="empty-state" style="padding:20px;"><div class="empty-icon">✅</div><p>No open tickets</p></div>
        <?php else:?>
        <div class="table-wrap">
        <table>
          <thead><tr><th>Subject</th><th>By</th><th>Priority</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($tickets as $t):
            $pc=match($t['priority']){'Emergency','High'=>'badge-red','Medium'=>'badge-yellow',default=>'badge-grey'};?>
          <tr>
            <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($t['subject'])?></td>
            <td><?=htmlspecialchars($t['first_name'])?></td>
            <td><span class="badge <?=$pc?>"><?=$t['priority']?></span></td>
            <td><a href="manage_tickets.php?id=<?=$t['id']?>" style="color:var(--red);font-size:12px;font-weight:600;text-decoration:none;">View →</a></td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        </div>
        <?php endif;?>
      </div>
    </div>

  </div>
</div>

<script>
function tick(){document.getElementById('liveClock').textContent=new Date().toLocaleDateString('en-MY',{weekday:'long',day:'numeric',month:'long',year:'numeric'})+' · '+new Date().toLocaleTimeString('en-MY',{hour:'2-digit',minute:'2-digit',second:'2-digit'});}
tick();setInterval(tick,1000);
</script>
</body>
</html>