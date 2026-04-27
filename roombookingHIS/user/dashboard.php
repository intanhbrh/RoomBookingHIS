<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$first_name = $_SESSION['first_name'];
$username   = $_SESSION['username'];
$user_id    = $_SESSION['user_id'];
$today      = date('Y-m-d');
$view_date  = $_GET['date'] ?? $today;
$prev_date  = date('Y-m-d', strtotime($view_date . ' -1 day'));
$next_date  = date('Y-m-d', strtotime($view_date . ' +1 day'));
$display    = date('l, j F Y', strtotime($view_date));

// Fetch rooms
$rooms = $conn->query("SELECT * FROM rooms WHERE is_active=1 ORDER BY category, room_name")->fetch_all(MYSQLI_ASSOC);

// Fetch bookings for this date
$stmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, r.room_name, r.category
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.booking_date = ? AND b.status != 'cancelled'
");
$stmt->bind_param("s", $view_date);
$stmt->execute();
$all_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group bookings by room_id
$bookings = [];
foreach ($all_bookings as $b) {
    $bookings[$b['room_id']][] = $b;
}

// Fetch timetable for this day
$dow = date('D', strtotime($view_date)); // Mon,Tue...
$stmt2 = $conn->prepare("
    SELECT t.*, r.room_name
    FROM timetable t
    JOIN rooms r ON t.room_id = r.id
    WHERE t.day_of_week = ?
");
$stmt2->bind_param("s", $dow);
$stmt2->execute();
$all_tt = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$timetable = [];
foreach ($all_tt as $t) {
    $timetable[$t['room_id']][] = $t;
}

// Time slots 07:00 to 17:30
$slots = [];
for ($h = 7; $h <= 17; $h++) {
    $slots[] = sprintf('%02d:00', $h);
    if ($h < 17) $slots[] = sprintf('%02d:30', $h);
}
$slots[] = '17:30';

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Day View — HIS Room Booking</title>
<link rel="icon" type="image/png" href="../assets/img/help-logo.png">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.timetable-grid {
  display: grid;
  grid-template-columns: 70px repeat(<?= count($rooms) ?>, minmax(130px,1fr));
}
.now-line {
  position: absolute; left: 0; right: 0;
  height: 2px; background: #C0001A; z-index: 6; pointer-events: none;
}
.now-line::before {
  content: ''; position: absolute;
  left: -4px; top: -4px;
  width: 10px; height: 10px;
  background: #C0001A; border-radius: 50%;
}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <div>
        <p class="topbar-title">Day View</p>
        <p class="topbar-date" id="liveClock"></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="user-avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
      <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </header>

  <div class="page-body">

    <!-- LEGEND -->
    <div class="legend-bar">
      <span style="font-size:11px;font-weight:600;color:var(--grey);text-transform:uppercase;letter-spacing:.08em;margin-right:4px;">Categories:</span>
      <span class="legend-pill event"><span class="dot"></span> Event Spaces</span>
      <span class="legend-pill meeting"><span class="dot"></span> Meeting Spaces</span>
      <span class="legend-pill timetabled"><span class="dot"></span> Timetabled Spaces</span>
      <div class="booking-legend">
        <span class="booking-chip"><span class="swatch mine"></span> My bookings</span>
        <span class="booking-chip"><span class="swatch others"></span> Other bookings</span>
        <span class="booking-chip"><span class="swatch timetable"></span> Timetabled</span>
      </div>
    </div>

    <!-- DATE NAV -->
    <div class="date-nav">
      <a href="dashboard.php?date=<?= $prev_date ?>" class="date-nav-btn">&#8249;</a>
      <div>
        <div class="date-nav-title"><?= $display ?></div>
        <?php if ($view_date === $today): ?>
        <div class="date-nav-sub">Today</div>
        <?php endif; ?>
      </div>
      <a href="dashboard.php?date=<?= $next_date ?>" class="date-nav-btn">&#8250;</a>
      <?php if ($view_date !== $today): ?>
      <a href="dashboard.php" class="btn-today">Today</a>
      <?php endif; ?>
    </div>

    <!-- TIMETABLE -->
    <div class="timetable-wrapper">
      <div class="timetable-scroll" id="timetableScroll">
        <div class="timetable-grid" id="grid">

          <!-- HEADER -->
          <div class="grid-time-header">TIME</div>
          <?php foreach ($rooms as $room): ?>
          <div class="grid-room-header">
            <div class="room-header-name"><?= htmlspecialchars($room['room_name']) ?></div>
            <div class="room-header-cat"><?= ucwords(str_replace('_',' ',$room['category'])) ?></div>
          </div>
          <?php endforeach; ?>

          <!-- ROWS -->
          <?php foreach ($slots as $slot):
            $isHour = substr($slot, 3) === '00';
            $label  = $isHour ? date('g A', strtotime($slot)) : '';
          ?>
          <div class="time-slot <?= $isHour ? 'hour-mark' : '' ?>"><?= $label ?></div>
          <?php foreach ($rooms as $room): ?>
          <div class="grid-cell"
               data-room="<?= $room['id'] ?>"
               data-time="<?= $slot ?>"
               data-date="<?= $view_date ?>"
               onclick="cellClick(this)">
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>

        </div>
      </div>
    </div>

  </div>
</div>

<!-- FAB -->
<a href="bookings.php" class="fab" title="Add Booking">+</a>

<!-- TRANSFER MODAL -->
<div class="modal-overlay" id="transferModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('transferModal')">×</button>
    <div class="modal-icon">🔁</div>
    <h3>Transfer Booking Request</h3>
    <p class="modal-desc" id="transferDesc"></p>
    <div class="transfer-meta" id="transferMeta"></div>
    <form method="POST" action="transfer_handler.php">
      <input type="hidden" name="booking_id" id="t_booking_id">
      <input type="hidden" name="to_user_id" id="t_to_user">
      <div class="form-group">
        <label>Message (optional)</label>
        <textarea name="message" placeholder="Add a message to the booking owner..." rows="3"></textarea>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="closeModal('transferModal')">Cancel</button>
        <button type="submit" class="btn-primary">Send Transfer Request</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD BOOKING MODAL -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('addModal')">×</button>
    <div class="modal-icon">📅</div>
    <h3>Add Booking</h3>
    <form method="POST" action="booking_handler.php">
      <input type="hidden" name="room_id"      id="a_room">
      <input type="hidden" name="booking_date" id="a_date">
      <input type="hidden" name="start_time"   id="a_start">
      <div class="transfer-meta" id="addMeta" style="margin-bottom:16px;"></div>
      <div class="form-group">
        <label>Title / Purpose</label>
        <input type="text" name="title" placeholder="e.g. Meeting.." required>
      </div>
      <div class="form-group">
        <label>End Time</label>
        <select name="end_time" id="a_end_sel" required></select>
      </div>
      <div class="form-group">
        <label>Notes (optional)</label>
        <textarea name="notes" rows="2" placeholder="Any additional notes..."></textarea>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn-primary">Book</button>
      </div>
    </form>
  </div>
</div>

<!-- TIMETABLE CLASS MODAL -->
<div class="modal-overlay" id="ttModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('ttModal')">×</button>
    <div class="modal-icon">📚</div>
    <h3 id="tt_name">Timetabled Class</h3>
    <div class="transfer-meta" id="tt_meta"></div>
    <div class="btn-row">
      <button type="button" class="btn-secondary" onclick="closeModal('ttModal')">Close</button>
    </div>
  </div>
</div>

<!-- FEEDBACK MODAL -->
<div class="modal-overlay" id="feedbackModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('feedbackModal')">×</button>
    <div class="modal-icon">💬</div>
    <h3>Send Feedback</h3>
    <p class="modal-desc">We'd love to hear your comments, report a bug, or request a new feature.</p>
    <form method="POST" action="feedback_handler.php">
      <textarea name="message" placeholder="Write your message here..." required></textarea>
      <button type="submit" class="btn-primary" style="width:100%;margin-top:4px;">Send Feedback</button>
    </form>
  </div>
</div>

<script>
// ── DATA FROM PHP ──────────────────────────────────────
const BOOKINGS   = <?= json_encode($bookings) ?>;
const TIMETABLE  = <?= json_encode($timetable) ?>;
const ROOMS      = <?= json_encode(array_column($rooms, null, 'id')) ?>;
const MY_ID      = <?= (int)$user_id ?>;
const VIEW_DATE  = '<?= $view_date ?>';
const TODAY      = '<?= $today ?>';
const SLOT_H     = 46;
const GRID_START = 7 * 60; // 07:00 in minutes

// ── HELPERS ───────────────────────────────────────────
function toMins(t) {
  const [h, m] = t.split(':').map(Number);
  return h * 60 + m;
}
function fmtTime(t) {
  const [h, m] = t.split(':');
  const hh = parseInt(h);
  const ampm = hh >= 12 ? 'PM' : 'AM';
  const h12  = hh > 12 ? hh - 12 : (hh === 0 ? 12 : hh);
  return `${h12}:${m} ${ampm}`;
}

// ── RENDER BOOKING BLOCKS ─────────────────────────────
function renderBlocks() {
  document.querySelectorAll('.booking-block, .now-line').forEach(e => e.remove());

  // Get first cell per room to use as anchor
  const firstCells = {};
  document.querySelectorAll('.grid-cell').forEach(cell => {
    const rid = cell.dataset.room;
    if (!firstCells[rid]) firstCells[rid] = cell;
  });

  Object.entries(firstCells).forEach(([roomId, cell]) => {
    cell.style.position = 'relative';

    // Bookings
    (BOOKINGS[roomId] || []).forEach(b => {
      const isMe = parseInt(b.user_id) === MY_ID;
      addBlock(cell, {
        start: b.start_time, end: b.end_time,
        label: b.first_name + ' ' + b.last_name,
        type: isMe ? 'mine' : 'others',
        onclick: () => isMe ? null : openTransfer(b)
      });
    });

    // Timetable
    (TIMETABLE[roomId] || []).forEach(t => {
      addBlock(cell, {
        start: t.start_time, end: t.end_time,
        label: t.class_name || 'Class',
        type: 'timetabled',
        onclick: () => openTT(t)
      });
    });
  });

  // Now line
  if (VIEW_DATE === TODAY) {
    const now = new Date();
    const mins = now.getHours() * 60 + now.getMinutes();
    if (mins >= GRID_START && mins <= GRID_START + 21 * 30) {
      const top = ((mins - GRID_START) / 30) * SLOT_H;
      Object.values(firstCells).forEach(cell => {
        const line = document.createElement('div');
        line.className = 'now-line';
        line.style.top = top + 'px';
        cell.appendChild(line);
      });
    }
  }
}

function addBlock(anchorCell, {start, end, label, type, onclick}) {
  const startM = toMins(start);
  const endM   = toMins(end);
  const top    = ((startM - GRID_START) / 30) * SLOT_H;
  const height = Math.max(((endM - startM) / 30) * SLOT_H, 22);

  const el = document.createElement('div');
  el.className = 'booking-block ' + type;
  el.style.cssText = `top:${top}px;height:${height}px;pointer-events:auto;`;
  el.innerHTML = `<div class="block-name">${label}</div><div class="block-time">${fmtTime(start)} – ${fmtTime(end)}</div>`;
  el.addEventListener('click', e => { e.stopPropagation(); onclick && onclick(); });
  anchorCell.appendChild(el);
}

// ── CELL CLICK → ADD BOOKING ──────────────────────────
function cellClick(cell) {
  const roomId = cell.dataset.room;
  const time   = cell.dataset.time;
  const date   = cell.dataset.date;
  const room   = ROOMS[roomId];
  if (!room) return;

  document.getElementById('a_room').value  = roomId;
  document.getElementById('a_date').value  = date;
  document.getElementById('a_start').value = time + ':00';

  document.getElementById('addMeta').innerHTML = `
    <span class="meta-key">Room</span><span class="meta-val">${room.room_name}</span>
    <span class="meta-key">Date</span><span class="meta-val">${new Date(date + 'T00:00').toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'})}</span>
    <span class="meta-key">Start</span><span class="meta-val">${fmtTime(time + ':00')}</span>
  `;

  // Populate end times
  const sel = document.getElementById('a_end_sel');
  sel.innerHTML = '';
  const startM = toMins(time + ':00');
  for (let m = startM + 30; m <= 17*60+30; m += 30) {
    const hh = String(Math.floor(m/60)).padStart(2,'0');
    const mm = String(m % 60).padStart(2,'0');
    const opt = document.createElement('option');
    opt.value = `${hh}:${mm}:00`;
    opt.textContent = fmtTime(`${hh}:${mm}`);
    sel.appendChild(opt);
  }
  openModal('addModal');
}

// ── TRANSFER MODAL ────────────────────────────────────
function openTransfer(b) {
  document.getElementById('t_booking_id').value = b.id;
  document.getElementById('t_to_user').value    = b.user_id;
  document.getElementById('transferDesc').textContent =
    `Send a message to ${b.first_name} ${b.last_name} requesting a transfer of this booking to you.`;
  document.getElementById('transferMeta').innerHTML = `
    <span class="meta-key">Date</span><span class="meta-val">${b.booking_date}</span>
    <span class="meta-key">Time</span><span class="meta-val">${b.start_time.slice(0,5)} – ${b.end_time.slice(0,5)}</span>
    <span class="meta-key">Room</span><span class="meta-val">${b.room_name}</span>
  `;
  openModal('transferModal');
}

// ── TIMETABLE MODAL ───────────────────────────────────
function openTT(t) {
  document.getElementById('tt_name').textContent = t.class_name || 'Timetabled Class';
  document.getElementById('tt_meta').innerHTML = `
    <span class="meta-key">Teacher</span><span class="meta-val">${t.teacher_name || '—'}</span>
    <span class="meta-key">Time</span><span class="meta-val">${t.start_time.slice(0,5)} – ${t.end_time.slice(0,5)}</span>
    <span class="meta-key">Room</span><span class="meta-val">${t.room_name}</span>
  `;
  openModal('ttModal');
}

// ── MODAL HELPERS ─────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function openFeedback(e){ e.preventDefault(); openModal('feedbackModal'); }

document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active')
    .forEach(el => el.classList.remove('active'));
});

// ── LIVE CLOCK ────────────────────────────────────────
function tick() {
  const now = new Date();
  document.getElementById('liveClock').textContent =
    now.toLocaleDateString('en-MY',{weekday:'long',day:'numeric',month:'long',year:'numeric'}) +
    ' · ' + now.toLocaleTimeString('en-MY',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
tick(); setInterval(tick, 1000);

// ── INIT ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  renderBlocks();
  // Scroll to current time
  const now = new Date();
  const mins = now.getHours() * 60 + now.getMinutes();
  const top  = ((mins - GRID_START) / 30) * SLOT_H;
  document.getElementById('timetableScroll').scrollTop = Math.max(0, top - 100);
});
</script>
</body>
</html>