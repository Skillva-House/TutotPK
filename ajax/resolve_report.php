<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

// Update all pending reports for this student to 'resolved'. 
// We assume 'resolved' is the correct state as per the dashboard query.
$stmt = $conn->prepare("UPDATE tutor_reports SET status = 'resolved' WHERE student_id = ? AND status = 'pending'");
$stmt->bind_param('i', $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
$stmt->close();
?>
