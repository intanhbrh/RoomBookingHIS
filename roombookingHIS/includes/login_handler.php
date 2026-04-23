<?php
// includes/login_handler.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: ../index.php?error=empty_fields"); exit();
}

// Look up user by username
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'user' LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php?error=invalid"); exit();
}

$user = $result->fetch_assoc();

// If this user logs in via Google, they shouldn't use password login
if ($user['password'] === 'GOOGLE_AUTH') {
    header("Location: ../index.php?error=use_google"); exit();
}

// Verify password
if (!password_verify($password, $user['password'])) {
    header("Location: ../index.php?error=invalid"); exit();
}

// Set session
$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['email']      = $user['email'];
$_SESSION['role']       = $user['role'];

header("Location: ../user/dashboard.php");
exit();
?>