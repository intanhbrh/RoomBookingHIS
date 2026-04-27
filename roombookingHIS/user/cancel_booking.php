<?php
require_once '../includes/auth.php'; requireLogin();
require_once '../includes/db.php';
$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$id,$user_id);
    $stmt->execute();
}
header("Location: bookings.php?success=cancelled"); exit();