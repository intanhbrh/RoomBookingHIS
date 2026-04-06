<?php
// admin_manage_series.php - COMPLETE UPDATED VERSION WITH RETAKE LOGIC CONTROL
include "includes/header.php"; 
include "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_series') {
        $exam_type = $_POST['exam_type'];
        $series_month = $_POST['series_month'];
        $series_year = intval($_POST['series_year']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $retake_logic_enabled = isset($_POST['retake_logic_enabled']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            INSERT INTO exam_series (exam_type, series_month, series_year, is_active, retake_logic_enabled)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                is_active = VALUES(is_active),
                retake_logic_enabled = VALUES(retake_logic_enabled)
        ");
        $stmt->bind_param("ssiii", $exam_type, $series_month, $series_year, $is_active, $retake_logic_enabled);
        
        if ($stmt->execute()) {
            $mode = $retake_logic_enabled ? 'RETAKE SESSION' : 'NORMAL';
            $message = "Series created/updated successfully as <strong>$mode</strong>!";
            $message_type = "success";
        } else {
            $message = "Error creating series: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
    
    elseif ($action === 'toggle_active') {
        $series_id = intval($_POST['series_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE exam_series SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $series_id);
        
        if ($stmt->execute()) {
            $status = $new_status ? 'activated' : 'deactivated';
            $message = "Series $status successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating series status.";
            $message_type = "danger";
        }
        $stmt->close();
    }
    
    elseif ($action === 'toggle_retake') {
        $series_id = intval($_POST['series_id']);
        
        // Get current status
        $stmt = $conn->prepare("SELECT retake_logic_enabled FROM exam_series WHERE id = ?");
        $stmt->bind_param("i", $series_id);
        $stmt->execute();
        $stmt->bind_result($current_status);
        $stmt->fetch();
        $stmt->close();
        
        // Toggle it
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE exam_series SET retake_logic_enabled = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $series_id);
        
        if ($stmt->execute()) {
            $mode = $new_status ? 'RETAKE SESSION' : 'NORMAL';
            $message = "✅ Series mode changed to: <strong>$mode</strong>";
            $message_type = "success";
        } else {
            $message = "❌ Error updating series mode.";
            $message_type = "danger";
        }
        $stmt->close();
    }
    
    elseif ($action === 'archive_series') {
        $series_id = intval($_POST['series_id']);
        
        // Archive all registrations for this series
        $stmt = $conn->prepare("
            UPDATE student_registration 
            SET registration_status = 'archived', archived_at = NOW()
            WHERE series_id = ? AND registration_status = 'active'
        ");
        $stmt->bind_param("i", $series_id);
        $stmt->execute();
        $archived_count = $stmt->affected_rows;
        $stmt->close();
        
        // Deactivate the series
        $stmt = $conn->prepare("UPDATE exam_series SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $series_id);
        $stmt->execute();
        $stmt->close();
        
        $message = "Series archived successfully! $archived_count registrations archived.";
        $message_type = "success";
    }
    
    elseif ($action === 'close_and_start_new') {
        $old_series_id = intval($_POST['old_series_id']);
        $new_exam_type = $_POST['new_exam_type'];
        $new_series_month = $_POST['new_series_month'];
        $new_series_year = intval($_POST['new_series_year']);
        $new_retake_logic = isset($_POST['new_retake_logic']) ? 1 : 0;
        
        // Archive old series
        $stmt = $conn->prepare("
            UPDATE student_registration 
            SET registration_status = 'archived', archived_at = NOW()
            WHERE series_id = ? AND registration_status = 'active'
        ");
        $stmt->bind_param("i", $old_series_id);
        $stmt->execute();
        $stmt->close();
        
        // Deactivate old series
        $stmt = $conn->prepare("UPDATE exam_series SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $old_series_id);
        $stmt->execute();
        $stmt->close();
        
        // Create/activate new series
        $stmt = $conn->prepare("
            INSERT INTO exam_series (exam_type, series_month, series_year, is_active, retake_logic_enabled)
            VALUES (?, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
                is_active = 1,
                retake_logic_enabled = VALUES(retake_logic_enabled)
        ");
        $stmt->bind_param("ssii", $new_exam_type, $new_series_month, $new_series_year, $new_retake_logic);
        $stmt->execute();
        $stmt->close();
        
        // Unlock all students for new registration
        $conn->query("UPDATE students SET registration_locked = 0");
        
        $mode = $new_retake_logic ? 'RETAKE SESSION' : 'NORMAL';
        $message = "Series transition completed! Old series archived and new series activated as <strong>$mode</strong>.";
        $message_type = "success";
    }
}

// Get all series with registration counts
$series_query = "
    SELECT 
        es.id,
        es.exam_type,
        es.series_month,
        es.series_year,
        CONCAT(es.series_month, ' ', es.series_year) as series_name,
        es.is_active,
        es.retake_logic_enabled,
        es.registration_start_date,
        es.registration_end_date,
        es.created_at,
        COUNT(DISTINCT CASE WHEN sr.registration_status = 'active' THEN sr.student_id END) as active_students,
        COUNT(DISTINCT CASE WHEN sr.registration_status = 'active' THEN sr.id END) as active_registrations,
        COUNT(DISTINCT CASE WHEN sr.registration_status = 'archived' THEN sr.student_id END) as archived_students,
        COUNT(DISTINCT CASE WHEN sr.registration_status = 'archived' THEN sr.id END) as archived_registrations
    FROM exam_series es
    LEFT JOIN student_registration sr ON sr.series_id = es.id
    GROUP BY es.id
    ORDER BY es.series_year DESC, es.series_month DESC, es.exam_type
";
$series_result = $conn->query($series_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Exam Series - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge-retake { background-color: #ffc107; color: #000; }
        .badge-normal { background-color: #17a2b8; color: #fff; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-calendar-event me-2"></i>Exam Series Management
                    </h4>
                    <small>Create, activate, and archive exam series with retake logic control</small>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Create New Series -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-plus-circle me-2"></i>Create New Exam Series
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="create_series">
                                
                                <div class="col-md-2">
                                    <label class="form-label">Exam Type</label>
                                    <select name="exam_type" class="form-select" required>
                                        <option value="GCE">GCE</option>
                                        <option value="IGCSE/GCSE">IGCSE/GCSE</option>
                                        <option value="CHECKPOINT">CHECKPOINT</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Series Month</label>
                                    <select name="series_month" class="form-select" required>
                                        <option value="JUNE">JUNE</option>
                                        <option value="NOVEMBER">NOVEMBER</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-1">
                                    <label class="form-label">Year</label>
                                    <input type="number" name="series_year" class="form-control" 
                                           value="<?= date('Y') ?>" min="2024" max="2030" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Registration Mode <i class="bi bi-info-circle" 
                                        title="Normal: Date-based late fees. Retake: Students specify NEW/RETAKE"></i></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="retake_logic_enabled" 
                                               id="retake_off" value="0" checked>
                                        <label class="btn btn-outline-info" for="retake_off">
                                            📅 Normal
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="retake_logic_enabled" 
                                               id="retake_on" value="1">
                                        <label class="btn btn-outline-warning" for="retake_on">
                                            ⚠️ Retake Session
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                                        <label class="form-check-label" for="is_active">
                                            Set as Active
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-lg me-1"></i>Create
                                    </button>
                                </div>
                            </form>
                            
                            <div class="alert alert-info mt-3 mb-0">
                                <strong>💡 Registration Mode:</strong><br>
                                <strong>Normal:</strong> All students pay late fees based on registration date<br>
                                <strong>Retake Session:</strong> Students specify if subject is NEW (late fee applies) or RETAKE (no late fee)
                            </div>
                        </div>
                    </div>

                    <!-- Existing Series -->
                    <h5 class="mb-3">All Exam Series</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Series</th>
                                    <th>Exam Type</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Mode</th>
                                    <th class="text-center">Active<br>Students</th>
                                    <th class="text-center">Active<br>Registrations</th>
                                    <th class="text-center">Archived<br>Students</th>
                                    <th class="text-center">Archived<br>Registrations</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($series = $series_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($series['series_name']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Created: <?= date('M j, Y', strtotime($series['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($series['exam_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($series['is_active']): ?>
                                            <span class="badge bg-success">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">INACTIVE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($series['retake_logic_enabled']): ?>
                                            <span class="badge badge-retake">⚠️ RETAKE SESSION</span>
                                            <br><small class="text-muted">Students specify NEW/RETAKE</small>
                                        <?php else: ?>
                                            <span class="badge badge-normal">📅 NORMAL</span>
                                            <br><small class="text-muted">Date-based late fees</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $series['active_students'] ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $series['active_registrations'] ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= $series['archived_students'] ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $series['archived_registrations'] ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <!-- Toggle Active/Inactive -->
                                            <form method="POST" class="mb-1" 
                                                  onsubmit="return confirm('Toggle series status?');">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $series['is_active'] ?>">
                                                <button type="submit" class="btn btn-outline-primary w-100" 
                                                        title="<?= $series['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="bi bi-power"></i> <?= $series['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Toggle Retake Logic -->
                                            <form method="POST" class="mb-1"
                                                  onsubmit="return confirm('Toggle retake mode? This will affect future registrations.');">
                                                <input type="hidden" name="action" value="toggle_retake">
                                                <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
                                                <button type="submit" class="btn btn-outline-warning w-100" title="Toggle Retake Mode">
                                                    <?php if ($series['retake_logic_enabled']): ?>
                                                        <i class="bi bi-arrow-repeat"></i> Switch to Normal
                                                    <?php else: ?>
                                                        <i class="bi bi-arrow-repeat"></i> Switch to Retake
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                            
                                            <!-- Archive Series -->
                                            <?php if ($series['is_active'] && $series['active_registrations'] > 0): ?>
                                            <form method="POST" class="mb-1" 
                                                  onsubmit="return confirm('Archive this series and all its active registrations? This cannot be undone!');">
                                                <input type="hidden" name="action" value="archive_series">
                                                <input type="hidden" name="series_id" value="<?= $series['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger w-100" title="Archive">
                                                    <i class="bi bi-archive"></i> Archive
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <!-- View Details -->
                                            <button type="button" class="btn btn-outline-info w-100" 
                                                    title="View Details"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailsModal<?= $series['id'] ?>">
                                                <i class="bi bi-info-circle"></i> Details
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal<?= $series['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <?= htmlspecialchars($series['series_name']) ?> - 
                                                    <?= htmlspecialchars($series['exam_type']) ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Status:</th>
                                                        <td><?= $series['is_active'] ? '🟢 Active' : '⚫ Inactive' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Mode:</th>
                                                        <td><?= $series['retake_logic_enabled'] ? '⚠️ Retake Session' : '📅 Normal' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Created:</th>
                                                        <td><?= date('M j, Y g:i A', strtotime($series['created_at'])) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Active Students:</th>
                                                        <td><?= $series['active_students'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Active Registrations:</th>
                                                        <td><?= $series['active_registrations'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Archived Students:</th>
                                                        <td><?= $series['archived_students'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Archived Registrations:</th>
                                                        <td><?= $series['archived_registrations'] ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Quick Series Transition Tool -->
                    <div class="card mt-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-repeat me-2"></i>Quick Series Transition
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Close an active series and automatically start a new one. 
                                This will archive all active registrations and unlock students for new registration.
                            </p>
                            
                            <form method="POST" onsubmit="return confirm('This will archive the current series and start a new one. All students will be unlocked. Continue?');">
                                <input type="hidden" name="action" value="close_and_start_new">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Close This Series</label>
                                        <select name="old_series_id" class="form-select" required>
                                            <?php
                                            $active_series = $conn->query("SELECT id, exam_type, series_month, series_year FROM exam_series WHERE is_active = 1");
                                            while ($s = $active_series->fetch_assoc()):
                                            ?>
                                                <option value="<?= $s['id'] ?>">
                                                    <?= $s['exam_type'] ?> - <?= $s['series_month'] ?> <?= $s['series_year'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">New Exam Type</label>
                                        <select name="new_exam_type" class="form-select" required>
                                            <option value="GCE">GCE</option>
                                            <option value="IGCSE/GCSE">IGCSE/GCSE</option>
                                            <option value="CHECKPOINT">CHECKPOINT</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">New Month</label>
                                        <select name="new_series_month" class="form-select" required>
                                            <option value="JUNE">JUNE</option>
                                            <option value="NOVEMBER">NOVEMBER</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <label class="form-label">New Year</label>
                                        <input type="number" name="new_series_year" class="form-control" 
                                               value="<?= date('Y') + 1 ?>" min="2024" max="2030" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">New Mode</label>
                                        <div class="form-check">
                                            <input type="checkbox" name="new_retake_logic" class="form-check-input" id="new_retake">
                                            <label class="form-check-label" for="new_retake">
                                                Retake Session
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="bi bi-arrow-repeat me-1"></i>Transition
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
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
