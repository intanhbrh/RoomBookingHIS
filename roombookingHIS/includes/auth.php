<?php
// includes/auth.php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /roombookingHIS/index.php");
        exit();
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: /roombookingHIS/index.php");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>