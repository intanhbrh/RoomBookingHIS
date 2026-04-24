<?php
// user/booking_handler.php — handles quick-add booking from day view
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit();
}

$user_id      = $_SESSION['user_id'];
$room_id      = intval($_POST['room_id']      ?? 0);
$booking_date = trim($_POST['booking_date']   ?? '');
$start_time   = trim($_POST['start_time']     ?? '');
$end_time     = trim($_POST['end_time']       ?? '');
$title        = trim($_POST['title']          ?? '');
$notes        = trim($_POST['notes']          ?? '');

// Basic validation
if (!$room_id || !$booking_date || !$start_time || !$end_time || !$title) {
    header("Location: dashboard.php?date=$booking_date&error=missing_fields"); exit();
}

// Normalise times to HH:MM:SS
$start_time = strlen($start_time) === 5 ? $start_time . ':00' : $start_time;
$end_time   = strlen($end_time) === 5   ? $end_time . ':00'   : $end_time;

// Check for conflicts
$stmt = $conn->prepare("
    SELECT id FROM bookings
    WHERE room_id = ?
      AND booking_date = ?
      AND status != 'cancelled'
      AND start_time < ? AND end_time > ?
    LIMIT 1
");
$stmt->bind_param("isss", $room_id, $booking_date, $end_time, $start_time);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: dashboard.php?date=$booking_date&error=conflict"); exit();
}

// Insert booking
$ins = $conn->prepare("
    INSERT INTO bookings (user_id, room_id, booking_date, start_time, end_time, title, notes, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
");
$ins->bind_param("iisssss", $user_id, $room_id, $booking_date, $start_time, $end_time, $title, $notes);
$ins->execute();

header("Location: dashboard.php?date=$booking_date&success=booked");
exit();