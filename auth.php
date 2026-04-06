<?php
//auth.php
session_start();
if (
    empty($_SESSION['user_email'])
  || ! in_array($_SESSION['role'], ['user','student','parent'])
) {
    header("Location: logout.php");
    exit;
}

// Check if student's registration is locked
if (isset($_SESSION['student_id']) && !isset($_SESSION['registration_locked'])) {
    require_once 'config.php';
    
    $check_lock = $conn->prepare("SELECT registration_locked FROM students WHERE student_id = ?");
    $check_lock->bind_param("i", $_SESSION['student_id']);
    $check_lock->execute();
    $check_lock->bind_result($registration_locked);
    $check_lock->fetch();
    $check_lock->close();
    
    if ($registration_locked == 1) {
        $_SESSION['registration_locked'] = true;
    } else {
        $_SESSION['registration_locked'] = false;
    }
}
?>
