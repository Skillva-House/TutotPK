<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Chat';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor';
$active_page = 'chat';
$current_user_id = $_SESSION['id'];

$target_id_param = isset($_GET['target_id']) ? $_GET['target_id'] : 0;
if ($target_id_param === 'admin') {
    $res = $conn->query("SELECT id FROM users WHERE role='admin' AND name='Admin_Panel' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $target_id = (int)$row['id'];
    } else {
        $target_id = 0;
    }
} else {
    $target_id = (int)$target_id_param;
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>


<div class="chat-container">
    <!-- Sidebar: Conversation List -->
    <aside class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2>Messages</h2>
        </div>
        <div class="conversation-list" id="conversationList">
            <!-- Loaded via AJAX -->
            <div style="padding: 20px; color: #94a3b8; font-size: 0.9rem;">Loading conversations...</div>
        </div>
    </aside>

    <main class="chat-window">
        <div id="chatWindowDefault" style="flex:1; display:flex; align-items:center; justify-content:center; flex-direction:column; color:#94a3b8;">
            <span style="font-size: 6rem; margin-bottom: 20px;">💬</span>
            <p>Select a student from the list to start chatting</p>
        </div>

        <div id="chatWindowActive" style="display:none; flex:1; flex-direction:column; min-height:0; overflow:hidden;">
            <div class="chat-header">
                <div id="activeChatAvatarContainer"></div>
                <div class="chat-header-name" id="activeChatName">Chatting</div>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <!-- Messages loaded via AJAX -->
            </div>

            <form class="chat-input-area" id="chatForm">
                <input type="text" class="chat-input" id="messageInput" placeholder="Write a message..." autocomplete="off">
                <button type="submit" class="chat-send-btn">Send</button>
            </form>
        </div>
    </main>
</div>

<script>
let currentTargetId = <?php echo $target_id; ?>;
let currentGroupId = 0;
const currentUserId = <?php echo $current_user_id; ?>;

async function fetchConversations() {
    try {
        const res = await fetch('../ajax/get_conversations.php');
        const data = await res.json();
        if (data.success) {
            renderConversations(data.conversations);
        } else {
            console.error('Failed to fetch conversations:', data.message);
        }
    } catch (err) {
        console.error('Error fetching conversations:', err);
    }
}

function renderConversations(conversations) {
    const list = document.getElementById('conversationList');
    if (conversations.length === 0) {
        list.innerHTML = '<div style="padding: 20px; color: #94a3b8; font-size: 0.85rem;">No active chats yet. Groups appear when 2+ students enroll!</div>';
        return;
    }

    list.innerHTML = conversations.map(c => {
        const isGroup = c.type === 'group';
        const isActive = isGroup ? (c.id == currentGroupId) : (c.id == currentTargetId);

        let avatarHtml;
        if (isGroup) {
            avatarHtml = `<div class="conv-avatar-placeholder" style="background: linear-gradient(135deg, #10b981, #3b82f6);">👥</div>`;
        } else {
            avatarHtml = c.photo_file 
                ? `<img src="/tutorpk/${c.photo_file}" class="conv-avatar" onerror="this.onerror=null; this.outerHTML='<div class=\\'conv-avatar-placeholder\\'>👤</div>'">`
                : `<div class="conv-avatar-placeholder">👤</div>`;
        }

        const unreadHtml = c.unread_count > 0 ? `<div class="conv-unread-dot"></div>` : '';

        return `
            <div class="conversation-item ${isActive ? 'active' : ''}" onclick="selectConversation(${c.id}, '${c.name}', '${c.photo_file || ''}', '${c.type}')">
                ${avatarHtml}
                <div class="conv-info">
                    <div class="conv-name-row">
                        <div class="conv-name">${c.name} ${isGroup ? '<small style="font-weight:400; color:#94a3b8;">(Group)</small>' : ''}</div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            ${unreadHtml}
                            <button type="button" class="conv-delete-btn" title="Delete from my chat list" onclick="event.stopPropagation(); deleteConversation(${c.id}, '${c.type}')" aria-label="Delete conversation">
                                <svg viewBox="0 0 24 24" class="conv-delete-svg" aria-hidden="true">
                                    <path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 6h2v9h-2V9zm4 0h2v9h-2V9zM7 9h2v9H7V9z" fill="currentColor"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="conv-last-msg">${c.last_message}</div>
                </div>
            </div>
        `;
    }).join('');
}

async function fetchMessages(forceScroll = false) {
    if (!currentTargetId && !currentGroupId) return;
    
    let url = `../ajax/get_messages.php?`;
    if (currentGroupId > 0) url += `group_id=${currentGroupId}`;
    else url += `target_id=${currentTargetId}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) {
            renderMessages(data.messages, forceScroll);
        } else {
            console.error('Failed to fetch messages:', data.message);
        }
    } catch (err) {
        console.error('Error fetching messages:', err);
    }
}

function renderMessages(messages, forceScroll = false) {
    const area = document.getElementById('messagesArea');
    if (!area) return;
    
    const isAtBottom = area.scrollHeight - area.scrollTop <= area.clientHeight + 150;
    
    area.innerHTML = messages.map(m => {
        const isSent = m.sender_id == currentUserId;
        const senderNameHtml = (currentGroupId > 0 && !isSent) ? `<div style="font-size:0.7rem; font-weight:800; color:#10b981; margin-bottom:4px;">${m.sender_name}</div>` : '';

        return `
            <div class="message-bubble ${isSent ? 'sent' : 'received'}">
                ${senderNameHtml}
                ${m.message_text}
                <span class="message-time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
            </div>
        `;
    }).join('');

    if (forceScroll || isAtBottom) {
        setTimeout(() => { area.scrollTop = area.scrollHeight; }, 50);
    }
}

function selectConversation(id, name, avatar, type) {
    if (type === 'group') {
        currentGroupId = id;
        currentTargetId = 0;
    } else {
        currentTargetId = id;
        currentGroupId = 0;
    }
    
    document.getElementById('chatWindowDefault').style.display = 'none';
    document.getElementById('chatWindowActive').style.display = 'flex';
    document.getElementById('activeChatName').innerText = name + (type === 'group' ? ' (Group Chat)' : '');
    
    const avatarContainer = document.getElementById('activeChatAvatarContainer');
    if (type === 'group') {
        avatarContainer.innerHTML = `<div class="conv-avatar-placeholder" style="width:40px; height:40px; font-size:1.2rem; background: linear-gradient(135deg, #10b981, #3b82f6);">👥</div>`;
    } else if (avatar) {
        avatarContainer.innerHTML = `<img src="/tutorpk/${avatar}" class="conv-avatar" style="width:40px; height:40px;">`;
    } else {
        avatarContainer.innerHTML = `<div class="conv-avatar-placeholder" style="width:40px; height:40px; font-size:1.2rem;">👤</div>`;
    }
    
    document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
    fetchMessages(true);
}

async function deleteConversation(conversationId, type) {
    if (!confirm('Delete this conversation from your side only?')) return;

    const formData = new FormData();
    formData.append('conversation_id', conversationId);
    formData.append('conversation_type', type);

    try {
        const res = await fetch('../ajax/delete_conversation.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            if ((type === 'group' && currentGroupId == conversationId) || (type !== 'group' && currentTargetId == conversationId)) {
                currentGroupId = 0;
                currentTargetId = 0;
                document.getElementById('chatWindowActive').style.display = 'none';
                document.getElementById('chatWindowDefault').style.display = 'flex';
            }
            fetchConversations();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) {
        alert('Network error while deleting conversation.');
    }
}

document.getElementById('chatForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();
    if (!msg || (!currentTargetId && !currentGroupId)) return;

    input.value = '';
    const formData = new FormData();
    if (currentGroupId > 0) formData.append('group_id', currentGroupId);
    else formData.append('receiver_id', currentTargetId);
    formData.append('message', msg);

    const res = await fetch('../ajax/send_message.php', {
        method: 'POST',
        body: formData
    });
    
    const data = await res.json();
    if (data.success) {
        fetchMessages(true);
        fetchConversations();
    }
});

fetchConversations();
if (currentTargetId) {
    document.getElementById('chatWindowDefault').style.display = 'none';
    document.getElementById('chatWindowActive').style.display = 'flex';
    fetchMessages(true);
}

setInterval(fetchMessages, 3000);
setInterval(fetchConversations, 10000);
</script>

<style>
.conv-delete-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    line-height: 1;
    opacity: 1;
    color: #ef4444;
    padding: 2px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.conv-delete-btn:hover {
    opacity: 1;
    color: #dc2626;
}
.conv-delete-svg {
    width: 14px;
    height: 14px;
    display: block;
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
