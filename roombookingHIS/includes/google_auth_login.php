<?php
// ============================================================
// HIS Room Booking - Google OAuth Login Handler
// File: includes/google_auth.php
// Requires: composer require google/apiclient
// ============================================================

session_start();
require_once 'db.php';

// ── CONFIGURE THESE ──────────────────────────────────────────
define('GOOGLE_CLIENT_ID',     '494702560472-fnnstbks52g9stja5a6knhs1ad7enp44.apps.googleusercontent.com');       // From Google Cloud Console
define('GOOGLE_CLIENT_SECRET', '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');   // From Google Cloud Console
define('GOOGLE_REDIRECT_URI',  'http://localhost/roombookingHIS/includes/google_auth_login.php');
define('ALLOWED_DOMAIN',       'kl.his.edu.my');               // Only allow this domain
// ─────────────────────────────────────────────────────────────

require_once 'vendor/autoload.php'; // after running composer install

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

// ── STEP 1: Redirect to Google ────────────────────────────────
if (!isset($_GET['code'])) {
    $auth_url = $client->createAuthUrl();
    header("Location: $auth_url");
    exit();
}

// ── STEP 2: Handle Google callback ───────────────────────────
try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        header("Location: ../index.php?error=google_auth_failed");
        exit();
    }
    
    $client->setAccessToken($token);
    
    $oauth2 = new Google\Service\Oauth2($client);
    $userinfo = $oauth2->userinfo->get();
    
    $google_email = $userinfo->email;
    $google_name  = $userinfo->name;
    $domain       = substr(strrchr($google_email, '@'), 1);
    
    // ── STEP 3: Enforce school domain ────────────────────────
    if ($domain !== ALLOWED_DOMAIN) {
        header("Location: ../index.php?error=not_school_email");
        exit();
    }
    
    // ── STEP 4: Look up user in database ─────────────────────
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR google_email = ? LIMIT 1");
    $stmt->bind_param("ss", $google_email, $google_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found - auto-create from Google info
        $username   = explode('@', $google_email)[0];
        $name_parts = explode(' ', $google_name, 2);
        $first_name = $name_parts[0];
        $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';
        
        $ins = $conn->prepare(
            "INSERT INTO users (username, first_name, last_name, email, google_email, password, role)
             VALUES (?, ?, ?, ?, ?, 'GOOGLE_AUTH', 'user')"
        );
        $ins->bind_param("sssss", $username, $first_name, $last_name, $google_email, $google_email);
        $ins->execute();
        $user_id = $conn->insert_id;
        $role = 'user';
        $first_name_s = $first_name;
    } else {
        $user = $result->fetch_assoc();
        $user_id     = $user['id'];
        $role        = $user['role'];
        $first_name_s = $user['first_name'];
        $username    = $user['username'];
    }
    
    // ── STEP 5: Set session & redirect ───────────────────────
    $_SESSION['user_id']    = $user_id;
    $_SESSION['username']   = $username ?? explode('@', $google_email)[0];
    $_SESSION['first_name'] = $first_name_s;
    $_SESSION['email']      = $google_email;
    $_SESSION['role']       = $role;
    $_SESSION['login_method'] = 'google';
    
    if ($role === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit();

} catch (Exception $e) {
    error_log("Google Auth Error: " . $e->getMessage());
    header("Location: ../index.php?error=auth_error");
    exit();
}
?>