<?php
require_once '../includes/auth.php'; requireAdmin();
require_once '../includes/db.php';

$monthly=$conn->query("SELECT DATE_FORMAT(booking_date,'%b %Y') as m, DATE_FORMAT(booking_date,'%Y-%m') as ym, COUNT(*) as cnt FROM bookings WHERE status='confirmed' AND booking_date >= DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym ASC")->fetch_all(MYSQLI_ASSOC);
$by_room=$conn->query("SELECT r.room_name,r.category,COUNT(b.id) as cnt FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.status='confirmed' GROUP BY b.room_id ORDER BY cnt DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$by_user=$conn->query("SELECT u.first_name,u.last_name,u.email,COUNT(b.id) as cnt FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.status='confirmed' GROUP BY b.user_id ORDER BY cnt DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$by_setup=$conn->query("SELECT COALESCE(setup_type,'not set') as setup, COUNT(*) as cnt FROM bookings WHERE status='confirmed' GROUP BY setup_type ORDER BY cnt DESC")->fetch_all(MYSQLI_ASSOC);
$total=$conn->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetch_row()[0];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Reports — Admin</title><link rel="icon" type="image/png" href="../assets/img/help-logo.png"><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<?php include 'admin_sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left"><div><p class="topbar-title">Reports & Analytics</p></div></div>
    <div class="topbar-right"><span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span></div>
  </header>
  <div class="page-body">
    <div class="stat-grid" style="margin-bottom:24px;">
      <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-val"><?=$total?></div><div class="stat-label">Total Confirmed Bookings</div></div>
      <div class="stat-card"><div class="stat-icon">🚪</div><div class="stat-val"><?=count($by_room)?></div><div class="stat-label">Rooms with bookings</div></div>
      <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-val"><?=count($by_user)?></div><div class="stat-label">Active staff bookers</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
      <div class="card">
        <p class="card-title">📈 Monthly Bookings (Last 6 Months)</p>
        <?php $max=max(array_column($monthly,'cnt')?:[1]);
        foreach($monthly as $i=>$m): $pct=round(($m['cnt']/$max)*100);?>
        <div style="margin-bottom:12px;">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
            <span><?=$m['m']?></span><strong style="color:var(--red);"><?=$m['cnt']?></strong>
          </div>
          <div style="height:8px;background:#f0ede8;border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:<?=$pct?>%;background:var(--red);border-radius:4px;transition:width .5s <?=$i*.1?>s;"></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <div class="card">
        <p class="card-title">🪑 Bookings by Setup Type</p>
        <?php $ms=max(array_column($by_setup,'cnt')?:[1]);
        foreach($by_setup as $s): $p=round(($s['cnt']/$ms)*100);?>
        <div style="margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:3px;">
            <span><?=ucwords(str_replace('_',' ',$s['setup']))?></span><strong><?=$s['cnt']?></strong>
          </div>
          <div style="height:6px;background:#f0ede8;border-radius:3px;overflow:hidden;">
            <div style="height:100%;width:<?=$p?>%;background:var(--red);border-radius:3px;"></div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
      <div class="card">
        <p class="card-title">🏆 Top Booked Rooms</p>
        <div class="table-wrap"><table>
          <thead><tr><th>#</th><th>Room</th><th>Category</th><th>Bookings</th></tr></thead>
          <tbody><?php foreach($by_room as $i=>$r):?>
          <tr><td style="font-weight:700;color:var(--grey);"><?=$i+1?></td><td><?=htmlspecialchars($r['room_name'])?></td><td><span style="font-size:11px;color:var(--grey);"><?=ucwords(str_replace('_',' ',$r['category']))?></span></td><td><span class="badge badge-green"><?=$r['cnt']?></span></td></tr>
          <?php endforeach;?></tbody>
        </table></div>
      </div>
      <div class="card">
        <p class="card-title">🏅 Most Active Staff</p>
        <div class="table-wrap"><table>
          <thead><tr><th>#</th><th>Name</th><th>Bookings</th></tr></thead>
          <tbody><?php foreach($by_user as $i=>$u):?>
          <tr><td style="font-weight:700;color:var(--grey);"><?=$i+1?></td><td><strong><?=htmlspecialchars($u['first_name'].' '.$u['last_name'])?></strong><br><span style="font-size:11px;color:var(--grey);"><?=htmlspecialchars($u['email'])?></span></td><td><span class="badge badge-red"><?=$u['cnt']?></span></td></tr>
          <?php endforeach;?></tbody>
        </table></div>
      </div>
    </div>
  </div>
</div>
</body></html>