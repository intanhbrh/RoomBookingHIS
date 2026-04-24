<?php
// includes/reset_handler.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php"); exit();
}

$username = trim($_POST['reset_username'] ?? '');
$email    = trim($_POST['reset_email'] ?? '');

// Verify user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ? LIMIT 1");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php?reset=notfound"); exit();
}

$user = $result->fetch_assoc();

// Generate token (in production: store in DB with expiry)
$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store token in DB (add this table to SQL: password_resets)
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expiry DATETIME NOT NULL,
    used TINYINT DEFAULT 0
)");

$ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
$ins->bind_param("iss", $user['id'], $token, $expiry);
$ins->execute();

// Send email (configure your SMTP in production)
$reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/roombookingHIS/reset_password.php?token=" . $token;
$to      = $email;
$subject = "HIS Room Booking - Password Reset";
$message = "Hello {$user['first_name']},\n\nClick the link below to reset your password:\n\n$reset_link\n\nThis link expires in 1 hour.\n\nIf you did not request this, please ignore this email.\n\nHIS Room Booking System";
$headers = "From: noreply@kl.his.edu.my";

mail($to, $subject, $message, $headers);

header("Location: ../index.php?reset=sent");
exit();
?>