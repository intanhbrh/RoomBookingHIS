<?php
session_start();

// Store role before destroying session
$userRole = $_SESSION['role'] ?? null;

session_unset();
session_destroy();

// Optional: Revoke Google token if needed

// Redirect based on user role
if (in_array($userRole, ['student', 'parent', 'user'])) {
    header("Location: https://his.myschoolportal.co.uk/");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
