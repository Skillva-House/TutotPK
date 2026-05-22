<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// Auth Guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$student_id   = $_SESSION['id'];
$tutor_id     = (int) ($_POST['tutor_id'] ?? 0);
$subject_name = trim($_POST['subject_name'] ?? '');
$schedule_id  = (int) ($_POST['schedule_id'] ?? 0);
$package_type = $_POST['package_type'] ?? '1';

$allowed_packages = ['1', '2', '5', '12'];
if (!in_array($package_type, $allowed_packages, true)) {
    $package_type = '1';
}

if ($tutor_id > 0 && !empty($subject_name) && $schedule_id > 0) {
    // Active enrollment rule:
    // 1) Student cannot duplicate if currently active.
    // 2) If previous enrollment is non-active (left/completed/cancelled), reactivate it for re-enrollment.
    $check_stmt = $conn->prepare("SELECT id, status, payment_status FROM enrollments WHERE student_id = ? AND tutor_id = ? AND subject_name = ? ORDER BY id DESC LIMIT 1");
    $check_stmt->bind_param('iis', $student_id, $tutor_id, $subject_name);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    if ($existing) {
        $existing_id = (int)$existing['id'];
        $existing_status = $existing['status'] ?? null;
        $is_active = ($existing_status === 'active' || $existing_status === null || $existing_status === '');

        if ($is_active) {
            $_SESSION['enroll_msg'] = "You are already enrolled in " . htmlspecialchars($subject_name) . ".";
            $_SESSION['enroll_type'] = 'error';
            header("Location: tutor_profile.php?id=" . $tutor_id);
            exit();
        }

        $reactivate_stmt = $conn->prepare("UPDATE enrollments SET package_type = ?, payment_status = 'unpaid', paid_amount = 0, status = 'active', schedule_id = ? WHERE id = ?");
        $reactivate_stmt->bind_param('sii', $package_type, $schedule_id, $existing_id);

        if ($reactivate_stmt->execute()) {
            $reactivate_stmt->close();
            $check_stmt->close();

            // Set a session message indicating re-enrollment
            $_SESSION['enroll_msg'] = "Welcome back! You are re-enrolling in " . htmlspecialchars($subject_name) . ". Please complete the payment.";
            $_SESSION['enroll_type'] = 'success';

            header("Location: payment.php?enroll_id=" . $existing_id . "&schedule_id=" . $schedule_id);
            exit();
        }
        $reactivate_stmt->close();

        $_SESSION['enroll_msg'] = "Error: Could not re-initiate enrollment.";
        $_SESSION['enroll_type'] = 'error';
        $check_stmt->close();
        header("Location: tutor_profile.php?id=" . $tutor_id);
        exit();
    }
    $check_stmt->close();

    // Create a PENDING enrollment
    $enroll_sql = "INSERT INTO enrollments (student_id, tutor_id, subject_name, package_type, payment_status, schedule_id) VALUES (?, ?, ?, ?, 'unpaid', ?)";
    $enroll_stmt = $conn->prepare($enroll_sql);
    $enroll_stmt->bind_param('iissi', $student_id, $tutor_id, $subject_name, $package_type, $schedule_id);

    try {
        if ($enroll_stmt->execute()) {
            $enroll_id = $enroll_stmt->insert_id;
            $enroll_stmt->close();
            header("Location: payment.php?enroll_id=" . $enroll_id . "&schedule_id=" . $schedule_id);
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        // Safety for existing unique key (student_id, tutor_id, subject_name):
        // if duplicate occurs, try to reactivate non-active row instead of fatal error.
        if ((int)$e->getCode() === 1062) {
            $fallback_stmt = $conn->prepare("SELECT id, status FROM enrollments WHERE student_id = ? AND tutor_id = ? AND subject_name = ? ORDER BY id DESC LIMIT 1");
            $fallback_stmt->bind_param('iis', $student_id, $tutor_id, $subject_name);
            $fallback_stmt->execute();
            $fallback = $fallback_stmt->get_result()->fetch_assoc();
            $fallback_stmt->close();

            if ($fallback) {
                $fallback_id = (int)$fallback['id'];
                $fallback_status = $fallback['status'] ?? null;
                $fallback_active = ($fallback_status === 'active' || $fallback_status === null || $fallback_status === '');

                if (!$fallback_active) {
                    $recover_stmt = $conn->prepare("UPDATE enrollments SET package_type = ?, payment_status = 'unpaid', paid_amount = 0, status = 'active', schedule_id = ? WHERE id = ?");
                    $recover_stmt->bind_param('sii', $package_type, $schedule_id, $fallback_id);
                    if ($recover_stmt->execute()) {
                        $recover_stmt->close();
                        $enroll_stmt->close();
                        header("Location: payment.php?enroll_id=" . $fallback_id . "&schedule_id=" . $schedule_id);
                        exit();
                    }
                    $recover_stmt->close();
                }
            }
        }

        $_SESSION['enroll_msg'] = "Error: Could not initiate enrollment.";
        $_SESSION['enroll_type'] = 'error';
    }
    $enroll_stmt->close();
} else {
    $_SESSION['enroll_msg'] = "Invalid enrollment request.";
}

header("Location: tutor_profile.php?id=" . $tutor_id);
exit();
?>
