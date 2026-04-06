<?php
include "includes/header.php";
include "config.php";

$student_id = $_GET['student_id'] ?? null;
$today = date('Y-m-d');
$exam_type = 'GCE';
$registration_fee = 50.00;
$invoice_id = null;

// Fetch student
$student = null;
if ($student_id) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ✅ Fetch GCE registrations with corrected join
$registrations = [];
if ($student_id) {
    $stmt = $conn->prepare("
        SELECT sr.registration_id, s.exam_type, s.syllabus_code, s.name AS subject_name, oc.code AS option_code, oc.fees
        FROM student_registration sr
        JOIN option_code oc ON oc.id = sr.option_code_id
        JOIN syllabus s ON s.syllabus_code = oc.syllabus_code
        WHERE sr.student_id = ? AND s.exam_type = ?
    ");
    $stmt->bind_param("is", $student_id, $exam_type);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    $stmt->close();
}

// ✅ Get late fee period - FIXED VERSION
$late_fee_level = 'Normal';
$late_fee_amount = 0.00;

if ($student_id && !empty($registrations)) {
    // Get the registration date for this student's subjects
    $stmt = $conn->prepare("
        SELECT sr.registered_at 
        FROM student_registration sr
        JOIN option_code oc ON oc.id = sr.option_code_id
        JOIN syllabus s ON s.syllabus_code = oc.syllabus_code
        WHERE sr.student_id = ? AND s.exam_type = ?
        ORDER BY sr.registered_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $student_id, $exam_type);
    $stmt->execute();
    $stmt->bind_result($registration_date);
    
    if ($stmt->fetch()) {
        $reg_date = date('Y-m-d', strtotime($registration_date));
        $stmt->close();
        
        // Check which fee period applies - check Warning2 first (higher penalty)
        $fee_periods = ['Warning2', 'Warning1'];
        
        foreach ($fee_periods as $level) {
            $fee_check = $conn->prepare("
                SELECT late_fee 
                FROM registration_fees 
                WHERE exam_type = ? AND level = ?
                AND ? >= start_date 
                AND (end_date IS NULL OR ? <= end_date)
                LIMIT 1
            ");
            $fee_check->bind_param("ssss", $exam_type, $level, $reg_date, $reg_date);
            $fee_check->execute();
            $fee_check->bind_result($late_fee);
            
            if ($fee_check->fetch()) {
                $late_fee_level = $level;
                $late_fee_amount = $late_fee;
                $fee_check->close();
                break; // Found a match, stop checking
            }
            $fee_check->close();
        }
    } else {
        $stmt->close();
    }
}

// ✅ Check if already paid for GCE
$already_paid_gce = false;
if (!empty($registrations)) {
    $check = $conn->prepare("
        SELECT 1 FROM payments p
        JOIN student_registration sr ON sr.registration_id = p.registration_id
        JOIN option_code oc ON oc.id = sr.option_code_id
        JOIN syllabus s ON s.syllabus_code = oc.syllabus_code
        WHERE sr.student_id = ? AND s.exam_type = ?
        LIMIT 1
    ");
    $check->bind_param("is", $student_id, $exam_type);
    $check->execute();
    $check->store_result();
    $already_paid_gce = $check->num_rows > 0;
    $check->close();
}

// ✅ Check if already paid for IGCSE
$already_paid_igcse = false;
$check = $conn->prepare("
    SELECT 1 FROM payments p
    JOIN student_registration sr ON sr.registration_id = p.registration_id
    JOIN option_code oc ON oc.id = sr.option_code_id
    JOIN syllabus s ON s.syllabus_code = oc.syllabus_code
    WHERE sr.student_id = ? AND s.exam_type = 'IGCSE/GCSE'
    LIMIT 1
");
$check->bind_param("i", $student_id);
$check->execute();
$check->store_result();
$already_paid_igcse = $check->num_rows > 0;
$check->close();

// ✅ Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_paid_gce && !empty($registrations)) {
    $exam_fee = array_sum(array_column($registrations, 'fees'));
    $first_registration_id = $registrations[0]['registration_id'];

    $stmt = $conn->prepare("
        INSERT INTO payments (registration_id, registration_fee, exam_fee, late_fee_level, late_fee_amount, paid_status, payment_date)
        VALUES (?, ?, ?, ?, ?, 'Paid', NOW())
    ");
    $stmt->bind_param("iddsd", $first_registration_id, $registration_fee, $exam_fee, $late_fee_level, $late_fee_amount);
    $stmt->execute();
    $invoice_id = $stmt->insert_id;
    $stmt->close();

    echo "<div class='alert alert-success'>Payment for GCE completed. 
            <a href='print_invoice.php?student_id=$student_id&exam_type=" . urlencode($exam_type) . "' class='btn btn-primary btn-sm' target='_blank'>Print Invoice</a>
          </div>";
}
?>

<!-- ✅ HTML Display -->
<div class="container mt-4">
    <h4>GCE Payment - <?= htmlspecialchars($student['full_name'] ?? 'Unknown') ?></h4>

    <?php if (!empty($registrations)): ?>
        <form method="POST">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Syllabus Code</th>
                        <th>Subject Name</th>
                        <th>Option Code</th>
                        <th>Fee (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $exam_total = 0; foreach ($registrations as $r): $exam_total += $r['fees']; ?>
                        <tr>
                            <td><?= htmlspecialchars($r['syllabus_code']) ?></td>
                            <td><?= htmlspecialchars($r['subject_name']) ?></td>
                            <td><?= htmlspecialchars($r['option_code']) ?></td>
                            <td><?= number_format($r['fees'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Exam Fee Subtotal</th>
                        <th>RM <?= number_format($exam_total, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="mb-3">
                <p><strong>Registration Fee:</strong> RM <?= number_format($registration_fee, 2) ?></p>
                <p><strong>Late Fee (<?= htmlspecialchars($late_fee_level) ?>):</strong> RM <?= number_format($late_fee_amount, 2) ?></p>
                <p class="fw-bold">Total Payment: RM <?= number_format($exam_total + $registration_fee + $late_fee_amount, 2) ?></p>
            </div>

            <?php if (!$already_paid_gce): ?>
                <button type="submit" class="btn btn-success">Submit Payment</button>
            <?php else: ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <span><strong>✔ Invoice Submitted:</strong> This student has already submitted for GCE invoice.</span>
                    <a href='print_invoice.php?student_id=<?= $student_id ?>&exam_type=<?= urlencode($exam_type) ?>' target='_blank' class='btn btn-outline-primary btn-sm'>View/Print Invoice</a>
                </div>
            <?php endif; ?>

            <a href="studentdetailsubjectlist.php?student_id=<?= $student_id ?>" class="btn btn-outline-secondary mt-3">← Back</a>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">No GCE subjects registered for this student.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
