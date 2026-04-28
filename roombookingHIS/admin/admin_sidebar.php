<?php
// admin/admin_sidebar.php
$open_tickets    = $conn->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetch_row()[0] ?? 0;
$pending_transfer= $conn->query("SELECT COUNT(*) FROM transfer_requests WHERE status='pending'")->fetch_row()[0] ?? 0;
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" style="background:#111;">
  <div class="sidebar-brand">
    <div class="brand-logo"><span>HIS</span></div>
    <div class="brand-text">
      <p class="brand-title">Admin Panel</p>
      <p class="brand-sub">Room Booking System</p>
    </div>
  </div>
  <nav class="sidebar-nav">
    <p class="nav-section-label">Overview</p>
    <a href="dashboard.php"       class="nav-item <?=$current==='dashboard.php'?'active':''?>"><span class="nav-icon">📊</span><span class="nav-label">Dashboard</span></a>
    <a href="all_bookings.php"    class="nav-item <?=$current==='all_bookings.php'?'active':''?>"><span class="nav-icon">📅</span><span class="nav-label">All Bookings</span></a>
    <p class="nav-section-label">Manage</p>
    <a href="manage_rooms.php"    class="nav-item <?=$current==='manage_rooms.php'?'active':''?>"><span class="nav-icon">🚪</span><span class="nav-label">Rooms</span></a>
    <a href="manage_users.php"    class="nav-item <?=$current==='manage_users.php'?'active':''?>"><span class="nav-icon">👥</span><span class="nav-label">Users</span></a>
    <a href="manage_tickets.php"  class="nav-item <?=$current==='manage_tickets.php'?'active':''?>">
      <span class="nav-icon">🎫</span><span class="nav-label">Helpdesk</span>
      <?php if($open_tickets>0):?><span class="nav-badge"><?=$open_tickets?></span><?php endif;?>
    </a>
    <a href="manage_transfers.php" class="nav-item <?=$current==='manage_transfers.php'?'active':''?>">
      <span class="nav-icon">🔁</span><span class="nav-label">Transfers</span>
      <?php if($pending_transfer>0):?><span class="nav-badge"><?=$pending_transfer?></span><?php endif;?>
    </a>
    <a href="reports.php"         class="nav-item <?=$current==='reports.php'?'active':''?>"><span class="nav-icon">📈</span><span class="nav-label">Reports</span></a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../index.php" class="nav-item"><span class="nav-icon">👤</span><span class="nav-label">User Login</span></a>
    <a href="../logout.php" class="nav-item nav-signout"><span class="nav-icon">🚪</span><span class="nav-label">Sign Out</span></a>
  </div>
</aside>