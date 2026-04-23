<?php
// includes/admin_login_handler.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin_login.php"); exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../admin_login.php?error=invalid"); exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    header("Location: ../admin_login.php?error=invalid"); exit();
}

$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['email']      = $user['email'];
$_SESSION['role']       = 'admin';

header("Location: ../admin/dashboard.php");
exit();
?>