<?php
// includes/db.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // your phpMyAdmin username
define('DB_PASS', '');             // your phpMyAdmin password
define('DB_NAME', 'roombooking_his');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>