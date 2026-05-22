<?php
// gamification_logic.php
if (!isset($_SESSION['id'])) return;

$uid = (int)$_SESSION['id'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 1. Fetch current status
$res = $conn->query("SELECT xp, level, streak_count, last_login FROM users WHERE id = $uid");
$user_data = $res->fetch_assoc();

$xp = $user_data['xp'] ?? 0;
$level = $user_data['level'] ?? 1;
$streak = $user_data['streak_count'] ?? 0;
$last_l = $user_data['last_login'];

// 2. Calculate Streak
if (!$last_l) {
    // First login
    $streak = 1;
    $xp += 100; // Welcome bonus
    $conn->query("UPDATE users SET streak_count = 1, last_login = '$today', xp = $xp WHERE id = $uid");
} elseif ($last_l === $yesterday) {
    // Continued streak!
    $streak += 1;
    $xp += 10; // Daily Login XP
    $conn->query("UPDATE users SET streak_count = $streak, last_login = '$today', xp = $xp WHERE id = $uid");
} elseif ($last_l !== $today) {
    // Streak broken (or first visit after a gap)
    $streak = 1;
    $xp += 10;
    $conn->query("UPDATE users SET streak_count = 1, last_login = '$today', xp = $xp WHERE id = $uid");
}

// 3. Level Up Logic (Every 1000 XP)
$new_level = floor($xp / 1000) + 1;
if ($new_level > $level) {
    $conn->query("UPDATE users SET level = $new_level WHERE id = $uid");
    $level = $new_level;
}

// 4. Badge Check (30-Day Master)
if ($streak >= 30) {
    $chk_badge = $conn->query("SELECT id FROM user_badges WHERE user_id = $uid AND badge_name = 'streak_30' LIMIT 1");
    if ($chk_badge->num_rows === 0) {
        $conn->query("INSERT INTO user_badges (user_id, badge_name, badge_icon) VALUES ($uid, 'streak_30', '🔥')");
    }
}

// 5. Fetch Badges for UI
$badges_res = $conn->query("SELECT badge_name, badge_icon FROM user_badges WHERE user_id = $uid ORDER BY awarded_at DESC");
$earned_badges = $badges_res->fetch_all(MYSQLI_ASSOC);

// XP for progress bar: current xp towards next level
$xp_this_level = $xp % 1000;
$xp_percent = floor(($xp_this_level / 1000) * 100);
?>
