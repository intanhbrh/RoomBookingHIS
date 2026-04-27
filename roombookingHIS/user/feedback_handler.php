<?php
require_once '../includes/auth.php'; requireLogin();
require_once '../includes/db.php';
$user_id = $_SESSION['user_id'];
$msg = trim($_POST['message'] ?? '');
if ($msg) {
    $stmt = $conn->prepare("INSERT INTO feedback (user_id,message) VALUES (?,?)");
    $stmt->bind_param("is",$user_id,$msg);
    $stmt->execute();
}
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php') . "?feedback=sent"); exit();