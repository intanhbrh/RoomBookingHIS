<?php
require_once "vendor/autoload.php";
require_once "config.php";          // for DB + session
require_once "google_config.php";   // for Google API keys

// Set up Google client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// Always prompt for login & consent
$client->setPrompt('select_account consent');

// Step 2: Handle Google callback
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die("Google SSO failed: " . htmlspecialchars($token['error']));
    }

    $client->setAccessToken($token['access_token']);

    // Get user info
    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;

    // 🔐 Restrict by domain
    $allowed_domain = 'kl.his.edu.my'; // 🔁 Replace with your real domain
    $domain = substr(strrchr($email, "@"), 1);

    if ($domain !== $allowed_domain) {
        echo "<script>alert('Access denied. Only @$allowed_domain emails are allowed.'); window.location.href='login.php';</script>";
        exit;
    }

    // Check if user already exists in login table
    $stmt = $conn->prepare("SELECT * FROM login WHERE email = ? AND role = 'user'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Insert into students table (only full_name and email)
        $insertStudent = $conn->prepare("INSERT INTO students (full_name, email) VALUES (?, ?)");
        $insertStudent->bind_param("ss", $name, $email);
        $insertStudent->execute();
        $student_id = $insertStudent->insert_id;
        $insertStudent->close();

        // Link to login table
        $insertLogin = $conn->prepare("INSERT INTO login (student_id, email, role, login_method) VALUES (?, ?, 'user', 'google')");
        $insertLogin->bind_param("is", $student_id, $email);
        $insertLogin->execute();
        $insertLogin->close();
    } else {
        $row = $res->fetch_assoc();
        $student_id = $row['student_id'];
    }

    // Set session
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = 'user';
    $_SESSION['student_id'] = $student_id;

    header("Location: user_dashboard.php");
    exit;
}


// Step 1: Redirect to Google for login
$auth_url = $client->createAuthUrl();
header("Location: $auth_url");
exit;
?>
