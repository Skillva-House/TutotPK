<?php
// Ensure DB connection is available
require_once __DIR__ . '/../connect.php';

// $role and $active_page should be set in each page before including this file
if (!isset($role)) {
    $role = $_SESSION['role'] ?? 'student';
}
if (!isset($active_page)) {
    $active_page = 'dashboard';
}

// Simple label for sidebar
$role_label = ucfirst($role);

// --- Fetch Notification Highlights (Static PHP) ---
$current_uid = $_SESSION['id'] ?? 0;
$has_unread_chat = false;
$has_pending_payments = false;
$has_pending_upgrades = false;

// Nav user defaults (fallback if not logged in)
$nav_user_photo = '';
$nav_user_name  = $_SESSION['name'] ?? 'User';

if ($current_uid && isset($conn)) {
    // 1. Check Unread Chat
    $res_priv = $conn->query("SELECT 1 FROM messages WHERE receiver_id = $current_uid AND is_read = 0 LIMIT 1");
    $res_grp  = $conn->query("SELECT 1 FROM messages m JOIN chat_group_members gm ON m.group_id = gm.group_id WHERE gm.user_id = $current_uid AND m.id > gm.last_read_message_id AND m.sender_id != $current_uid LIMIT 1");
    
    if (($res_priv && $res_priv->num_rows > 0) || ($res_grp && $res_grp->num_rows > 0)) {
        $has_unread_chat = true;
    }

    // 2. Check Pending Payments (Admin Only)
    // Use both variable and session for safety
    if ($role === 'admin' || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
        $res_pay = $conn->query("SELECT 1 FROM payments WHERE status = 'paid' LIMIT 1");
        if ($res_pay && $res_pay->num_rows > 0) {
            $has_pending_payments = true;
        }

        $res_upg = $conn->query("SELECT 1 FROM chatbot_upgrades WHERE status = 'pending' LIMIT 1");
        if ($res_upg && $res_upg->num_rows > 0) {
            $has_pending_upgrades = true;
        }
    }
    // 3. Fetch current user's photo and name
    $res_user = $conn->query("SELECT name, photo_file FROM users WHERE id = $current_uid LIMIT 1");
    if ($res_user && $row_user = $res_user->fetch_assoc()) {
        $nav_user_photo = $row_user['photo_file'] ?? '';
        $nav_user_name  = $row_user['name'] ?? $nav_user_name;
    }
}
?>

