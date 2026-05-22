<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// Guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Student Dashboard';
$role        = 'student';
$user_name   = $_SESSION['name'] ?? 'Student';
$active_page = 'student_dashboard';

// 1. Run Gamification Logic
include_once __DIR__ . '/gamification_logic.php';

// Sync classes_done with actual attendance records to fix any data inconsistency
// This ensures classes_done reflects the actual number of marked-present records
$sync_query = "
    UPDATE enrollments e
    SET e.classes_done = (
        SELECT COUNT(*) FROM attendance a 
        WHERE a.enrollment_id = e.id AND a.status = 'present'
    )
    WHERE e.student_id = " . (int)$_SESSION['id'];
$conn->query($sync_query);

// 2. Fetch enrollment/attendance stats
$enroll_res = $conn->query("SELECT SUM(CASE package_type WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '5' THEN 5 WHEN '12' THEN 12 ELSE 0 END) as total_planned FROM enrollments WHERE student_id = " . (int)$_SESSION['id'] . " AND (payment_status = 'paid' OR payment_status = 'released') AND (status IS NULL OR status != 'left')");
$total_planned = (int)($enroll_res->fetch_assoc()['total_planned'] ?? 0);

// Total classes actually done across all enrollments
$done_res    = $conn->query("SELECT SUM(classes_done) as total_done FROM enrollments WHERE student_id = " . (int)$_SESSION['id']);
$total_done  = (int)($done_res->fetch_assoc()['total_done'] ?? 0);

// Completed courses
$comp_res    = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE student_id = " . (int)$_SESSION['id'] . " AND status = 'completed'");
$total_completed = (int)($comp_res->fetch_assoc()['c'] ?? 0);

$attendance_stats = $conn->query(
    "SELECT
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS total_present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS total_absent
     FROM attendance
     WHERE student_id = " . (int)$_SESSION['id']
)->fetch_assoc();

$total_attended = (int)($attendance_stats['total_present'] ?? 0);
$total_absent = (int)($attendance_stats['total_absent'] ?? 0);
$total_marked = $total_attended + $total_absent;

// Attendance rate is based on marked classes only: present / (present + absent)
$att_percent = ($total_marked > 0) ? floor(($total_attended / $total_marked) * 100) : 0;
if ($att_percent > 100) $att_percent = 100;

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<div class="ad-header">
    <h1>Welcome <?php echo escape_output($user_name); ?> to your learning journey</h1>
</div>

<!-- ══ Gamification Row ══ -->
<div class="gm-dashboard-grid">
    
    <!-- Left: Streak & XP -->
    <div class="gm-status-card">
        <div class="gm-card-title">
            <span>Level Progress</span>
            <span class="gm-lvl-tag" style="background:#f59e0b;">Level <?php echo (int)$level; ?></span>
        </div>
        
        <div class="gm-xp-bar-outer">
            <div class="gm-xp-bar-inner" style="width: <?php echo (int)$xp_percent; ?>%;"></div>
        </div>
        <div class="gm-xp-stats">
            ✨ <?php echo (int)$xp % 1000; ?> / 1000 XP to Level <?php echo (int)$level + 1; ?>
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #f1f5f9;">

        <div class="gm-streak-wrap">
            <div class="gm-fire-icon">🔥</div>
            <div>
                <div class="gm-streak-val"><?php echo (int)$streak; ?></div>
                <div class="gm-streak-lbl">Day Streak</div>
            </div>
        </div>
    </div>

    <!-- Right: Attendance & Badges -->
    <div class="gm-status-card">
        <div class="gm-card-title">
            <span>Attendance Rate</span>
            <span class="gm-lvl-tag" style="background:#10b981;"><?php echo (int)$att_percent; ?>%</span>
        </div>
        
        <div class="gm-xp-bar-outer">
            <div class="gm-xp-bar-inner" style="width: <?php echo (int)$att_percent; ?>%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
        </div>
        <div class="gm-xp-stats">
            📊 <?php echo (int)$total_attended; ?> / <?php echo (int)$total_planned; ?> Planned Classes (<?php echo (int)$total_absent; ?> absent)
        </div>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #f1f5f9;">

        <div class="gm-badge-case">
            <?php if (empty($earned_badges)): ?>
                <div class="gm-badge-item empty" title="Locked">
                    🔒 <span class="gm-badge-name">First Badge</span>
                </div>
            <?php else: ?>
                <?php foreach ($earned_badges as $bg): ?>
                    <div class="gm-badge-item" title="<?php echo escape_output($bg['badge_name']); ?>">
                        <?php echo $bg['badge_icon']; ?>
                        <span class="gm-badge-name"><?php echo str_replace('_', ' ', $bg['badge_name']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══ Quick Stats ══ -->
<div class="ad-stats-grid" style="margin-top: 25px;">
    <div class="ad-stat-card">
        <span class="ad-stat-label">Active Enrollments</span>
        <?php
            $active_res   = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE student_id = " . (int)$_SESSION['id'] . " AND (payment_status = 'paid' OR payment_status = 'released') AND (status IS NULL OR status = 'active')");
            $active_count = $active_res->fetch_assoc()['c'] ?? 0;
        ?>
        <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$active_count; ?></span>
    </div>
    <div class="ad-stat-card">
        <span class="ad-stat-label">Classes Done</span>
        <span class="ad-stat-value" style="color: #10b981;"><?php echo $total_done; ?></span>
    </div>
    <div class="ad-stat-card">
        <span class="ad-stat-label">Courses Completed</span>
        <span class="ad-stat-value" style="color: #f59e0b;"><?php echo $total_completed; ?></span>
    </div>
    <div class="ad-stat-card">
        <span class="ad-stat-label">Total Points</span>
        <span class="ad-stat-value" style="color: #10b981;"><?php echo number_format($xp); ?></span>
    </div>
    <div class="ad-stat-card">
        <span class="ad-stat-label">Messages</span>
        <?php
            $msg_res = $conn->query("SELECT COUNT(*) as c FROM messages WHERE receiver_id = " . (int)$_SESSION['id'] . " AND is_read = 0");
            $unread  = $msg_res->fetch_assoc()['c'] ?? 0;
        ?>
        <span class="ad-stat-value" style="color: #f59e0b;"><?php echo (int)$unread; ?></span>
    </div>
</div>

<!-- <div class="student-actions">
    <a href="search_tutor.php" class="st-view-btn primary">+ Find a New Tutor</a>
    <a href="chat.php" class="st-view-btn">💬 My Chats</a>
</div> -->


<?php
include_once __DIR__ . '/../includes/footer.php';
?>

