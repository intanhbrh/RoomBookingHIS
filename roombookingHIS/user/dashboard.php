<?php
require_once '../includes/auth.php';
requirelogin();
require_once '../include/db.php';

$activePage ='dashboard';

//current user info 
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$username = $_SESSION['username'];

//date navigation 
$today = date('Y-m-d');
$view_date = isset($_GET['date']) ? $_GET['date'] : $today;

//validate date 
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $view_date)) $view_date = $today;

$prev_date = date('Y-m-d', strtotime($view_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($view_date . ' +1 day'));
$display_date = date('l, j F Y', strtotime($view_date));
$day_of_week  = date('D', strtotime($view_date)); // Mon, Tue...

// fetch bookings for this date
$stmt = $conn->prepare("
    SELECT b.*, u.first_name, u.last_name, u.username, r.room_name, r.category
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.booking_date = ? AND b.status != 'cancelled'
");
$stmt->bind_param("s", $view_date);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($b = $bookings_result->fetch_assoc()) {
    $bookings[$b['room_id']][] = $b;
}

// fetch timetable for this day 
$stmt2 = $conn->prepare("
    SELECT t.*, r.room_name, r.category
    FROM timetable t
    JOIN rooms r ON t.room_id = r.id
    WHERE t.day_of_week = ?
");
$stmt2->bind_param("s", $day_of_week);
$stmt2->execute();
$tt_result = $stmt2->get_result();
$timetable = [];
while ($t = $tt_result->fetch_assoc()) {
    $timetable[$t['room_id']][] = $t;
}
 
// Time slots: 07:00 to 17:30 in 30-min blocks
$time_slots = [];
$start_h = 7; $end_h = 17; $end_m = 30;
for ($h = $start_h; $h <= $end_h; $h++) {
    $time_slots[] = sprintf('%02d:00', $h);
    if ($h < $end_h || ($h == $end_h && $end_m >= 30)) {
        $time_slots[] = sprintf('%02d:30', $h);
    }
}

// helper: convert "hh:mm" to minutes since midnight 
function toMins($t) {
    [$h, $m] = explode(':', $t);
    return (int)$h * 60 + (int)$m;

}

//slot height in px (each 30 min slot = 48px)
$slot_h = 48;

// build indexed booking blocks per room for rendering 
// we'll render them as absolute positioned inside a relative column
function buildBlocks($room_id, $bookings, $timetable, $user_id, $time_slots, $slot_h) {
    $blocks = [];
    $grid_start = toMins('07:00');

    // bookings  
    if (!empty($bookings[$room_id])) {
        foreach ($bookings[$room_id] as $b) {
            $start_m = toMins($b['start_time']);
            $end_m   = toMins($b['end_time']);
            $top     = (($start_m - $grid_start) / 30) * $slot_h;
            $height  = (($end_m - $start_m) / 30) * $slot_h;
            $type    = ($b['user_id'] == $user_id) ? 'mine' : 'others';
            $blocks[] = [
                'top'    => $top,
                'height' => max($height, 24),
                'type'   => $type,
                'name'   => $b['first_name'] . ' ' . $b['last_name'],
                'start'  => date('H:i', strtotime($b['start_time'])),
                'end'    => date('H:i', strtotime($b['end_time'])),
                'booking_id' => $b['id'],
                'user_id'    => $b['user_id'],
                'username'   => $b['username'],
                'room_name'  => $b['room_name'],
                'booking_date' => $b['booking_date'],
                'is_timetable' => false,
            ];
        }
    }
    // Timetable
    if (!empty($timetable[$room_id])) {
        foreach ($timetable[$room_id] as $t) {
            $start_m = toMins($t['start_time']);
            $end_m   = toMins($t['end_time']);
            $top     = (($start_m - $grid_start) / 30) * $slot_h;
            $height  = (($end_m - $start_m) / 30) * $slot_h;
            $blocks[] = [
                'top'    => $top,
                'height' => max($height, 24),
                'type'   => 'timetabled',
                'name'   => $t['class_name'],
                'start'  => date('H:i', strtotime($t['start_time'])),
                'end'    => date('H:i', strtotime($t['end_time'])),
                'booking_id'   => null,
                'teacher'      => $t['teacher_name'],
                'is_timetable' => true,
            ];
        }
    }
    return $blocks;
}
 
// Column count for grid layout
$col_count = count($rooms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Day View — HIS Room Booking</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* Dashboard-specific grid */
.timetable-grid {
  display: grid;
  grid-template-columns: 70px repeat(<?= $col_count ?>, minmax(130px, 1fr));
}
.grid-col {
  position: relative;
}
/* Current time indicator */
.now-line {
  position: absolute;
  left: 0; right: 0;
  height: 2px;
  background: var(--red);
  z-index: 6;
  pointer-events: none;
}
.now-line::before {
  content: '';
  position: absolute;
  left: -4px; top: -4px;
  width: 10px; height: 10px;
  background: var(--red);
  border-radius: 50%;
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
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </header>
 
  <div class="page-body">
 
    <!-- LEGEND BAR -->
    <div class="legend-bar">
      <span class="legend-label">Categories:</span>
      <span class="legend-pill event"   onclick="toggleFilter('event')">
        <span class="dot"></span> Event Spaces
      </span>
      <span class="legend-pill meeting" onclick="toggleFilter('meeting')">
        <span class="dot"></span> Meeting Spaces
      </span>
      <span class="legend-pill timetabled" onclick="toggleFilter('timetabled')">
        <span class="dot"></span> Timetabled Spaces
      </span>
      <div class="booking-legend">
        <span class="booking-chip"><span class="swatch mine"></span> My bookings</span>
        <span class="booking-chip"><span class="swatch others"></span> Other bookings</span>
        <span class="booking-chip"><span class="swatch timetable"></span> Timetabled classes</span>
      </div>
    </div>
 
    <!-- DATE NAV -->
    <div class="date-nav">
      <a href="dashboard.php?date=<?= $prev_date ?>" class="date-nav-btn" title="Previous day">‹</a>
      <div>
        <div class="date-nav-title"><?= $display_date ?></div>
        <?php if ($view_date === $today): ?>
        <div class="date-nav-sub">Today</div>
        <?php endif; ?>
      </div>
      <a href="dashboard.php?date=<?= $next_date ?>" class="date-nav-btn" title="Next day">›</a>
      <?php if ($view_date !== $today): ?>
      <a href="dashboard.php" class="btn-today">Today</a>
      <?php endif; ?>
    </div>
 
    <!-- TIMETABLE -->
    <div class="timetable-wrapper">
      <div class="timetable-scroll" id="timetableScroll">
        <div class="timetable-grid" id="timetableGrid">
 
          <!-- HEADER ROW -->
          <div class="grid-time-header">Time</div>
          <?php foreach ($rooms as $room): ?>
          <div class="grid-room-header cat-<?= $room['category'] ?>">
            <div class="room-header-name"><?= htmlspecialchars($room['room_name']) ?></div>
            <div class="room-header-cat"><?= ucwords(str_replace('_', ' ', $room['category'])) ?></div>
          </div>
          <?php endforeach; ?>
 
          <!-- TIME ROWS -->
          <?php foreach ($time_slots as $i => $slot):
            $is_hour = (substr($slot, 3) === '00');
            $slot_label = $is_hour ? date('g A', strtotime($slot)) : '';
          ?>
 
          <div class="time-slot <?= $is_hour ? 'hour-mark' : '' ?>">
            <?= $slot_label ?>
          </div>
 
          <?php foreach ($rooms as $room):
            // Only render blocks on the :00 slots (we span them via JS/absolute pos)
          ?>
          <div class="grid-cell clickable cat-<?= $room['category'] ?>"
               data-room="<?= $room['id'] ?>"
               data-time="<?= $slot ?>"
               data-date="<?= $view_date ?>"
               onclick="cellClick(this)">
          </div>
          <?php endforeach; ?>
 
          <?php endforeach; ?>
 
        </div><!-- /timetable-grid -->
      </div><!-- /timetable-scroll -->
    </div><!-- /timetable-wrapper -->
 
  </div><!-- /page-body -->
</div><!-- /main-content -->
 
<!-- FAB: Add Booking -->
<a href="bookings.php?action=add" class="fab" title="Add Booking">+</a>
 
<!-- ══ TRANSFER REQUEST MODAL ══════════════════════ -->
<div class="modal-overlay" id="transferModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('transferModal')">×</button>
    <div class="modal-icon">🔁</div>
    <h3>Transfer Booking Request</h3>
    <p class="modal-desc" id="transferDesc">Send a transfer request for this booking.</p>
 
    <div class="transfer-meta" id="transferMeta"></div>
 
    <form method="POST" action="transfer_handler.php">
      <input type="hidden" name="booking_id" id="transfer_booking_id">
      <input type="hidden" name="to_user_id" id="transfer_to_user_id">
      <div class="form-group">
        <label>Message</label>
        <textarea name="message" placeholder="Optional message to the booking owner..." rows="3"></textarea>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="closeModal('transferModal')">Cancel</button>
        <button type="submit" class="btn-primary">
          <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
          Send Transfer Request
        </button>
      </div>
    </form>
  </div>
</div>
 
<!-- ══ TIMETABLE BLOCK MODAL (view timetabled class) ══ -->
<div class="modal-overlay" id="timetableModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('timetableModal')">×</button>
    <div class="modal-icon">📚</div>
    <h3 id="tt_className">Timetabled Class</h3>
    <div class="transfer-meta" id="tt_meta"></div>
    <div class="btn-row">
      <button type="button" class="btn-secondary" onclick="closeModal('timetableModal')">Close</button>
    </div>
  </div>
</div>
 
<!-- ══ ADD BOOKING QUICK MODAL ══════════════════════ -->
<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('addModal')">×</button>
    <div class="modal-icon">📅</div>
    <h3>Add Booking</h3>
    <form method="POST" action="booking_handler.php">
      <input type="hidden" name="room_id"      id="add_room_id">
      <input type="hidden" name="booking_date" id="add_date">
      <input type="hidden" name="start_time"   id="add_start">
 
      <div class="transfer-meta" id="add_meta" style="margin-bottom:18px;"></div>
 
      <div class="form-group">
        <label>Title / Purpose</label>
        <input type="text" name="title" placeholder="e.g. Department Meeting" required>
      </div>
      <div class="form-group">
        <label>End Time</label>
        <select name="end_time" id="add_end_select" required></select>
      </div>
      <div class="form-group">
        <label>Notes (optional)</label>
        <textarea name="notes" placeholder="Any additional notes..." rows="2"></textarea>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn-primary">Book</button>
      </div>
    </form>
  </div>
</div>
 
<script>
// ══ BOOKING DATA FROM PHP ═══════════════════════════
const BOOKINGS_DATA = <?= json_encode($bookings) ?>;
const TIMETABLE_DATA = <?= json_encode($timetable) ?>;
const ROOMS = <?= json_encode(array_column($rooms, null, 'id')) ?>;
const CURRENT_USER_ID = <?= $user_id ?>;
const VIEW_DATE = '<?= $view_date ?>';
const SLOT_H = 48;
const GRID_START_MINS = 7 * 60; // 07:00
 
function toMins(t) {
  const [h, m] = t.split(':').map(Number);
  return h * 60 + m;
}
function formatTime(t) {
  const [h, m] = t.split(':');
  const hh = parseInt(h);
  return `${hh > 12 ? hh-12 : hh}:${m} ${hh >= 12 ? 'PM' : 'AM'}`;
}
 
// ══ RENDER BOOKING BLOCKS ════════════════════════════
function renderBlocks() {
  // Remove old blocks
  document.querySelectorAll('.booking-block').forEach(el => el.remove());
  document.querySelectorAll('.now-line').forEach(el => el.remove());
 
  const grid = document.getElementById('timetableGrid');
  // Get all room column cells for each room
  // Cells are in rows — find first data cell for each room column to get a reference col
  const roomCols = {};
  document.querySelectorAll('.grid-cell').forEach(cell => {
    const rid = cell.dataset.room;
    if (!roomCols[rid]) roomCols[rid] = [];
    roomCols[rid].push(cell);
  });
 
  // For each room, create a relative container overlay
  Object.entries(roomCols).forEach(([roomId, cells]) => {
    if (!cells.length) return;
    const firstCell = cells[0];
    const lastCell  = cells[cells.length - 1];
 
    // Create overlay div spanning all cells in this column
    const overlay = document.createElement('div');
    overlay.className = `grid-col-overlay`;
    overlay.dataset.room = roomId;
    overlay.style.cssText = `
      position: absolute;
      pointer-events: none;
      top: 0; left: 0; right: 0;
      z-index: 3;
    `;
 
    // Position relative to timetable grid
    const gridRect   = grid.getBoundingClientRect();
    const firstRect  = firstCell.getBoundingClientRect();
 
    // Place overlay inside the first cell but spanning full column height
    firstCell.style.position = 'relative';
    firstCell.appendChild(overlay);
 
    // Render bookings
    const roomBookings = BOOKINGS_DATA[roomId] || [];
    roomBookings.forEach(b => {
      renderBlock(overlay, b, false);
    });
 
    // Render timetable
    const roomTT = TIMETABLE_DATA[roomId] || [];
    roomTT.forEach(t => {
      renderBlock(overlay, t, true);
    });
  });
 
  // Draw now-line if viewing today
  if (VIEW_DATE === '<?= $today ?>') {
    drawNowLine(roomCols);
  }
}
 
function renderBlock(container, data, isTimetable) {
  const startField = isTimetable ? data.start_time : data.start_time;
  const endField   = isTimetable ? data.end_time   : data.end_time;
 
  const startMins = toMins(startField);
  const endMins   = toMins(endField);
  const top    = ((startMins - GRID_START_MINS) / 30) * SLOT_H;
  const height = Math.max(((endMins - startMins) / 30) * SLOT_H, 20);
 
  const block = document.createElement('div');
  block.className = 'booking-block';
  block.style.cssText = `top:${top}px; height:${height}px; pointer-events:auto;`;
 
  let typeClass, name, timeStr;
 
  if (isTimetable) {
    typeClass = 'timetabled';
    name = data.class_name || 'Class';
    timeStr = `${formatTime(data.start_time)} – ${formatTime(data.end_time)}`;
    block.onclick = () => openTimetableModal(data);
  } else {
    const isMe = parseInt(data.user_id) === CURRENT_USER_ID;
    typeClass = isMe ? 'mine' : 'others';
    name = `${data.first_name} ${data.last_name}`;
    timeStr = `${formatTime(data.start_time)} – ${formatTime(data.end_time)}`;
    if (!isMe) {
      block.onclick = () => openTransferModal(data);
    } else {
      block.onclick = () => openMyBookingModal(data);
    }
  }
 
  block.classList.add(typeClass);
  block.innerHTML = `
    <div class="block-name">${name}</div>
    <div class="block-time">${timeStr}</div>
  `;
 
  container.appendChild(block);
}
 
function drawNowLine(roomCols) {
  const now = new Date();
  const nowMins = now.getHours() * 60 + now.getMinutes();
  if (nowMins < GRID_START_MINS || nowMins > GRID_START_MINS + (21 * 30)) return;
  const top = ((nowMins - GRID_START_MINS) / 30) * SLOT_H;
 
  Object.entries(roomCols).forEach(([roomId, cells]) => {
    if (!cells[0]) return;
    const line = document.createElement('div');
    line.className = 'now-line';
    line.style.top = top + 'px';
    cells[0].style.position = 'relative';
    cells[0].appendChild(line);
  });
}
 
// ══ MODALS ═══════════════════════════════════════════
function openTransferModal(b) {
  document.getElementById('transfer_booking_id').value = b.id;
  document.getElementById('transfer_to_user_id').value  = b.user_id;
  document.getElementById('transferDesc').textContent =
    `Send a message to ${b.first_name} ${b.last_name} requesting a transfer of this booking to you.`;
  document.getElementById('transferMeta').innerHTML = `
    <span class="meta-key">Date</span>
    <span class="meta-val">${new Date(b.booking_date).toLocaleDateString('en-GB', {day:'2-digit',month:'2-digit',year:'numeric'})}</span>
    <span class="meta-key">Time</span>
    <span class="meta-val">${b.start_time.slice(0,5)} – ${b.end_time.slice(0,5)}</span>
    <span class="meta-key">Room / Resource</span>
    <span class="meta-val">${b.room_name}</span>
  `;
  openModal('transferModal');
}
 
function openTimetableModal(t) {
  document.getElementById('tt_className').textContent = t.class_name || 'Timetabled Class';
  document.getElementById('tt_meta').innerHTML = `
    <span class="meta-key">Teacher</span>
    <span class="meta-val">${t.teacher_name || '—'}</span>
    <span class="meta-key">Time</span>
    <span class="meta-val">${t.start_time.slice(0,5)} – ${t.end_time.slice(0,5)}</span>
    <span class="meta-key">Room</span>
    <span class="meta-val">${t.room_name}</span>
  `;
  openModal('timetableModal');
}
 
function openMyBookingModal(b) {
  // For own bookings — redirect to edit page
  window.location.href = `bookings.php?edit=${b.id}`;
}
 
function cellClick(cell) {
  const roomId   = cell.dataset.room;
  const time     = cell.dataset.time;
  const date     = cell.dataset.date;
  const room     = ROOMS[roomId];
  if (!room) return;
 
  document.getElementById('add_room_id').value = roomId;
  document.getElementById('add_date').value    = date;
  document.getElementById('add_start').value   = time;
  document.getElementById('add_meta').innerHTML = `
    <span class="meta-key">Room</span>
    <span class="meta-val">${room.room_name}</span>
    <span class="meta-key">Date</span>
    <span class="meta-val">${new Date(date + 'T00:00:00').toLocaleDateString('en-GB', {weekday:'long', day:'numeric', month:'long', year:'numeric'})}</span>
    <span class="meta-key">Start Time</span>
    <span class="meta-val">${formatTime(time + ':00')}</span>
  `;
 
  // Populate end times (30-min increments after start, up to 17:30)
  const sel = document.getElementById('add_end_select');
  sel.innerHTML = '';
  const startMins = toMins(time + ':00');
  for (let m = startMins + 30; m <= 17*60+30; m += 30) {
    const hh = String(Math.floor(m/60)).padStart(2,'0');
    const mm = String(m % 60).padStart(2,'0');
    const opt = document.createElement('option');
    opt.value = `${hh}:${mm}`;
    opt.textContent = formatTime(`${hh}:${mm}`);
    sel.appendChild(opt);
  }
  openModal('addModal');
}
 
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
 
// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('active'); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active')
    .forEach(el => el.classList.remove('active'));
});
 
// ══ CATEGORY FILTER ══════════════════════════════════
const activeFilters = new Set(['event', 'meeting', 'timetabled']);
function toggleFilter(type) {
  const pill = document.querySelector(`.legend-pill.${type}`);
  if (activeFilters.has(type)) {
    activeFilters.delete(type);
    pill.classList.add('inactive');
  } else {
    activeFilters.add(type);
    pill.classList.remove('inactive');
  }
  // Show/hide columns
  const catMap = { event:'event_space', meeting:'meeting_space', timetabled:'timetabled_space' };
  document.querySelectorAll('.cat-event_space, .cat-meeting_space, .cat-timetabled_space').forEach(el => {
    const cat = [...el.classList].find(c => c.startsWith('cat-'))?.replace('cat-','');
    const filterKey = Object.keys(catMap).find(k => catMap[k] === cat);
    el.style.display = filterKey && !activeFilters.has(filterKey) ? 'none' : '';
  });
}
 
// ══ LIVE CLOCK ═══════════════════════════════════════
function updateClock() {
  const now = new Date();
  document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-MY', {
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  });
}
updateClock();
setInterval(updateClock, 1000);
 
// ══ FEEDBACK MODAL ════════════════════════════════════
function openFeedback(e) { e.preventDefault(); openModal('feedbackModal'); }
function closeFeedback()  { closeModal('feedbackModal'); }
 
// ══ SCROLL TO CURRENT TIME ═══════════════════════════
function scrollToNow() {
  const now = new Date();
  const mins = now.getHours() * 60 + now.getMinutes();
  if (mins < GRID_START_MINS) return;
  const top = ((mins - GRID_START_MINS) / 30) * SLOT_H;
  const scroller = document.getElementById('timetableScroll');
  scroller.scrollTop = Math.max(0, top - 100);
}
 
// ══ INIT ══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  renderBlocks();
  scrollToNow();
  // Refresh blocks every 60s (live update)
  setInterval(() => {
    fetch(`../api/bookings_live.php?date=${VIEW_DATE}`)
      .then(r => r.json())
      .then(data => {
        // Update BOOKINGS_DATA and re-render
        // (api file built in later step)
      }).catch(() => {});
  }, 60000);
});
</script>
</body>
</html>

