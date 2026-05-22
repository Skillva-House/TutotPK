<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$tutor_id = isset($_POST['tutor_id']) ? (int)$_POST['tutor_id'] : 0;
$student_id = (int)$_SESSION['id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

if (!$tutor_id || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating data']);
    exit();
}

// Check if already rated (using ON DUPLICATE KEY UPDATE might be better)
$stmt = $conn->prepare("INSERT INTO tutor_ratings (tutor_id, student_id, rating) VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt->bind_param('iii', $tutor_id, $student_id, $rating);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error recording rating']);
}

$stmt->close();
?>
