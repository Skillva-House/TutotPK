<?php
session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$uid = (int)$_SESSION['id'];

// User-specific hidden conversations table (soft delete from one user's view only)
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

// Unified query to fetch both private and group conversations.
// Using explicit CAST and COLLATE to avoid "Illegal mix of collations" error in UNION.
$query = "
SELECT * FROM (
    -- 1. Private Conversations
    SELECT DISTINCT
        u.id, 
        CAST(u.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as name, 
        CAST(u.photo_file AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as photo_file,
        CAST(COALESCE(m.message_text, 'Start a conversation!') AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as last_message,
        COALESCE(m.created_at, '2000-01-01 00:00:00') as last_time,
        CAST('private' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as type,
        (SELECT COUNT(*) FROM messages m3 WHERE m3.receiver_id = $uid AND m3.sender_id = u.id AND m3.is_read = 0 AND m3.group_id IS NULL) as unread_count
    FROM users u
    LEFT JOIN enrollments e ON (e.student_id = u.id OR e.tutor_id = u.id)
    LEFT JOIN (
        SELECT 
            CASE WHEN m_outer.sender_id = $uid THEN m_outer.receiver_id ELSE m_outer.sender_id END as other_id,
            MAX(m_outer.id) as last_id
        FROM messages m_outer
        WHERE (m_outer.sender_id = $uid OR m_outer.receiver_id = $uid) AND m_outer.group_id IS NULL
        GROUP BY other_id
    ) msg_meta ON u.id = msg_meta.other_id
    LEFT JOIN messages m ON m.id = msg_meta.last_id
    WHERE u.id != $uid AND (
        (e.payment_status = 'paid' AND (e.student_id = $uid OR e.tutor_id = $uid))
        OR msg_meta.last_id IS NOT NULL
        OR (u.role = 'admin' AND u.name = 'Admin_Panel')
    )
            AND NOT EXISTS (
                    SELECT 1
                    FROM user_hidden_conversations uhc
                    WHERE uhc.user_id = $uid
                        AND uhc.conversation_type = 'private'
                        AND uhc.conversation_id = u.id
            )

    UNION ALL

    -- 2. Group Conversations
    SELECT 
        g.group_id as id, 
        CAST(g.subject_name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as name, 
        CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as photo_file,
        CAST(COALESCE(mg.message_text, 'Welcome to the group!') AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as last_message,
        COALESCE(mg.created_at, '2000-01-01 00:00:01') as last_time,
        CAST('group' AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci as type, 
        (SELECT COUNT(*) FROM messages m4 WHERE m4.group_id = g.group_id AND m4.id > gm.last_read_message_id) as unread_count
    FROM chat_groups g
    JOIN chat_group_members gm ON g.group_id = gm.group_id
    LEFT JOIN (
        SELECT m2.group_id, m2.message_text, m2.created_at 
        FROM messages m2
        WHERE m2.id IN (SELECT MAX(id) FROM messages WHERE group_id IS NOT NULL GROUP BY group_id)
    ) mg ON g.group_id = mg.group_id
    WHERE gm.user_id = $uid
            AND NOT EXISTS (
                    SELECT 1
                    FROM user_hidden_conversations uhc
                    WHERE uhc.user_id = $uid
                        AND uhc.conversation_type = 'group'
                        AND uhc.conversation_id = g.group_id
            )
) as unified_chat
ORDER BY last_time DESC, name ASC
";

try {
    $result = $conn->query($query);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Query error', 'debug' => $e->getMessage()]);
    exit();
}

$conversations = [];
while ($row = $result->fetch_assoc()) {
    $row['last_message'] = escape_output($row['last_message']);
    $conversations[] = $row;
}
echo json_encode(['success' => true, 'conversations' => $conversations]);
?>
