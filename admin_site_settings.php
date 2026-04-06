<?php
include "includes/header.php"; 
include "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setting_key = $_POST['setting_key'];
    $setting_value = $_POST['setting_value'];
    
    $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $setting_value, $setting_key);
    
    if ($stmt->execute()) {
        $message = "Setting updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating setting.";
        $message_type = "danger";
    }
    $stmt->close();
}

// Get all settings except homepage_notice
$settings = [];
$result = $conn->query("SELECT * FROM admin_settings WHERE setting_key != 'homepage_notice' ORDER BY setting_key");
while ($row = $result->fetch_assoc()) {
    $settings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-gear-fill me-2"></i>Site Settings Management
                    </h4>
                    <small>Update series information, deadlines, and contact details</small>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php foreach ($settings as $setting): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <form method="POST">
                                            <input type="hidden" name="setting_key" value="<?= htmlspecialchars($setting['setting_key']) ?>">
                                            
                                            <h6 class="card-title">
                                                <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                                            </h6>
                                            
                                            <p class="text-muted small">
                                                <?= htmlspecialchars($setting['setting_description']) ?>
                                            </p>
                                            
                                            <?php if ($setting['setting_key'] === 'registration_deadline'): ?>
                                                <input type="date" name="setting_value" class="form-control mb-3" value="<?= htmlspecialchars($setting['setting_value']) ?>" required>
                                            <?php else: ?>
                                                <input type="text" name="setting_value" class="form-control mb-3" value="<?= htmlspecialchars($setting['setting_value']) ?>" required>
                                            <?php endif; ?>
                                            
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-check-lg me-1"></i>Update
                                            </button>
                                            
                                            <small class="text-muted d-block mt-2">
                                                Last updated: <?= date('M j, Y g:i A', strtotime($setting['updated_at'])) ?>
                                            </small>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
