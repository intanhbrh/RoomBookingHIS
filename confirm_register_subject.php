<?php
// confirm_register_subject.php
require 'auth.php';
include "includes/header.php";
include "config.php";
require 'vendor/autoload.php';  // for SendGrid

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$error = '';
$success = false;

// Check if pending registration exists
if (!isset($_SESSION['pending_registration'])) {
    header("Location: student_registersubject.php");
    exit;
}

$pending = $_SESSION['pending_registration'];
$exam_type = $pending['exam_type'];
$selected_options = $pending['option_code'];
$retake_status = $pending['retake_status'] ?? [];

// Get student information - check which fields exist in your database
$stmt = $conn->prepare("
    SELECT 
        s.email, 
        s.full_name,
        si.forename, 
        si.surname, 
        si.candidate_number
    FROM students s
    LEFT JOIN isams_students si ON si.email = s.email
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Initialize fields that may not exist in database yet
$student_info['student_mobile'] = $student_info['student_mobile'] ?? '';
$student_info['personal_email'] = $student_info['personal_email'] ?? '';

// Fetch details of selected subjects
$option_ids = implode(',', array_map('intval', $selected_options));
$subjects_query = $conn->query("
    SELECT 
        oc.id as option_code_id,
        oc.code,
        oc.fees,
        s.name as subject_name,
        s.exam_type,
        s.syllabus_code
    FROM option_code oc
    JOIN syllabus s ON s.id = oc.syllabus_id
    WHERE oc.id IN ($option_ids)
    ORDER BY s.name
");
$selected_subjects = $subjects_query->fetch_all(MYSQLI_ASSOC);

// Calculate total fees
$total_fees = array_sum(array_column($selected_subjects, 'fees'));

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle cancel action
    if (isset($_POST['cancel_registration'])) {
        unset($_SESSION['pending_registration']);
        header("Location: student_registersubject.php");
        exit;
    }
    
    // Handle final registration
    if (isset($_POST['finish_registration'])) {
        // Validate form fields
        $declaration_1 = isset($_POST['declaration_1']) ? 1 : 0;
        $declaration_2 = isset($_POST['declaration_2']) ? 1 : 0;
        $student_mobile = trim($_POST['student_mobile'] ?? '');
        $personal_email = trim($_POST['personal_email'] ?? '');
        $english_first_language = ucfirst($_POST['english_first_language'] ?? '');
        $special_needs = ucfirst($_POST['special_needs'] ?? '');
        $special_needs_details = trim($_POST['special_needs_details'] ?? '');
        $relationship = $_POST['relationship'] ?? '';
        $relationship_other = trim($_POST['relationship_other'] ?? '');
        $signed_name = trim($_POST['signed_name'] ?? '');
        $signed_date = trim($_POST['signed_date'] ?? '');
        
        // Validation
        if (!($declaration_1 && $declaration_2)) {
            $error = '⚠️ You must accept all terms and conditions.';
        } elseif (empty($student_mobile)) {
            $error = '⚠️ Student mobile number is required.';
        } elseif (empty($personal_email) || !filter_var($personal_email, FILTER_VALIDATE_EMAIL)) {
            $error = '⚠️ Valid personal email address is required.';
        } elseif (empty($english_first_language)) {
            $error = '⚠️ Please specify if English is your first language.';
        } elseif (empty($special_needs)) {
            $error = '⚠️ Please specify if special needs accommodations are required.';
        } elseif ($special_needs === 'yes' && empty($special_needs_details)) {
            $error = '⚠️ Please provide details about special needs requirements.';
        } elseif (!in_array($relationship, ['Self','Parent','Guardian','Other'], true)) {
            $error = '⚠️ Please select your relationship to the candidate.';
        } elseif ($relationship === 'Other' && $relationship_other === '') {
            $error = '⚠️ Please specify the relationship when "Other" is selected.';
        } elseif ($signed_name === '' || $signed_date === '') {
            $error = '⚠️ Please provide both your printed name and signature date.';
        }
        
        if (!$error) {
            // First, let's check what columns exist in the students table
            $columns_check = $conn->query("SHOW COLUMNS FROM students");
            $existing_columns = [];
            while ($col = $columns_check->fetch_assoc()) {
                $existing_columns[] = $col['Field'];
            }
            
            // Build dynamic update query based on existing columns
            $update_fields = [];
            $update_values = [];
            $types = '';
            
            // Check each field and add to update if column exists
            if (in_array('student_mobile', $existing_columns)) {
                $update_fields[] = 'student_mobile = ?';
                $update_values[] = $student_mobile;
                $types .= 's';
            }
            
            if (in_array('personal_email', $existing_columns)) {
                $update_fields[] = 'personal_email = ?';
                $update_values[] = $personal_email;
                $types .= 's';
            }
            
            if (in_array('english_first_language', $existing_columns)) {
                $update_fields[] = 'english_first_language = ?';
                $update_values[] = $english_first_language;
                $types .= 's';
            }
            
            if (in_array('special_needs', $existing_columns)) {
                $update_fields[] = 'special_needs = ?';
                $update_values[] = $special_needs;
                $types .= 's';
            }
            
            if (in_array('special_needs_details', $existing_columns)) {
                $update_fields[] = 'special_needs_details = ?';
                $update_values[] = $special_needs_details;
                $types .= 's';
            }
            
            if (in_array('declaration_1', $existing_columns)) {
                $update_fields[] = 'declaration_1 = ?';
                $update_values[] = $declaration_1;
                $types .= 'i';
            }
            
            if (in_array('declaration_2', $existing_columns)) {
                $update_fields[] = 'declaration_2 = ?';
                $update_values[] = $declaration_2;
                $types .= 'i';
            }
            
            if (in_array('relationship', $existing_columns)) {
                $update_fields[] = 'relationship = ?';
                $update_values[] = $relationship;
                $types .= 's';
            }
            
            if (in_array('relationship_other', $existing_columns)) {
                $update_fields[] = 'relationship_other = ?';
                $update_values[] = $relationship_other;
                $types .= 's';
            }
            
            if (in_array('declaration_signed_name', $existing_columns)) {
                $update_fields[] = 'declaration_signed_name = ?';
                $update_values[] = $signed_name;
                $types .= 's';
            }
            
            if (in_array('declaration_signed_date', $existing_columns)) {
                $update_fields[] = 'declaration_signed_date = ?';
                $update_values[] = $signed_date;
                $types .= 's';
            }
            
            // Only run update if we have fields to update
            if (!empty($update_fields)) {
                $update_values[] = $student_id; // Add student_id for WHERE clause
                $types .= 'i';
                
                $update_sql = "UPDATE students SET " . implode(', ', $update_fields) . " WHERE student_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param($types, ...$update_values);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Get deadline and fee information
            $deadlines = [];
            $deadlines_detail = [];
            $df = $conn->query("
                SELECT exam_type, level, end_date, start_date, late_fee
                FROM registration_fees
                WHERE level IN ('Warning1', 'Warning2')
            ");
            while ($r = $df->fetch_assoc()) {
                if ($r['level'] === 'Warning2') {
                    $deadlines[$r['exam_type']] = $r['end_date'];
                }
                $deadlines_detail[$r['exam_type']][$r['level']] = $r;
            }
            
            // Check retake logic settings
            $retake_logic_enabled = false;
            $retake_stmt = $conn->prepare("SELECT retake_logic_enabled FROM retake_fee_settings WHERE exam_type = ?");
            $retake_stmt->bind_param("s", $exam_type);
            $retake_stmt->execute();
            $retake_stmt->bind_result($retake_logic_enabled);
            $retake_stmt->fetch();
            $retake_stmt->close();
            
            // Determine series info
            $today = new DateTime();
            $year = (int)$today->format('Y');
            $july = new DateTime("$year-07-01");
            $dow = (int)$july->format('N');
            $offset = ($dow === 1) ? 0 : (8 - $dow);
            $firstMonday = (clone $july)->modify("+{$offset} days");
            $thirdMonday = (clone $firstMonday)->modify('+14 days');
            $month = (int)$today->format('n');
            $day = (int)$today->format('j');
            
            if ($month <= 6) {
                $seriesMonth = "JUNE";
            } elseif ($month >= 7 && $month < 12) {
                $seriesMonth = "NOVEMBER";
            } elseif ($month == 12 && $day <= 7) {
                $seriesMonth = "NOVEMBER";
            } else {
                $seriesMonth = "JUNE";
            }
            
            $isGCENovember = ($exam_type === 'GCE' && $seriesMonth === 'NOVEMBER');
            
            // Insert registrations with fee logic
            $now = date('Y-m-d H:i:s');
            foreach ($selected_options as $opt) {
                $is_retake = 0;
                $late_fee_applied = 0;
                
                if ($retake_logic_enabled && $isGCENovember) {
                    $is_retake = isset($retake_status[$opt]) && $retake_status[$opt] === '1' ? 1 : 0;
                    if (!$is_retake) {
                        $late_fee_applied = 1;
                    }
                } elseif (!$retake_logic_enabled) {
                    $current_date = date('Y-m-d');
                    foreach (['Warning2', 'Warning1'] as $level) {
                        if (isset($deadlines_detail[$exam_type][$level])) {
                            $start = $deadlines_detail[$exam_type][$level]['start_date'];
                            $end = $deadlines_detail[$exam_type][$level]['end_date'];
                            if ($current_date >= $start && (empty($end) || $current_date <= $end)) {
                                $late_fee_applied = 1;
                                break;
                            }
                        }
                    }
                }
                
                // Get active series ID for this exam type
                $series_stmt = $conn->prepare("SELECT id FROM exam_series WHERE exam_type = ? AND is_active = 1 LIMIT 1");
                $series_stmt->bind_param("s", $exam_type);
                $series_stmt->execute();
                $series_result = $series_stmt->get_result();
                $series_row = $series_result->fetch_assoc();
                $series_id = $series_row['id'] ?? null;
                $series_stmt->close();

                $ins = $conn->prepare("
                    INSERT INTO student_registration
                        (student_id, option_code_id, series_id, registered_at, is_retake, late_fee_applied, registration_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                $ins->bind_param("iiisii", $student_id, $opt, $series_id, $now, $is_retake, $late_fee_applied);
                $ins->execute();
                $ins->close();
            }
            
            // Clear pending registration
            unset($_SESSION['pending_registration']);

            // Lock the registration so student cannot login and modify again
            $lock_stmt = $conn->prepare("UPDATE students SET registration_locked = 1 WHERE student_id = ?");
            $lock_stmt->bind_param("i", $student_id);
            $lock_stmt->execute();
            $lock_stmt->close();

            // Lock the registration so student cannot login and modify again
            $lock_stmt = $conn->prepare("UPDATE students SET registration_locked = 1 WHERE student_id = ?");
            $lock_stmt->bind_param("i", $student_id);
            $lock_stmt->execute();
            $lock_stmt->close();

            // Update session immediately so user doesn't need to logout
            $_SESSION['registration_locked'] = true;
            
            // Send confirmation email with all declaration information
            
            // Build recipient list
            $recipients = [];

            // Get student information for email
            $stmt = $conn->prepare("
                SELECT s.email, s.full_name,
                       si.forename, si.surname, si.school_id, si.candidate_number
                FROM students s
                LEFT JOIN isams_students si ON si.email = s.email
                WHERE s.student_id = ?
                LIMIT 1
            ");
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $stmt->bind_result($studentEmail, $fullName, $forename, $surname, $schoolId, $candidateNumber);

            $studentName = '';
            $studentDisplay = '';

            if ($stmt->fetch()) {
                // Priority 1: Use full_name from students table if available
                if (!empty($fullName)) {
                    $studentName = $fullName;
                    $studentDisplay = $candidateNumber ? "($candidateNumber)" : "($student_id)";
                }
                // Priority 2: Construct from forename + surname if available
                elseif (!empty($forename) && !empty($surname)) {
                    $studentName = trim($forename . ' ' . $surname);
                    $studentDisplay = $candidateNumber ? "($candidateNumber)" : "($student_id)";
                }
                // Priority 3: Fall back to email username
                else {
                    $emailParts = explode('@', $studentEmail);
                    $studentName = $emailParts[0];
                    $studentDisplay = $candidateNumber ? "($candidateNumber)" : "($student_id)";
                }
            }
            $stmt->close();

            // Get parent emails
            $stmt = $conn->prepare("
                SELECT parent_email
                FROM student_contact
                WHERE student_school_id = (
                    SELECT si.school_id
                    FROM students s
                    JOIN isams_students si ON si.email = s.email
                    WHERE s.student_id = ?
                    LIMIT 1
                )
            ");
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $stmt->bind_result($parentEmail);
            while ($stmt->fetch()) {
                if (filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = $parentEmail;
                }
            }
            $stmt->close();

            // Add student emails
            if (filter_var($personal_email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $personal_email;
            }
            if (filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $studentEmail;
            }

            // Add exams office
            $recipients[] = 'exams@kl.his.edu.my';
            $recipients = array_unique($recipients);

            // Get registered subjects for email
            $regs = [];

            $stmt = $conn->prepare("
                SELECT s.exam_type,
                       s.name AS subject_name,
                       oc.code AS option_code,
                       oc.fees AS fees,
                       sr.is_retake,
                       sr.late_fee_applied
                FROM student_registration sr
                JOIN option_code oc ON oc.id = sr.option_code_id
                JOIN syllabus s ON s.id = oc.syllabus_id
                JOIN exam_series es ON es.id = sr.series_id             
                WHERE sr.student_id = ?
                    AND es.is_active = 1
                    AND sr.registration_status = 'active'
                ORDER BY s.exam_type, s.name
            ");
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $regs[] = $r;
            }
            $stmt->close();

            // Build email content
            $lines = [];
            if (empty($regs)) {
                $lines[] = "No subjects registered.";
            } else {
                foreach ($regs as $r) {
                    $retakeText = '';
                    $lateFeeText = '';
                    
                    if ($r['is_retake'] !== null && $retake_logic_enabled) {
                        $retakeText = $r['is_retake'] ? ' (Retake)' : ' (New)';
                    }
                    
                    if ($r['late_fee_applied']) {
                        $lateFeeText = ' + Late Fee';
                    }
                    $lines[] = "[{$r['exam_type']}] {$r['subject_name']} ({$r['option_code']}) – RM"
                            . number_format($r['fees'], 2) . $retakeText . $lateFeeText;
                }
            }
            $textList = implode("\n", $lines);

            // Build HTML table
            $htmlTable = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse">'
                      . '<thead><tr>'
                      . '<th>Exam Type</th><th>Subject</th><th>Option Code</th><th>Fee (RM)</th><th>Status</th>'
                      . '</tr></thead><tbody>';
            foreach ($regs as $r) {
                $retakeText = '';
                $lateFeeText = '';
                
                if ($r['is_retake'] !== null && $retake_logic_enabled) {
                    $retakeText = $r['is_retake'] ? 'Retake' : 'New';
                }
                
                if ($r['late_fee_applied']) {
                    $lateFeeText = $retakeText ? $retakeText . ' + Late Fee' : 'Late Fee Applied';
                } else {
                    $lateFeeText = $retakeText ?: 'Confirmed';
                }
                
                $htmlTable .= '<tr>'
                            . '<td>'.htmlspecialchars($r['exam_type']).'</td>'
                            . '<td>'.htmlspecialchars($r['subject_name']).'</td>'
                            . '<td>'.htmlspecialchars($r['option_code']).'</td>'
                            . '<td style="text-align:right;">'.number_format($r['fees'],2).'</td>'
                            . '<td>'.htmlspecialchars($lateFeeText).'</td>'
                            . '</tr>';
            }
            $htmlTable .= '</tbody></table>';

            // Declaration information for email
            $declarationInfo = "\n\nDeclaration Information:\n"
                            . "Student Mobile: $student_mobile\n"
                            . "Personal Email: $personal_email\n"
                            . "English First Language: " . ucfirst($english_first_language) . "\n"
                            . "Special Needs Required: " . ucfirst($special_needs) . "\n";
            
            if ($special_needs === 'yes' && !empty($special_needs_details)) {
                $declarationInfo .= "Special Needs Details: $special_needs_details\n";
            }
            
            $declarationInfo .= "Relationship to Candidate: $relationship\n";
            if ($relationship === 'Other' && !empty($relationship_other)) {
                $declarationInfo .= "Relationship Details: $relationship_other\n";
            }
            $declarationInfo .= "Signed by: $signed_name on $signed_date\n";

            // HTML version of declaration info
            $declarationInfoHtml = '<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #28a745;">'
                                . '<h4 style="margin-top: 0;">Declaration Information:</h4>'
                                . '<table style="width: 100%; border-collapse: collapse;">'
                                . '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Student Mobile:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($student_mobile) . '</td></tr>'
                                . '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Personal Email:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($personal_email) . '</td></tr>'
                                . '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>English First Language:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars(ucfirst($english_first_language)) . '</td></tr>'
                                . '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Special Needs Required:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars(ucfirst($special_needs)) . '</td></tr>';
            
            if ($special_needs === 'yes' && !empty($special_needs_details)) {
                $declarationInfoHtml .= '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Special Needs Details:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($special_needs_details) . '</td></tr>';
            }
            
            $declarationInfoHtml .= '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Relationship to Candidate:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($relationship) . '</td></tr>';
            
            if ($relationship === 'Other' && !empty($relationship_other)) {
                $declarationInfoHtml .= '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Relationship Details:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($relationship_other) . '</td></tr>';
            }
            
            $declarationInfoHtml .= '<tr><td style="padding: 5px; border: 1px solid #ddd;"><strong>Signed by:</strong></td><td style="padding: 5px; border: 1px solid #ddd;">' . htmlspecialchars($signed_name) . ' on ' . htmlspecialchars($signed_date) . '</td></tr>'
                                 . '</table></div>';

            // Terms & Conditions
            $tcTexts = [
                "I, the candidate/parent or guardian of the candidate declare that the information provided in this registration form is true and correct. I understand that any false or misleading information may result in the cancellation of the candidate's exam registration.",
                "I understand that the candidate is only permitted to sit for the subjects and papers registered on this system."
            ];
            $textTC = "\n\nTerms & Conditions Accepted:\n";
            foreach ($tcTexts as $t) {
                $textTC .= " - $t\n";
            }

            $htmlTC = '<h4>Terms & Conditions Accepted</h4><ul>';
            foreach ($tcTexts as $t) {
                $htmlTC .= '<li>'.htmlspecialchars($t).'</li>';
            }
            $htmlTC .= '</ul>';

            // Fee notice
            $feeNoticeText = "\n\nFees Notice:\n"
                          . "Examination fees will be included in your next invoice.\n"
                          . "If you have any questions, please contact us at 03-7809 7000 or email to exams@kl.his.edu.my\n";

            $feeNoticeHtml = '<div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107;">'
                          . '<h4 style="margin-top: 0;">Fees Notice:</h4>'
                          . '<p>Examination fees will be <strong>included in your next invoice</strong>.</p>'
                          . '<p>If you have any questions, please contact us at <strong>03-7809 7000</strong> or email to '
                          . '<strong><a href="mailto:exams@kl.his.edu.my">exams@kl.his.edu.my</a></strong></p>'
                          . '</div>';

            // Send email using SendGrid
            $sendgrid  = new \SendGrid(apikey);
            $fromEmail = 'ExamSystem@em7140.kl.his.edu.my';
            $fromName = 'Exam Registration System';
            $subject = "📢 Subject Registration Confirmation - {$studentName} {$studentDisplay}";

            $bodyText = "Dear Candidate/Parent/Guardian,\n\n"
                     . "⚠️ THIS IS AN AUTOMATED EMAIL - DO NOT REPLY TO THIS ADDRESS\n"
                     . "For questions, contact us at 03-7809 7000 or exams@kl.his.edu.my\n\n"
                     . "Subject registration confirmation for: {$studentName} {$studentDisplay}\n\n"
                     . "Here is the current list of registered subjects:\n\n"
                     . $textList
                     . $declarationInfo
                     . $textTC
                     . $feeNoticeText
                     . "\nThank you,\nExam Registration System";
                     
            $bodyHtml = '<div style="background-color: #dc3545; color: white; padding: 8px 15px; margin-bottom: 20px; text-align: center; border-radius: 0px; font-weight: bold; font-size: 12px; text-transform: uppercase;">'
                     . 'THIS IS AN AUTOMATICALLY GENERATED EMAIL. PLEASE DO NOT REPLY'
                     . '</div>'
                     . '<div style="padding: 15px; text-align: center; font-size: 14px; line-height: 1.4;">'
                     . 'If you have any questions or require further clarification, please do<br>'
                     . 'not hesitate to contact us at:<br><br>'
                     . '📞 <strong>03-78097000</strong><br>'
                     . '📧 <strong><a href="mailto:exams@kl.his.edu.my" style="color: #0066cc; text-decoration: none;">exams@kl.his.edu.my</a></strong>'
                     . '</div>'
                     . '<p>Dear Student/Parent,</p>'
                     . '<p><strong>Subject registration confirmation for:</strong> ' . htmlspecialchars($studentName . ' ' . $studentDisplay) . '</p>'
                     . '<p>Here is the current list of registered subjects:</p>'
                     . $htmlTable
                     . $declarationInfoHtml
                     . $htmlTC
                     . $feeNoticeHtml
                     . '<p>Thank you,<br>Exam Registration System</p>';

            foreach ($recipients as $to) {
                $mail = new \SendGrid\Mail\Mail();
                $mail->setFrom($fromEmail, $fromName);
                $mail->setSubject($subject);
                $mail->addTo($to);
                $mail->addContent('text/plain', $bodyText);
                $mail->addContent('text/html', $bodyHtml);
                try {
                    $response = $sendgrid->send($mail);
                    // You can log this if needed: echo "<p>✅ Sent to <strong>{$to}</strong> – Status: {$response->statusCode()}</p>";
                } catch (Exception $e) {
                    // You can log this if needed: echo "<p>❌ Failed to send to <strong>{$to}</strong>: {$e->getMessage()}</p>";
                }
            }
            
            // Redirect to payment summary
            header("Location: student_viewfees.php?registration_complete=1");
            exit;
        }
    }
}

// Calculate series info for display
$today = new DateTime();
$year = (int)$today->format('Y');
$july = new DateTime("$year-07-01");
$dow = (int)$july->format('N');
$offset = ($dow === 1) ? 0 : (8 - $dow);
$firstMonday = (clone $july)->modify("+{$offset} days");
$thirdMonday = (clone $firstMonday)->modify('+14 days');
$seriesYear = ($today >= $thirdMonday) ? $year : $year;
$month = (int)$today->format('n');
$day = (int)$today->format('j');

if ($month <= 6) {
    $seriesMonth = "JUNE";
} elseif ($month >= 7 && $month < 12) {
    $seriesMonth = "NOVEMBER";
} elseif ($month == 12 && $day <= 7) {
    $seriesMonth = "NOVEMBER";
} else {
    $seriesMonth = "JUNE";
    $seriesYear += 1;
}

// Get current series from database
$stmt = $conn->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'current_series'");
$stmt->execute();
$current_series = $stmt->get_result()->fetch_assoc()['setting_value'];
$stmt->close();
$examSeriesTitle = "Registration for $current_series Exam Series";

?>

<!-- Include CSS -->
<link rel="stylesheet" href="confirm_registration.css">

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h3 class="text-center mb-4">📋 Confirm Subject Registration</h3>
            <h5 class="text-center text-muted mb-4"><?= $examSeriesTitle ?></h5>
            <!--<h5 class="text-center text-muted mb-4"><?= $seriesMonth ?> <?= $seriesYear ?> Series</h5>-->
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Registration Summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📚 Selected Subjects Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Code</th>
                                    <th>Exam Type</th>
                                    <?php if (!empty($retake_status)): ?>
                                        <th class="text-center">Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selected_subjects as $subject): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                        <td><code><?= htmlspecialchars($subject['code']) ?></code></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($subject['exam_type']) ?></span></td>
                                        <?php if (!empty($retake_status)): ?>
                                            <td class="text-center">
                                                <?php
                                                $is_retake = isset($retake_status[$subject['option_code_id']]) && $retake_status[$subject['option_code_id']] === '1';
                                                echo $is_retake ? '<span class="badge bg-warning">Retake</span>' : '<span class="badge bg-success">New</span>';
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3">
                        <strong>Note:</strong> Detailed fee information will be available on the payment summary page after registration.
                    </div>
                </div>
            </div>
            
            <!-- Declaration Form -->
            <form method="POST" id="confirmationForm" novalidate>
                <!-- Terms & Conditions -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">📜 Terms & Conditions</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="declaration_1" name="declaration_1" required>
                            <label class="form-check-label" for="declaration_1">
                                <span class="text-danger">*</span>
                                I, the candidate/parent or guardian of the candidate declare that the information provided in this registration form is true and correct. I understand that any false or misleading information may result in the cancellation of the candidate's exam registration.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="declaration_2" name="declaration_2" required>
                            <label class="form-check-label" for="declaration_2">
                                <span class="text-danger">*</span>
                                I understand that the candidate is only permitted to sit for the subjects and papers registered on this system.
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">📞 Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="student_mobile" class="form-label">Student Mobile Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="student_mobile" name="student_mobile" 
                                       value="<?= htmlspecialchars($student_info['student_mobile'] ?? '') ?>" 
                                       placeholder="+60123456789" required>
                                <div class="form-text">Include country code (e.g., +60 for Malaysia)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="personal_email" class="form-label">Personal Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="personal_email" name="personal_email" 
                                       value="<?= htmlspecialchars($student_info['personal_email'] ?? '') ?>" 
                                       placeholder="student@example.com" required>
                                <div class="form-text">This will be used for recording purposes.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Language Information -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">🌐 Language Information</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Is English your first language? <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="english_first_language" id="english_yes" value="yes" required>
                            <label class="form-check-label" for="english_yes">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="english_first_language" id="english_no" value="no" required>
                            <label class="form-check-label" for="english_no">No</label>
                        </div>
                    </div>
                </div>
                
                <!-- Access Arrangements -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📝 Access Arrangement Applications</h5>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Does your child/ward require any special needs due to medical, physical, mental conditions or other ill health issues such as dyslexia or hearing difficulties? <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="special_needs" id="special_yes" value="yes" required>
                            <label class="form-check-label" for="special_yes">Yes</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="special_needs" id="special_no" value="no" required>
                            <label class="form-check-label" for="special_no">No</label>
                        </div>
                        
                        <div id="special_needs_details_section" style="display: none;">
                            <label for="special_needs_details" class="form-label">Please provide details about the special needs requirements:</label>
                            <textarea class="form-control" id="special_needs_details" name="special_needs_details" rows="3" 
                                      placeholder="Describe the specific accommodations needed..."></textarea>
                            <div class="alert alert-info mt-2">
                                <strong>📧 Important:</strong> Please send proper medical documentation to support this request to 
                                <strong><a href="mailto:exams@kl.his.edu.my">exams@kl.his.edu.my</a></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Relationship and Signature -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">✍️ Declaration & Signature</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Relationship to Candidate <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship" id="rel_self" value="Self" required>
                                    <label class="form-check-label" for="rel_self">Self</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship" id="rel_parent" value="Parent" required>
                                    <label class="form-check-label" for="rel_parent">Parent</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="relationship" id="rel_guardian" value="Guardian" required>
                                    <label class="form-check-label" for="rel_guardian">Guardian</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="relationship" id="rel_other" value="Other" required>
                                    <label class="form-check-label" for="rel_other">Other</label>
                                </div>
                                <div id="relationship_other_section" style="display: none;">
                                    <input type="text" class="form-control" id="relationship_other" name="relationship_other" 
                                           placeholder="Please specify relationship">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="signed_name" class="form-label">Full Name (Print) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="signed_name" name="signed_name" 
                                           placeholder="Enter your full name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="signed_date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="signed_date" name="signed_date" 
                                           value="<?= date('Y-m-d') ?>" readonly required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong>Digital Signature:</strong> By submitting this form, you are providing your digital signature 
                            and confirming that all information provided is accurate and complete.
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body text-center">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <button type="submit" name="cancel_registration" class="btn btn-outline-secondary w-100">
                                    ← Cancel Registration
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="user_dashboard.php" class="btn btn-outline-primary w-100">
                                    🏠 Go Home
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button type="submit" name="finish_registration" class="btn btn-success w-100" id="finishBtn">
                                    ✅ Finish Registration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="confirm_registration.js"></script>

</body>
</html>
