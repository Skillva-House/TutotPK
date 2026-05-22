<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$enrollment_id = isset($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : 0;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$tutor_id = (int)$_SESSION['id'];
$today = date('Y-m-d');

if (!$enrollment_id || !$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment data']);
    exit();
}

$chk = $conn->query("SELECT id FROM attendance WHERE enrollment_id = $enrollment_id AND class_date = '$today' LIMIT 1");
if ($chk && $chk->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Attendance already marked for today!']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO attendance (enrollment_id, student_id, tutor_id, class_date, status) VALUES (?, ?, ?, ?, 'absent')");
$stmt->bind_param('iiis', $enrollment_id, $student_id, $tutor_id, $today);

if ($stmt->execute()) {
    // Deduct 100 XP but don't allow negative XP.
    $conn->query("UPDATE users SET xp = GREATEST(xp - 100, 0) WHERE id = $student_id");

    $res = $conn->query("SELECT xp FROM users WHERE id = $student_id");
    $u_data = $res ? $res->fetch_assoc() : ['xp' => 0];
    $current_xp = (int)($u_data['xp'] ?? 0);
    $new_level = floor($current_xp / 1000) + 1;
    $conn->query("UPDATE users SET level = $new_level WHERE id = $student_id");

    $progress_stmt = $conn->prepare("SELECT package_type FROM enrollments WHERE id = ? LIMIT 1");
    $progress_stmt->bind_param('i', $enrollment_id);
    $progress_stmt->execute();
    $planned_classes = (int)($progress_stmt->get_result()->fetch_assoc()['package_type'] ?? 0);
    $progress_stmt->close();

    $marked_res = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE enrollment_id = $enrollment_id AND (status = 'present' OR status = 'absent')");
    $marked_classes = (int)($marked_res->fetch_assoc()['c'] ?? 0);
    if ($planned_classes > 0 && $marked_classes > $planned_classes) {
        $marked_classes = $planned_classes;
    }

    echo json_encode([
        'success' => true,
        'progress_text' => $marked_classes . '/' . $planned_classes
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error recording attendance']);
}

$stmt->close();
?>