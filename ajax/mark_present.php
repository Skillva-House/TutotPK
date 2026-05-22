<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
$student_id    = isset($_POST['student_id'])    ? (int)$_POST['student_id']    : 0;
$tutor_id      = (int)$_SESSION['id'];
$today         = date('Y-m-d');

if (!$enrollment_id || !$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment data']);
    exit();
}

// 1. Check if already marked for today
$chk = $conn->query("SELECT id FROM attendance WHERE enrollment_id = $enrollment_id AND class_date = '$today' LIMIT 1");
if ($chk && $chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already marked present today!']);
    exit();
}

// 2. Insert Attendance
$stmt = $conn->prepare("INSERT INTO attendance (enrollment_id, student_id, tutor_id, class_date, status) VALUES (?, ?, ?, ?, 'present')");
$stmt->bind_param('iiis', $enrollment_id, $student_id, $tutor_id, $today);

if ($stmt->execute()) {
    // 3. Award +150 XP to Student
    $conn->query("UPDATE users SET xp = xp + 150 WHERE id = $student_id");

    // 4. Update Level based on latest XP
    $res    = $conn->query("SELECT xp, level FROM users WHERE id = $student_id");
    $u_data = $res->fetch_assoc();
    $new_level = floor($u_data['xp'] / 1000) + 1;
    $conn->query("UPDATE users SET level = $new_level WHERE id = $student_id");

    // 5. Increment classes_done on the enrollment
    $conn->query("UPDATE enrollments SET classes_done = classes_done + 1 WHERE id = $enrollment_id");

    // 6. Fetch package total and updated classes_done
    $progress_stmt = $conn->prepare("SELECT package_type, classes_done FROM enrollments WHERE id = ? LIMIT 1");
    $progress_stmt->bind_param('i', $enrollment_id);
    $progress_stmt->execute();
    $enroll_row = $progress_stmt->get_result()->fetch_assoc();
    $progress_stmt->close();

    $pkg_map       = ['1' => 1, '5' => 5, '12' => 12];
    $planned       = $pkg_map[$enroll_row['package_type']] ?? (int)$enroll_row['package_type'];
    $done          = (int)($enroll_row['classes_done'] ?? 0);

    // 7. Auto-mark enrollment as completed when all classes done
    if ($done >= $planned) {
        $conn->query("UPDATE enrollments SET status = 'completed' WHERE id = $enrollment_id");
    }

    echo json_encode([
        'success'       => true,
        'progress_text' => $done . '/' . $planned,
        'classes_done'  => $done,
        'total_classes' => $planned,
        'completed'     => ($done >= $planned),
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error recording attendance']);
}

$stmt->close();
