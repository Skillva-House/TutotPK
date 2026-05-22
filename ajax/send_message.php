<?php
session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sender_id = $_SESSION['id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

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

if (empty($message_text) || ($receiver_id <= 0 && $group_id <= 0)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

if ($group_id > 0) {
    // If sender previously hid this group, unhide it on new outgoing message.
    $conn->query("DELETE FROM user_hidden_conversations WHERE user_id = $sender_id AND conversation_type = 'group' AND conversation_id = $group_id");

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, group_id, message_text) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $sender_id, $group_id, $message_text);
} else {
    // If sender previously hid this private chat, unhide it on new outgoing message.
    $conn->query("DELETE FROM user_hidden_conversations WHERE user_id = $sender_id AND conversation_type = 'private' AND conversation_id = $receiver_id");

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $sender_id, $receiver_id, $message_text);
}

if ($stmt->execute()) {
    // If it's a report sent to the Admin, track it in tutor_reports table
    if (isset($_POST['type']) && $_POST['type'] === 'report') {
        // Find if this is an admin
        $chk = $conn->query("SELECT id FROM users WHERE id = $receiver_id AND role = 'admin' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            // Check if there's already an active report for this student
            $chk_rep = $conn->query("SELECT id FROM tutor_reports WHERE student_id = $sender_id AND status = 'pending' LIMIT 1");
            if ($chk_rep && $chk_rep->num_rows === 0) {
                // Create a report record. 
                $reported_tid = isset($_POST['reported_tutor_id']) ? (int)$_POST['reported_tutor_id'] : null;
                $rep_stmt = $conn->prepare("INSERT INTO tutor_reports (student_id, tutor_id, report_reason, status) VALUES (?, ?, ?, 'pending')");
                $reason = "Reported via chat: " . mb_strimwidth($message_text, 0, 100, "...");
                $rep_stmt->bind_param('iis', $sender_id, $reported_tid, $reason);
                $rep_stmt->execute();
                $rep_stmt->close();
            }
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$stmt->close();
?>
