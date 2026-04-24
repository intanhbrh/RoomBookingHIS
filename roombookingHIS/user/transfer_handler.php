<?php
// user/transfer_handler.php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php"); exit();
}

$from_user_id = $_SESSION['user_id'];
$booking_id   = intval($_POST['booking_id'] ?? 0);
$to_user_id   = intval($_POST['to_user_id'] ?? 0);
$message      = trim($_POST['message'] ?? '');

if (!$booking_id || !$to_user_id) {
    header("Location: dashboard.php?error=invalid_transfer"); exit();
}

// Verify booking exists and belongs to the target user
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $booking_id, $to_user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header("Location: dashboard.php?error=booking_not_found"); exit();
}

// Insert transfer request
$ins = $conn->prepare("
    INSERT INTO transfer_requests (booking_id, from_user_id, to_user_id, message, status)
    VALUES (?, ?, ?, ?, 'pending')
");
$ins->bind_param("iiis", $booking_id, $from_user_id, $to_user_id, $message);
$ins->execute();

// Optional: notify the owner via email
$owner = $conn->query("SELECT * FROM users WHERE id = $to_user_id")->fetch_assoc();
$requester = $conn->query("SELECT * FROM users WHERE id = $from_user_id")->fetch_assoc();

if ($owner && $requester) {
    $to      = $owner['email'];
    $subject = "HIS Room Booking — Transfer Request from {$requester['first_name']} {$requester['last_name']}";
    $body    = "Hello {$owner['first_name']},\n\n"
             . "{$requester['first_name']} {$requester['last_name']} has requested a transfer of your booking:\n\n"
             . "Date: {$booking['booking_date']}\n"
             . "Time: {$booking['start_time']} – {$booking['end_time']}\n\n"
             . ($message ? "Message: $message\n\n" : '')
             . "Please log in to the Room Booking System to accept or decline.\n\n"
             . "HIS Room Booking System";
    mail($to, $subject, $body, "From: noreply@kl.his.edu.my");
}

header("Location: dashboard.php?success=transfer_sent");
exit();