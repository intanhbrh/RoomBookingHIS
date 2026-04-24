<?php
// includes/sidebar.php
// Requires: $activePage variable to be set before including
// e.g. $activePage = 'dashboard';
if (!isset($activePage)) $activePage = 'dashboard';
?>
<aside class="sidebar" id="sidebar">

  <!-- LOGO / BRAND -->
  <div class="sidebar-brand">
    <div class="brand-logo">
      <span>HIS</span>
    </div>
    <div class="brand-text">
      <p class="brand-title">Room Booking</p>
      <p class="brand-sub">Help International School</p>
    </div>
  </div>

  <!-- MAIN NAV -->
  <nav class="sidebar-nav">
    <p class="nav-section-label">Calendar</p>

    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 8a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zm6-6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zm0 8a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
      </span>
      <span class="nav-label">Day View</span>
    </a>

    <a href="bookings.php" class="nav-item <?= $activePage==='bookings' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">Bookings</span>
      <span class="nav-badge" id="bookingsBadge"></span>
    </a>

    <a href="statistics.php" class="nav-item <?= $activePage==='statistics' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
      </span>
      <span class="nav-label">Statistics</span>
    </a>

    <a href="helpdesk.php" class="nav-item <?= $activePage==='helpdesk' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">Helpdesk</span>
    </a>
  </nav>

  <!-- BOTTOM NAV -->
  <div class="sidebar-bottom">
    <a href="account.php" class="nav-item <?= $activePage==='account' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">My Account</span>
    </a>

    <a href="#" class="nav-item" onclick="openFeedback(event)">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">Send Feedback</span>
    </a>

    <a href="help.php" class="nav-item <?= $activePage==='help' ? 'active':'' ?>">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">Help</span>
    </a>

    <a href="../logout.php" class="nav-item nav-signout">
      <span class="nav-icon">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
      </span>
      <span class="nav-label">Sign Out</span>
    </a>
  </div>

</aside>

<!-- ── FEEDBACK MODAL ───────────────────────────── -->
<div class="modal-overlay" id="feedbackModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeFeedback()">×</button>
    <div class="modal-icon">💬</div>
    <h3>Send Feedback</h3>
    <p class="modal-desc">We'd love to hear your comments — report a bug, request a new feature, or ask about anything else.</p>
    <form method="POST" action="feedback_handler.php">
      <textarea name="message" placeholder="Write your message here..." rows="5" required></textarea>
      <button type="submit" class="btn-primary">Send Feedback</button>
    </form>
  </div>
</div>