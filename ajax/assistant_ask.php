<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');

// --- CONFIGURATION ---
$GROQ_API_KEY   = 'YOUR_GROQ_API_KEY';
$GEMINI_API_KEY = 'YOUR_GEMINI_API_KEY';

if (!isset($_SESSION['id'], $_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'tutor'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int) $_SESSION['id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// --- CHATBOT LIMIT CHECK ---
$is_premium = false;
$premium_stmt = $conn->prepare("SELECT id FROM chatbot_upgrades WHERE user_id = ? AND status = 'approved' AND plan_end > NOW() LIMIT 1");
$premium_stmt->bind_param("i", $user_id);
$premium_stmt->execute();
if ($premium_stmt->get_result()->num_rows > 0) {
    $is_premium = true;
}
$premium_stmt->close();

if (!$is_premium) {
    // Count prompts in last 24 hours
    $count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM chatbot_usage WHERE user_id = ? AND used_at >= NOW() - INTERVAL 24 HOUR");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $res = $count_stmt->get_result()->fetch_assoc();
    $prompts_used = (int)$res['total'];
    $count_stmt->close();

    if ($prompts_used >= 5) {
        // Find when the first prompt of the current 24h window was sent to tell the user when it resets
        $reset_stmt = $conn->prepare("SELECT used_at FROM chatbot_usage WHERE user_id = ? AND used_at >= NOW() - INTERVAL 24 HOUR ORDER BY used_at ASC LIMIT 1");
        $reset_stmt->bind_param("i", $user_id);
        $reset_stmt->execute();
        $reset_res = $reset_stmt->get_result()->fetch_assoc();
        $reset_stmt->close();

        $wait_time = "a few hours";
        if ($reset_res) {
            $first_prompt_time = strtotime($reset_res['used_at']);
            $reset_time = $first_prompt_time + (24 * 3600);
            $seconds_left = $reset_time - time();
            if ($seconds_left > 0) {
                $hours = floor($seconds_left / 3600);
                $minutes = ceil(($seconds_left % 3600) / 60);
                $wait_time = ($hours > 0) ? "$hours hours and $minutes minutes" : "$minutes minutes";
            }
        }

        echo json_encode([
            'success' => false, 
            'limit_reached' => true, 
            'message' => "You've used all 5 free prompts for today! Upgrade to Premium for just Rs 200/year to get unlimited access, or wait $wait_time for your free limit to reset.",
            'reset_time' => $wait_time
        ]);
        exit();
    }
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$action = isset($payload['action']) ? (string) $payload['action'] : 'ask';
$session_id = session_id();

// Ensure session exists in DB
$sess_stmt = $conn->prepare('SELECT id FROM chat_sessions WHERE user_id = ? AND session_id = ? LIMIT 1');
$sess_stmt->bind_param('is', $user_id, $session_id);
$sess_stmt->execute();
$sess_stmt->store_result();
if ($sess_stmt->num_rows === 0) {
    $ins_sess = $conn->prepare('INSERT INTO chat_sessions (user_id, session_id, created_at) VALUES (?, ?, NOW())');
    $ins_sess->bind_param('is', $user_id, $session_id);
    $ins_sess->execute();
    $ins_sess->close();
}
$sess_stmt->close();

// --- ACTION: HISTORY ---
if ($action === 'history') {
    $history = [];
    $hist_stmt = $conn->prepare('SELECT question, answer, created_at FROM chat_logs WHERE user_id = ? ORDER BY id DESC LIMIT 20');
    $hist_stmt->bind_param('i', $user_id);
    $hist_stmt->execute();
    $res = $hist_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $history[] = [
            'question' => $row['question'],
            'answer' => $row['answer'],
            'created_at' => $row['created_at'],
        ];
    }
    $hist_stmt->close();
    $history = array_reverse($history);

    echo json_encode([
        'success' => true,
        'history' => $history,
        'session_id' => $session_id,
    ]);
    exit();
}

// --- ACTION: ASK ---
$question = isset($payload['question']) ? trim((string) $payload['question']) : '';
if ($question === '') {
    echo json_encode(['success' => false, 'message' => 'Question is required']);
    exit();
}

if (mb_strlen($question) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Question is too long']);
    exit();
}

$cache_key = mb_strtolower(preg_replace('/\s+/', ' ', $question));

$start = microtime(true);

// 1. Check Cache
$cache_stmt = $conn->prepare('SELECT id, answer FROM chat_cache WHERE question = ? LIMIT 1');
$cache_stmt->bind_param('s', $cache_key);
$cache_stmt->execute();
$cache_res = $cache_stmt->get_result();
$cached = $cache_res->fetch_assoc();
$cache_stmt->close();

if ($cached) {
    $answer = (string) $cached['answer'];
    $cache_hit = true;
    
    $hit_stmt = $conn->prepare('UPDATE chat_cache SET hits = hits + 1 WHERE id = ?');
    $cid = (int) $cached['id'];
    $hit_stmt->bind_param('i', $cid);
    $hit_stmt->execute();
    $hit_stmt->close();
} else {
    $cache_hit = false;
    $system_prompt = $_SESSION['role'] === 'tutor'
        ? 'You are TutorPk Assistant for tutors. Provide practical tutoring help, lesson planning tips, classroom management advice, and concise academic explanations.'
        : 'You are TutorPk Assistant for students. Explain concepts clearly, provide study tips, and keep answers simple and encouraging.';

    $combined_prompt = $system_prompt . "\n\nUser question: " . $question;

    // --- TIERED FAILOVER LOGIC ---
    $answer = null;

    // Tier 1: Groq
    if (!$answer && !empty($GROQ_API_KEY)) {
        $answer = callGroq($combined_prompt, $GROQ_API_KEY);
    }

    // Tier 2: Gemini
    if (!$answer && !empty($GEMINI_API_KEY)) {
        $answer = callGemini($combined_prompt, $GEMINI_API_KEY);
    }

    // Final Fallback
    if (!$answer) {
        $answer = "I'm sorry, I'm currently unable to process your request. Please try again later.";
    } else {
        // Cache NEW successful response
        $insert_cache = $conn->prepare('INSERT INTO chat_cache (question, answer, hits, created_at) VALUES (?, ?, 1, NOW())');
        $insert_cache->bind_param('ss', $cache_key, $answer);
        $insert_cache->execute();
        $insert_cache->close();
    }
}

// Log to DB
$response_time = microtime(true) - $start;
if (!$answer) {
    error_log("TutorPk AI: ALL PROVIDERS FAILED for question: '$question'");
}
$log_stmt = $conn->prepare('INSERT INTO chat_logs (user_id, question, answer, response_time, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
$log_stmt->bind_param('issds', $user_id, $question, $answer, $response_time, $ip);
$log_stmt->execute();
$log_stmt->close();

// Insert into usage tracker
if (!$is_premium) {
    $usage_stmt = $conn->prepare("INSERT INTO chatbot_usage (user_id, used_at) VALUES (?, NOW())");
    $usage_stmt->bind_param("i", $user_id);
    $usage_stmt->execute();
    $usage_stmt->close();
}

echo json_encode([
    'success' => true,
    'answer' => $answer,
    'cache_hit' => $cache_hit,
    'response_time' => round($response_time, 4),
    'session_id' => $session_id,
    'is_premium' => $is_premium
]);

// --- PROVIDER FUNCTIONS ---

function callProviderCommon($url, $method, $headers, $data, $timeout) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Quick fix for XAMPP SSL issues
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("TutorPk AI cURL Error ($url): $curlErr");
    }

    if ($httpCode !== 200) {
        error_log("TutorPk AI HTTP Error ($url): $httpCode - Response: " . substr($response, 0, 100));
    }

    return ($httpCode === 200) ? $response : null;
}

function callGroq($prompt, $apiKey) {
    $data = json_encode([
        'model' => 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7
    ]);
    
    $resp = callProviderCommon(
        'https://api.groq.com/openai/v1/chat/completions',
        'POST',
        ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        $data,
        10
    );

    if ($resp) {
        $decoded = json_decode($resp, true);
        return $decoded['choices'][0]['message']['content'] ?? null;
    }
    return null;
}

function callGemini($prompt, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    $data = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]]
    ]);

    $resp = callProviderCommon($url, 'POST', ['Content-Type: application/json'], $data, 15);

    if ($resp) {
        $decoded = json_decode($resp, true);
        return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
    return null;
}

