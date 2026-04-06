<?php
session_start();
require_once "config.php";

// Redirect if already logged in
if (isset($_SESSION['admin_username']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM login WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();
    $stmt->close();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['role'] = 'admin';
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | HELP Exam Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-wrapper {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to right, #0056b3, #007bff);
            color: white;
        }
        .login-container {
            background: white;
            color: #333;
            border-radius: 1rem;
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .login-left {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('image/exam-login.jpg') center/cover no-repeat;
            flex: 1;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-left h1 {
            color: white;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }
        .login-right {
            flex: 1.2;
            padding: 2rem 3rem;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .google-btn img {
            height: 20px;
            margin-right: 8px;
        }
        .divider {
            text-align: center;
            margin: 2rem 0;
        }
        .divider span {
            background: #fff;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <!-- Left image/branding -->
        <div class="login-left">
            <h1>HELP International School<br>------------------<br>Exam Management System</h1>
        </div>

        <!-- Right login section -->
        <div class="login-right">
            <!-- Admin Login -->
            <div class="form-section">
                <h4 class="mb-3">Admin Login</h4>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary">Login as Admin</button>
                    </div>
                </form>
            </div>

            <div class="divider">
                <span>OR</span>
            </div>

            <!-- Student Login -->
            <div class="form-section text-center">
                <h4 class="mb-3">Student Login</h4>
                <p class="text-muted">Login with your school Google account</p>
                <a href="google_login.php" class="btn btn-outline-danger w-75 google-btn mx-auto">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                    Sign in with Google
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