<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="sidebar-logo">
                <img src="/tutorpk/assets/logos.png" alt="TutorPk Logo" class="logo-img">
            </div>

            <nav class="sidebar-nav">
                <?php if ($role === 'student'): ?>
                    <a href="/tutorpk/student/student_dashboard.php" class="nav-item <?php echo $active_page === 'student_dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span><span>Dashboard</span></a>
                    <a href="/tutorpk/student/search_tutor.php" class="nav-item <?php echo $active_page === 'search_tutor' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-magnifying-glass"></i></span><span>Search Tutor</span></a>
                    <a href="/tutorpk/student/my_classes.php" class="nav-item <?php echo $active_page === 'my_classes' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-book-open"></i></span><span>My Courses</span></a>
                    <a href="/tutorpk/student/chat.php" class="nav-item <?php echo $active_page === 'chat' ? 'active' : ''; ?>" style="position:relative;"><span class="nav-icon"><i class="fa-solid fa-comments"></i></span><span>Chat</span><span class="nav-chat-dot" style="display: <?php echo $has_unread_chat ? 'inline-block' : 'none'; ?>;"></span>
                    </a>                    <a href="/tutorpk/student/assistant.php" class="nav-item <?php echo $active_page === 'assistant' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-robot"></i></span><span>TutorPk Assistant</span></a>
                <?php elseif ($role === 'tutor'): ?>
                    <a href="/tutorpk/tutor/tutor_dashboard.php" class="nav-item <?php echo $active_page === 'tutor_dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span><span>Dashboard</span></a>
                    <a href="/tutorpk/tutor/manage_profile.php" class="nav-item <?php echo $active_page === 'manage_profile' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-user-gear"></i></span><span>Manage Profile</span></a>
                    <a href="/tutorpk/tutor/create_schedule.php" class="nav-item <?php echo $active_page === 'create_schedule' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span><span>Create Schedule</span></a>
                    <a href="/tutorpk/tutor/my_students.php" class="nav-item <?php echo $active_page === 'my_students' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-users"></i></span><span>My Students</span></a>
                    <a href="/tutorpk/tutor/chat.php" class="nav-item <?php echo $active_page === 'chat' ? 'active' : ''; ?>" style="position:relative;"><span class="nav-icon"><i class="fa-solid fa-comments"></i></span><span>Chat</span><span class="nav-chat-dot" style="display: <?php echo $has_unread_chat ? 'inline-block' : 'none'; ?>;"></span>
                    </a>                    <a href="/tutorpk/tutor/assistant.php" class="nav-item <?php echo $active_page === 'assistant' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-robot"></i></span><span>TutorPk Assistant</span></a>                    <a href="/tutorpk/tutor/mark_attendance.php" class="nav-item <?php echo $active_page === 'attendance' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-clipboard-check"></i></span><span>Attendance Tracker</span></a>
                    <a href="/tutorpk/tutor/chat.php?target_id=admin" class="nav-item" style="color:#10b981; margin-top:10px;"><span class="nav-icon"><i class="fa-solid fa-headset"></i></span><span>Admin Support</span></a>
                <?php elseif ($role === 'admin'): ?>
                    <a href="/tutorpk/admin/admin_dashboard.php" class="nav-item <?php echo $active_page === 'admin_dashboard' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span><span>Dashboard</span></a>
                    <a href="/tutorpk/admin/verify_tutors.php" class="nav-item <?php echo $active_page === 'verify_tutors' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-user-check"></i></span><span>Tutor Verification</span></a>
                    <a href="/tutorpk/admin/manage_Tutors.php" class="nav-item <?php echo $active_page === 'manage_tutors' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-users-gear"></i></span><span>Manage Tutors</span></a>
                     <a href="/tutorpk/admin/chat.php" class="nav-item <?php echo $active_page === 'chat' ? 'active' : ''; ?>" style="position:relative;"><span class="nav-icon"><i class="fa-solid fa-comments"></i></span><span>Chat</span><span class="nav-chat-dot" style="display: <?php echo $has_unread_chat ? 'inline-block' : 'none'; ?>;"></span>
                    </a>
                    <a href="/tutorpk/admin/manage_payments.php" class="nav-item <?php echo $active_page === 'manage_payments' ? 'active' : ''; ?>" style="position:relative;"><span class="nav-icon"><i class="fa-solid fa-credit-card"></i></span><span>Payment Monitor</span><span class="nav-chat-dot" style="display: <?php echo $has_pending_payments ? 'inline-block' : 'none'; ?>;"></span>
                    </a>
                    <a href="/tutorpk/admin/manage_chatbot_upgrades.php" class="nav-item <?php echo $active_page === 'chatbot_upgrades' ? 'active' : ''; ?>" style="position:relative;"><span class="nav-icon"><i class="fa-solid fa-rocket"></i></span><span>AI Upgrades</span><span class="nav-chat-dot" style="display: <?php echo $has_pending_upgrades ? 'inline-block' : 'none'; ?>;"></span>
                    </a>
                    <a href="/tutorpk/admin/reports.php" class="nav-item <?php echo $active_page === 'reports' ? 'active' : ''; ?>"><span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span><span>Reports</span></a>
                    
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="/tutorpk/login.php" class="logout-link"><span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Logout</span></a>
            </div>
        </div>
    </aside>

    <div class="main-container" style="flex:1; display:flex; flex-direction:column; min-width: 0;">
        <header class="navbar">
            <div class="navbar-inner">
                <div style="display:flex;align-items:center;gap:10px;">
                    <!-- Logo removed from navbar -->
                </div>
                <div class="nav-meta">
                    <?php 
                        $display_name = $nav_user_name;
                        $initials = strtoupper(mb_substr(trim($display_name), 0, 1));
                    ?>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <?php if (!empty($nav_user_photo)): ?>
                            <img 
                                src="/tutorpk/<?php echo htmlspecialchars($nav_user_photo); ?>" 
                                alt="<?php echo htmlspecialchars($display_name); ?>"
                                style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.3); box-shadow:0 2px 8px rgba(0,0,0,0.3);"
                            >
                        <?php else: ?>
                            <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#10b981,#f59e0b); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; color:#fff; border:2px solid rgba(255,255,255,0.3); box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                                <?php echo $initials; ?>
                            </div>
                        <?php endif; ?>
                        <span style="font-weight:600; color:#fff;"><?php echo htmlspecialchars($display_name); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <main class="main">
