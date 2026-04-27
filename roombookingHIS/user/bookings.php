<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';
$activePage = 'bookings';

$user_id    = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$username   = $_SESSION['username'];

// Handle messages
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

// Fetch all active rooms for the booking form
$rooms_res = $conn->query("SELECT * FROM rooms WHERE is_active=1 ORDER BY category, room_name");
$rooms = [];
while ($r = $rooms_res->fetch_assoc()) $rooms[] = $r;

// Fetch MY bookings (upcoming first, then past)
$stmt = $conn->prepare("
    SELECT b.*, r.room_name, r.category
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ? AND b.status != 'cancelled'
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings — HIS Room Booking</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
  <header class="topbar">
    <div class="topbar-left">
      <div>
        <p class="topbar-title">Bookings</p>
        <p class="topbar-date"><?= date('l, j F Y') ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="user-avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
      <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </header>

  <div class="page-body">

    <?php if ($success === 'booked'): ?>
    <div class="alert alert-success">✅ Room booked successfully!</div>
    <?php elseif ($success === 'cancelled'): ?>
    <div class="alert alert-success">✅ Booking cancelled.</div>
    <?php elseif ($error === 'conflict'): ?>
    <div class="alert alert-error">⚠️ That time slot is already booked. Please choose another.</div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;">

      <!-- ── ADD BOOKING FORM ── -->
      <div class="card">
        <p class="card-title">➕ Add Booking</p>
        <form method="POST" action="booking_handler.php">

          <div class="form-group">
            <label>Room / Resource</label>
            <select name="room_id" required>
              <option value="">— Select a room —</option>
              <?php
              $lastCat = '';
              foreach ($rooms as $room):
                $cat = ucwords(str_replace('_',' ', $room['category']));
                if ($cat !== $lastCat):
                  if ($lastCat) echo '</optgroup>';
                  echo "<optgroup label=\"$cat\">";
                  $lastCat = $cat;
                endif;
              ?>
              <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
              <?php endforeach; if ($lastCat) echo '</optgroup>'; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Title / Purpose</label>
            <input type="text" name="title" placeholder="e.g. Department Meeting" required>
          </div>

          <div class="form-group">
            <label>User</label>
            <input type="text" value="<?= htmlspecialchars($first_name . ' ' . ($_SESSION['last_name'] ?? '')) ?>" readonly style="background:#f5f5f5;color:#888;">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="booking_date" min="<?= $today ?>" value="<?= $today ?>" required>
            </div>
            <div class="form-group"><!-- spacer --></div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Start Time</label>
              <select name="start_time" required>
                <?php
                for ($h = 7; $h <= 17; $h++) {
                  foreach (['00','30'] as $m) {
                    if ($h == 17 && $m == '30') break;
                    $val = sprintf('%02d:%s:00', $h, $m);
                    $lbl = date('g:i A', strtotime("$h:$m"));
                    echo "<option value=\"$val\">$lbl</option>";
                  }
                }
                ?>
              </select>
            </div>
            <div class="form-group">
              <label>End Time</label>
              <select name="end_time" required>
                <?php
                for ($h = 7; $h <= 17; $h++) {
                  foreach (['30','00'] as $idx => $m) {
                    $hh = ($idx === 1) ? $h + 1 : $h;
                    if ($hh > 17 || ($hh == 17 && $m == '30')) { echo "<option value=\"17:30:00\">5:30 PM</option>"; break 2; }
                    $val = sprintf('%02d:%s:00', $hh, $m);
                    $lbl = date('g:i A', strtotime("$hh:$m"));
                    echo "<option value=\"$val\">$lbl</option>";
                  }
                }
                ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Notes (optional)</label>
            <textarea name="notes" placeholder="Any additional notes..."></textarea>
          </div>

          <div class="form-group" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="is_recurring" id="recurCheck" value="1" style="width:auto;">
            <label for="recurCheck" style="text-transform:none;font-size:13px;margin:0;">Recurring booking</label>
          </div>
          <div id="recurOptions" style="display:none;">
            <div class="form-group">
              <label>Repeat for how many days?</label>
              <input type="number" name="recur_days" min="1" max="60" placeholder="e.g. 5 (weekdays only)">
            </div>
          </div>

          <button type="submit" class="btn-primary" style="width:100%;">📅 Book Room</button>
        </form>
      </div>

      <!-- ── MY BOOKINGS LIST ── -->
      <div class="card">
        <p class="card-title">📋 Your Bookings</p>
        <?php if (empty($my_bookings)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <h3>No bookings yet</h3>
          <p>Use the form on the left to make your first booking.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Room</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($my_bookings as $b):
                $isPast = $b['booking_date'] < $today;
                $statusClass = $isPast ? 'badge-grey' : 'badge-green';
                $statusLabel = $isPast ? 'Past' : ucfirst($b['status']);
              ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($b['room_name']) ?></strong><br>
                  <span style="font-size:11px;color:var(--grey);"><?= htmlspecialchars($b['title'] ?? '') ?></span>
                </td>
                <td><?= date('d M Y', strtotime($b['booking_date'])) ?></td>
                <td><?= substr($b['start_time'],0,5) ?> – <?= substr($b['end_time'],0,5) ?></td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                <td>
                  <?php if (!$isPast && $b['status'] !== 'cancelled'): ?>
                  <a href="cancel_booking.php?id=<?= $b['id'] ?>"
                     onclick="return confirm('Cancel this booking?')"
                     style="font-size:12px;color:var(--red);text-decoration:none;">Cancel</a>
                  <?php endif; ?>
                </td>
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

<!-- FEEDBACK MODAL -->
<div class="modal-overlay" id="feedbackModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('feedbackModal')">×</button>
    <div class="modal-icon">💬</div>
    <h3>Send Feedback</h3>
    <p class="modal-desc">We'd love to hear your comments — report a bug, request a feature, or ask anything.</p>
    <form method="POST" action="feedback_handler.php">
      <textarea name="message" placeholder="Write your message here..." required></textarea>
      <button type="submit" class="btn-primary" style="width:100%;">Send Feedback</button>
    </form>
  </div>
</div>

<script>
document.getElementById('recurCheck').addEventListener('change', function(){
  document.getElementById('recurOptions').style.display = this.checked ? 'block' : 'none';
});
function openFeedback(e){ e.preventDefault(); document.getElementById('feedbackModal').classList.add('active'); }
function closeModal(id){ document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(el=>{
  el.addEventListener('click',e=>{ if(e.target===el) el.classList.remove('active'); });
});
</script>
</body>
</html>