<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($student_id <= 0) {
    echo json_encode(['has_report' => false]);
    exit();
}

$query = "SELECT id FROM tutor_reports WHERE student_id = $student_id AND status = 'pending' LIMIT 1";
$res = $conn->query($query);

if ($res && $res->num_rows > 0) {
    echo json_encode(['has_report' => true]);
} else {
    echo json_encode(['has_report' => false]);
}
?>
