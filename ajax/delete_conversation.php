<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['id'];
$conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$conversation_type = isset($_POST['conversation_type']) ? trim($_POST['conversation_type']) : '';

if ($conversation_id <= 0 || !in_array($conversation_type, ['private', 'group'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$conn->query(
    "CREATE TABLE IF NOT EXISTS user_hidden_conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        conversation_type ENUM('private','group') NOT NULL,
        conversation_id INT NOT NULL,
        hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_conversation (user_id, conversation_type, conversation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$stmt = $conn->prepare(
    "INSERT INTO user_hidden_conversations (user_id, conversation_type, conversation_id)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE hidden_at = CURRENT_TIMESTAMP"
);
$stmt->bind_param('isi', $user_id, $conversation_type, $conversation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete conversation']);
}

$stmt->close();
?>