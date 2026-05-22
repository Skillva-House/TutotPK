<?php
session_start();
include_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'] ?? '';
$query = trim($query);

if ($query === '') {
    echo json_encode([]);
    exit;
}

// Search for subjects matching the query
$searchTerm = '%' . $query . '%';
$stmt = $conn->prepare("SELECT DISTINCT subject_name FROM tutor_schedule WHERE subject_name LIKE ? LIMIT 10");
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['subject_name'];
}
$stmt->close();

echo json_encode($subjects);
