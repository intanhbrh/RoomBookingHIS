<?php
require_once '../includes/auth.php'; requireAdmin();
require_once '../includes/db.php';

if (isset($_GET['approve'])) {
    $id=intval($_GET['approve']);
    $tr=$conn->query("SELECT * FROM transfer_requests WHERE id=$id")->fetch_assoc();
    if ($tr && $tr['status']==='pending') {
        $conn->query("UPDATE bookings SET user_id={$tr['from_user_id']} WHERE id={$tr['booking_id']}");
        $conn->query("UPDATE transfer_requests SET status='accepted' WHERE id=$id");
    }
    header("Location: manage_transfers.php?success=approved"); exit();
}
if (isset($_GET['decline'])) {
    $id=intval($_GET['decline']);
    $conn->query("UPDATE transfer_requests SET status='declined' WHERE id=$id");
    header("Location: manage_transfers.php?success=declined"); exit();
}

$transfers=$conn->query("
    SELECT tr.*,
           uf.first_name as from_fn,uf.last_name as from_ln,
           ut.first_name as to_fn,  ut.last_name as to_ln,
           b.booking_date,b.start_time,b.end_time,b.title,r.room_name
    FROM transfer_requests tr
    JOIN users uf ON tr.from_user_id=uf.id
    JOIN users ut ON tr.to_user_id=ut.id
    JOIN bookings b ON tr.booking_id=b.id
    JOIN rooms r ON b.room_id=r.id
    ORDER BY FIELD(tr.status,'pending','accepted','declined'),tr.requested_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Transfer Requests — Admin</title><link rel="icon" type="image/png" href="../assets/img/help-logo.png"><link rel="stylesheet" href="../assets/css/style.css"></head><body>
<?php include 'admin_sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left"><div><p class="topbar-title">Transfer Requests</p></div></div>
    <div class="topbar-right"><span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span></div>
  </header>
  <div class="page-body">
    <?php if(isset($_GET['success'])):?><div class="alert alert-success">✅ Transfer <?=$_GET['success']?>.</div><?php endif;?>
    <div class="card">
      <p class="card-title">🔁 All Transfer Requests</p>
      <?php if(empty($transfers)):?>
      <div class="empty-state"><div class="empty-icon">✅</div><h3>No transfer requests</h3></div>
      <?php else:?>
      <div class="table-wrap"><table>
        <thead><tr><th>Requested By</th><th>From</th><th>Room</th><th>Date & Time</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($transfers as $tr):
          $sc=match($tr['status']){'pending'=>'badge-yellow','accepted'=>'badge-green',default=>'badge-grey'};?>
        <tr>
          <td><?=htmlspecialchars($tr['from_fn'].' '.$tr['from_ln'])?></td>
          <td><?=htmlspecialchars($tr['to_fn'].' '.$tr['to_ln'])?></td>
          <td><?=htmlspecialchars($tr['room_name'])?></td>
          <td><?=date('d M Y',strtotime($tr['booking_date']))?><br><span style="font-size:11px;color:var(--grey);"><?=substr($tr['start_time'],0,5)?> – <?=substr($tr['end_time'],0,5)?></span></td>
          <td style="font-size:12px;color:var(--grey);max-width:160px;"><?=htmlspecialchars($tr['message']??'—')?></td>
          <td><span class="badge <?=$sc?>"><?=ucfirst($tr['status'])?></span></td>
          <td style="white-space:nowrap;">
            <?php if($tr['status']==='pending'):?>
            <a href="manage_transfers.php?approve=<?=$tr['id']?>" style="color:var(--green);font-size:12px;font-weight:600;text-decoration:none;" onclick="return confirm('Approve this transfer?')">✅ Approve</a>
            &nbsp;
            <a href="manage_transfers.php?decline=<?=$tr['id']?>" style="color:var(--red);font-size:12px;font-weight:600;text-decoration:none;" onclick="return confirm('Decline this transfer?')">❌ Decline</a>
            <?php endif;?>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table></div>
      <?php endif;?>
    </div>
  </div>
</div>
</body></html>