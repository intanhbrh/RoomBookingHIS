<?php
require_once '../includes/auth.php'; requireAdmin();
require_once '../includes/db.php';

if (isset($_GET['status']) && isset($_GET['id'])) {
    $id=intval($_GET['id']); $st=$_GET['status'];
    $allowed=['Open','In Progress','Resolved','Closed'];
    if (in_array($st,$allowed)) $conn->query("UPDATE tickets SET status='$st' WHERE id=$id");
    header("Location: manage_tickets.php"); exit();
}
$tickets=$conn->query("SELECT t.*,u.first_name,u.last_name FROM tickets t JOIN users u ON t.user_id=u.id ORDER BY FIELD(t.status,'Open','In Progress','Resolved','Closed'),t.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Helpdesk — Admin</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css"></head><body>
<?php include 'admin_sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
        <div><p class="topbar-title">Helpdesk Tickets</p></div></div>
    <div class="topbar-right"
    ><span style="background:rgba(192,0,26,.12);border:1px solid rgba(192,0,26,.3);border-radius:100px;padding:3px 10px;font-size:11px;font-weight:600;color:var(--red);">🔐 Admin</span></div>
  </header>
  
  <div class="page-body">
    <div class="card">
      <p class="card-title">🎫 All Tickets</p>
      <?php if(empty($tickets)):?>
      <div class="empty-state"><div class="empty-icon">🎉</div><h3>No tickets</h3></div>
      <?php else:?>
      <div class="table-wrap"><table>
        <thead><tr><th>Subject</th><th>By</th><th>Dept</th><th>Priority</th><th>Risk</th><th>Status</th><th>Date</th><th>Update Status</th></tr></thead>
        <tbody>
        <?php foreach($tickets as $t):
          $pc=match($t['priority']){'Emergency','High'=>'badge-red','Medium'=>'badge-yellow',default=>'badge-grey'};
          $sc=match($t['status']){'Open'=>'badge-blue','In Progress'=>'badge-yellow','Resolved'=>'badge-green',default=>'badge-grey'};
        ?>
        <tr>
          <td><strong><?=htmlspecialchars($t['subject'])?></strong>
          <?php if($t['description']):?><br><span style="font-size:11px;color:var(--grey);">
            <?=htmlspecialchars(substr($t['description'],0,60)).(strlen($t['description'])>60?'...':'')?></span><?php endif;?></td>
          
            <td><?=htmlspecialchars($t['first_name'].' '.$t['last_name'])?></td>
          <td><?=$t['department']?></td>
          <td><span class="badge <?=$pc?>"><?=$t['priority']?></span></td>
          <td><span class="badge <?=$t['risk']==='High'?'badge-red':($t['risk']==='Medium'?'badge-yellow':'badge-grey')?>"><?=$t['risk']?></span></td>
          <td><span class="badge <?=$sc?>"><?=$t['status']?></span></td>
          <td><?=date('d M',strtotime($t['created_at']))?></td>
          <td style="white-space:nowrap;">

            <?php foreach(['Open','In Progress','Resolved','Closed'] as $s):
              if($s!==$t['status']):?>
              <a href="manage_tickets.php?id=<?=$t['id']?>&status=<?=urlencode($s)?>"
               style="font-size:11px;color:var(--grey-dark);text-decoration:none;margin-right:6px;"><?=$s?></a>
            <?php endif; endforeach;?>
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