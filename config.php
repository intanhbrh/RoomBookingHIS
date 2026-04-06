<?php
// config.php
// Start session
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

// ===== DATABASE CONNECTION =====
$servername = "localhost";
$username   = "root";
$password   = "root"; // Change this if your DB has a password
$dbname     = "examSystem";

// ✅ Define the iSAMS API key
define('ISAMS_API_KEY', '171B1CE3-40FD-44BC-BF97-A3709377DBC3');
define('apikey','');

// Create DB connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Database Connection failed: " . $conn->connect_error);
}
