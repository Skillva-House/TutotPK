<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'TutorPk Assistant';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor';
$active_page = 'assistant';
$tutor_id    = (int) $_SESSION['id'];

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<!-- MarkDown Support -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<link rel="stylesheet" href="tutor.css?v=<?php echo time(); ?>">
<style>
    .main {
        padding-top: 0;
    }
    #assistant-chat ul, #assistant-chat ol {
        margin: 10px 0;
        padding-left: 25px;
    }
    #assistant-chat li {
        margin-bottom: 8px;
    }
    #assistant-chat pre {
        background: #1e293b;
        color: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        overflow-x: auto;
        margin: 10px 0;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 0.85rem;
        line-height: 1.5;
    }
    #assistant-chat code {
        background: rgba(0,0,0,0.05);
        padding: 2px 4px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 600;
    }
    #assistant-chat pre code {
        background: transparent;
        padding: 0;
        font-weight: 400;
        color: inherit;
    }
    #assistant-chat strong {
        color: #1e293b;
        font-weight: 700;
    }
    .message-bubble {
        display: inline-block;
        padding: 14px 20px;
        border-radius: 12px;
        max-width: 85%;
        word-wrap: break-word;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .user-msg {
        background: #7c3aed;
        color: #fff;
    }
    .assistant-msg {
        background: #fff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
</style>

<div style="background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow: hidden; display: flex; flex-direction: column; height: calc(100vh - 85px); width: 100%;">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 12px; padding: 20px 24px; border-bottom: 2px solid #f1f5f9; flex-shrink: 0;">
        <span style="font-size: 2rem;">🤖</span>
        <div>
            <h1 style="margin: 0; font-size: 1.4rem; font-weight: 800; color: #1e293b;">TutorPk Assistant</h1>
            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Ask questions and get instant teaching support.</p>
        </div>
    </div>

    <!-- Chat Area -->
    <div id="assistant-chat" style="flex: 1; background: #f8fafc; padding: 16px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px;">
        <div style="text-align: center; color: #94a3b8; padding: 20px;">
            <p style="font-size: 0.9rem;">Start chatting to see your responses here.</p>
        </div>
    </div>

    <!-- Input Area -->
    <div style="display: flex; gap: 10px; padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #fff; flex-shrink: 0;">
        <input type="text" id="assistant-input" placeholder="Ask me anything..." style="flex: 1; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 0.9rem; outline: none; transition: all 0.2s;">
        <button id="assistant-send" style="padding: 12px 24px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);">
            Send
        </button>
    </div>
</div>

<!-- Upgrade Modal -->
<div id="upgradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 90%; max-width: 450px; border-radius: 24px; padding: 40px 30px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); position: relative;">
        <span onclick="closeModal()" style="position: absolute; top: 20px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #94a3b8;">&times;</span>
        <div style="font-size: 4rem; margin-bottom: 20px;">🤖</div>
        <h2 style="margin-bottom: 10px; color: #1e293b; font-weight: 800;">Limit Reached!</h2>
        <p id="limit-message" style="color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px;">
            You've used all your 5 free prompts for today. Upgrade to get unlimited access!
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="../student/chatbot_upgrade.php" style="display: block; padding: 16px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 10px 20px rgba(124, 58, 237, 0.2);">
                Upgrade for Rs 200/Year
            </a>
            <button onclick="closeModal()" style="padding: 14px; background: #f1f5f9; color: #475569; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
                Maybe Later
            </button>
        </div>
        <p style="margin-top: 20px; font-size: 0.8rem; color: #94a3b8;">Wait 24h or upgrade to continue chatting.</p>
    </div>
</div>

<script>
const chatDiv = document.getElementById('assistant-chat');
const inputField = document.getElementById('assistant-input');
const sendBtn = document.getElementById('assistant-send');
const endpoint = '../ajax/assistant_ask.php';

sendBtn.addEventListener('click', sendMessage);
inputField.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
});

loadHistory();

async function loadHistory() {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'history' })
        });
        const data = await response.json();
        if (!data.success || !Array.isArray(data.history) || data.history.length === 0) {
            return;
        }

        chatDiv.innerHTML = '';
        data.history.forEach(item => {
            appendMessage(item.question, true);
            appendMessage(item.answer, false);
        });
        chatDiv.scrollTop = chatDiv.scrollHeight;
    } catch (error) {
        console.error('History load failed', error);
    }
}

async function sendMessage() {
    const msg = inputField.value.trim();
    if (!msg) return;

    appendMessage(msg, true);

    inputField.value = '';
    inputField.focus();
    chatDiv.scrollTop = chatDiv.scrollHeight;
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending...';

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: msg })
        });

        const data = await response.json();
        if (data.success && data.answer) {
            appendMessage(data.answer, false);
        } else if (data.limit_reached) {
            document.getElementById('limit-message').textContent = data.message;
            document.getElementById('upgradeModal').style.display = 'flex';
        } else {
            appendMessage(data.message || 'Sorry, I could not process your question.', false);
        }
    } catch (error) {
        appendMessage('Network error. Please try again.', false);
    } finally {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send';
        chatDiv.scrollTop = chatDiv.scrollHeight;
    }
}

function closeModal() {
    document.getElementById('upgradeModal').style.display = 'none';
}

function appendMessage(text, isUser) {
    const wrapper = document.createElement('div');
    wrapper.style.cssText = isUser ? 'text-align: right; margin: 12px 0;' : 'text-align: left; margin: 12px 0;';
    
    // Parse Markdown for assistant, or just escape for user
    const content = isUser ? escapeHtml(text) : marked.parse(text);
    const bubbleClass = isUser ? 'user-msg' : 'assistant-msg';
    
    wrapper.innerHTML = `<div class="message-bubble ${bubbleClass}">${content}</div>`;
    chatDiv.appendChild(wrapper);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
