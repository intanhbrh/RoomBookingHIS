<?php
require_once '../includes/auth.php'; requireLogin();
require_once '../includes/db.php';
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: helpdesk.php"); exit(); }
$subject = trim($_POST['subject'] ?? '');
$dept    = trim($_POST['department'] ?? '');
$desc    = trim($_POST['description'] ?? '');
$pri     = trim($_POST['priority'] ?? 'Low');
$risk    = trim($_POST['risk'] ?? 'Low');
if (!$subject || !$dept || !$desc) { header("Location: helpdesk.php?error=missing"); exit(); }
$stmt = $conn->prepare("INSERT INTO tickets (user_id,subject,department,description,priority,risk) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("isssss",$user_id,$subject,$dept,$desc,$pri,$risk);
$stmt->execute();
header("Location: helpdesk.php?success=ticket_submitted"); exit();