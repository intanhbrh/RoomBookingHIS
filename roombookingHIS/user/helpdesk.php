<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$activePage = 'helpdesk';
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

$success = $_GET['success'] ?? '';

// Fetch my tickets
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Helpdesk — HIS Room Booking</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <div><p class="topbar-title">Helpdesk</p><p class="topbar-date"><?= date('l, j F Y') ?></p></div>
    </div>
    <div class="topbar-right">
      <div class="user-avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
      <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </header>

  <div class="page-body">
    <div class="page-header">
      <h1>Helpdesk</h1>
      <p>Submit tickets to notify us of any issues with rooms, resources, or anything else. Track your tickets below.</p>
    </div>

    <?php if ($success === 'ticket_submitted'): ?>
    <div class="alert alert-success">✅ Your ticket has been submitted successfully!</div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:22px;align-items:start;">

      <!-- ADD TICKET FORM -->
      <div class="card">
        <p class="card-title">🎫 Add New Ticket</p>
        <form method="POST" action="ticket_handler.php">

          <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" placeholder="Brief description of the issue" required>
          </div>

          <div class="form-group">
            <label>Department</label>
            <select name="department" required>
              <option value="">Please Select</option>
              <option value="Facilities">Facilities</option>
              <option value="IT">IT</option>
              <option value="Admin">Admin</option>
            </select>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Please describe the issue in detail..." rows="4" required></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Priority</label>
              <select name="priority" required>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
                <option value="Emergency">Emergency</option>
              </select>
            </div>
            <div class="form-group">
              <label>Risk</label>
              <select name="risk" required>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
              </select>
            </div>
          </div>

          <button type="submit" class="btn-primary" style="width:100%;">🎫 Add New Ticket</button>
        </form>
      </div>

      <!-- MY TICKETS -->
      <div class="card">
        <p class="card-title">📋 Your Tickets</p>
        <?php if (empty($tickets)): ?>
        <div class="empty-state">
          <div class="empty-icon">🎉</div>
          <h3>There are no tickets logged.</h3>
          <p>Use the form to submit a ticket if you have an issue.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Subject</th><th>Dept</th><th>Priority</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t):
              $priClass = match($t['priority']) {
                'Emergency' => 'badge-red',
                'High'      => 'badge-red',
                'Medium'    => 'badge-yellow',
                default     => 'badge-grey'
              };
              $statClass = match($t['status']) {
                'Open'        => 'badge-blue',
                'In Progress' => 'badge-yellow',
                'Resolved'    => 'badge-green',
                default       => 'badge-grey'
              };
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($t['subject']) ?></strong></td>
              <td><?= $t['department'] ?></td>
              <td><span class="badge <?= $priClass ?>"><?= $t['priority'] ?></span></td>
              <td><span class="badge <?= $statClass ?>"><?= $t['status'] ?></span></td>
              <td><?= date('d M', strtotime($t['created_at'])) ?></td>
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