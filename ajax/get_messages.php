<?php
session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$current_user_id = $_SESSION['id'];
$target_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($target_id <= 0 && $group_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid target']);
    exit();
}

if ($group_id > 0) {
    // Update group read status
    $conn->query("UPDATE chat_group_members SET last_read_message_id = (SELECT MAX(id) FROM messages WHERE group_id = $group_id) WHERE group_id = $group_id AND user_id = $current_user_id");

    // Fetch group messages
    $query = "
        SELECT m.sender_id, m.message_text, m.created_at, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.group_id = ? 
        ORDER BY m.created_at ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $group_id);
} else {
    // Mark messages as read when opening private conversation
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $target_id AND receiver_id = $current_user_id AND is_read = 0");

    // Fetch messages between current user and target user
    $query = "
        SELECT sender_id, message_text, created_at 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiii', $current_user_id, $target_id, $target_id, $current_user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];

while ($row = $result->fetch_assoc()) {
    // If it's a styled system report, don't escape it so the colors show up.
    if (strpos(trim($row['message_text']), "<div style='background: #fff5f5;") === 0) {
        // Keep as is
    } else {
        $row['message_text'] = escape_output($row['message_text']);
    }
    $messages[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'messages' => $messages]);
?>
