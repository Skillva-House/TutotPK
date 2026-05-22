<?php
session_start();
include_once __DIR__ . '/../connect.php';
$page_title  = 'My Classes';
$role        = 'student';
$user_name   = $_SESSION['name'] ?? 'Student';
$active_page = 'my_classes';

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<?php
$student_id = $_SESSION['id'];

// Sync classes_done with actual attendance records to fix any data inconsistency
// This ensures classes_done reflects the actual number of marked-present records per enrollment
$sync_query = "
    UPDATE enrollments e
    SET e.classes_done = (
        SELECT COUNT(*) FROM attendance a 
        WHERE a.enrollment_id = e.id AND a.status = 'present'
    )
    WHERE e.student_id = " . (int)$student_id;
$conn->query($sync_query);

function format_student_roll_number($student_id) {
    return 'STD-' . str_pad((string)((int)$student_id), 6, '0', STR_PAD_LEFT);
}

$student_roll_number = format_student_roll_number($student_id);

$has_leave_reason_col = false;
$has_left_at_col = false;
$has_left_by_col = false;

$col_res = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'leave_reason'");
if ($col_res && $col_res->num_rows > 0) $has_leave_reason_col = true;

$col_res = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'left_at'");
if ($col_res && $col_res->num_rows > 0) $has_left_at_col = true;

$col_res = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'left_by'");
if ($col_res && $col_res->num_rows > 0) $has_left_by_col = true;

// Handle Delete (archive) Completed Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_completed'])) {
    $enroll_id = (int)$_POST['enroll_id'];
    $stmt = $conn->prepare("UPDATE enrollments SET status = 'left', left_by = 'student', left_at = NOW(), leave_reason = 'Course completed and archived by student.' WHERE id = ? AND student_id = ? AND status = 'completed'");
    $stmt->bind_param('ii', $enroll_id, $student_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $msg = "Course archived successfully.";
        $msg_type = "success";
    } else {
        $msg = "Could not archive course.";
        $msg_type = "error";
    }
    $stmt->close();
}

// Handle Leave Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_course'])) {
    $enroll_id = (int)$_POST['enroll_id'];
    $leave_reason = trim($_POST['leave_reason'] ?? '');

    if ($leave_reason === '' || mb_strlen($leave_reason) < 10) {
        $msg = "Please provide a valid reason (at least 10 characters) before leaving the course.";
        $msg_type = "error";
    } elseif (!$has_leave_reason_col || !$has_left_at_col || !$has_left_by_col) {
        $msg = "Leave-reason columns are missing in database. Run the SQL migration first.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE enrollments SET status = 'left', leave_reason = ?, left_at = NOW(), left_by = 'student' WHERE id = ? AND student_id = ? AND (status IS NULL OR status != 'left')");
        $stmt->bind_param('sii', $leave_reason, $enroll_id, $student_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $msg = "You have successfully left the course.";
            $msg_type = "success";
        } else {
            $msg = "Error leaving the course or course already left.";
            $msg_type = "error";
        }
        $stmt->close();
    }
}

// Handle Report Tutor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_tutor'])) {
    $tutor_id = (int)$_POST['tutor_id'];
    $tutor_name = $_POST['tutor_name'];
    $subject_name = $_POST['subject_name'];
    $report_reason = trim($_POST['report_reason'] ?? '');

    if ($report_reason !== '') {
        $admin_res = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $admin_res->fetch_assoc();
        if ($admin) {
            $admin_id = $admin['id'];
            $msg_content = "<div style='background: #fff5f5; border: 1px solid #feb2b2; border-radius: 12px; padding: 15px; font-family: sans-serif; max-width: 100%;'>
                <div style='color: #c53030; font-weight: 800; font-size: 1.1rem; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;'>
                    <span>🚨</span> TUTOR REPORT
                </div>
                <div style='background: #ffffff; border-radius: 8px; padding: 10px; border: 1px solid #fed7d7;'>
                    <div style='margin-bottom: 5px; font-size: 0.9rem;'><strong style='color: #718096;'>Tutor:</strong> <span style='color: #2d3748;'>$tutor_name (ID: $tutor_id)</span></div>
                    <div style='margin-bottom: 5px; font-size: 0.9rem;'><strong style='color: #718096;'>Course:</strong> <span style='color: #2d3748;'>$subject_name</span></div>
                    <div style='margin-bottom: 5px; font-size: 0.9rem;'><strong style='color: #718096;'>Student Roll No:</strong> <span style='color: #4c51bf;'>$student_roll_number</span></div>
                </div>
                <div style='margin-top: 12px; font-size: 0.95rem; color: #2d3748; line-height: 1.5;'>
                    <strong style='display: block; margin-bottom: 4px; color: #c53030;'>Complaint Detail:</strong>
                    $report_reason
                </div>
                <div style='margin-top: 10px; font-size: 0.75rem; color: #a0aec0; font-style: italic;'>
                    Report generated automatically via TutorPK Secure System
                </div>
            </div>";
            
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $student_id, $admin_id, $msg_content);
            $stmt->execute();
            $stmt->close();
            
            header("Location: chat.php?target_id=" . $admin_id);
            exit();
        }
    }
}

// Fetch Active Enrollments (not completed, not left)
$query_active = "
    SELECT e.*,
           u.name as tutor_name, 
           u.photo_file,
           COALESCE((SELECT COUNT(*) FROM attendance WHERE enrollment_id = e.id AND status = 'present'), 0) as classes_done,
           (SELECT id FROM tutor_schedule WHERE tutor_id = e.tutor_id AND subject_name COLLATE utf8mb4_general_ci = e.subject_name COLLATE utf8mb4_general_ci ORDER BY class_date ASC LIMIT 1) as schedule_id
    FROM enrollments e
    JOIN users u ON e.tutor_id = u.id
    WHERE e.student_id = ? AND (e.status IS NULL OR e.status NOT IN ('left', 'completed'))
    ORDER BY e.created_at DESC
";
$stmt_active = $conn->prepare($query_active);
$stmt_active->bind_param('i', $student_id);
$stmt_active->execute();
$enrollments_active = $stmt_active->get_result();

// Fetch Completed Enrollments
$query_completed = "
    SELECT e.*,
           u.name as tutor_name, 
           u.photo_file,
           COALESCE((SELECT COUNT(*) FROM attendance WHERE enrollment_id = e.id AND status = 'present'), 0) as classes_done,
           (SELECT id FROM tutor_schedule WHERE tutor_id = e.tutor_id AND subject_name COLLATE utf8mb4_general_ci = e.subject_name COLLATE utf8mb4_general_ci ORDER BY class_date ASC LIMIT 1) as schedule_id
    FROM enrollments e
    JOIN users u ON e.tutor_id = u.id
    WHERE e.student_id = ? AND e.status = 'completed'
    ORDER BY e.created_at DESC
";
$stmt_completed = $conn->prepare($query_completed);
$stmt_completed->bind_param('i', $student_id);
$stmt_completed->execute();
$enrollments_completed = $stmt_completed->get_result();
?>

<link rel="stylesheet" href="student.css?v=<?php echo time(); ?>">

<div class="st-section-head">
    <h1 class="st-section-title">My Enrolled Courses</h1>
    <span class="st-result-count"><?php echo $enrollments_active->num_rows; ?> Active Courses</span>
</div>

<?php if (isset($msg)): ?>
    <div class="mc-alert <?php echo $msg_type === 'success' ? 'mc-alert-success' : 'mc-alert-error'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="st-tutor-grid">
    <?php if ($enrollments_active->num_rows > 0): ?>
        <?php while ($row = $enrollments_active->fetch_assoc()):
            $is_paid       = in_array($row['payment_status'], ['paid', 'released']);
            $is_completed  = ($row['status'] === 'completed');
            $pkg_map       = ['1' => 1, '5' => 5, '12' => 12];
            $total_classes = $pkg_map[$row['package_type']] ?? (int)$row['package_type'];
            $done          = (int)($row['classes_done'] ?? 0);
            $progress_pct  = $total_classes > 0 ? min(100, round(($done / $total_classes) * 100)) : 0;

            if ($is_completed) {
                $card_class = 'mc-card mc-card-completed';
            } elseif ($is_paid) {
                $card_class = 'mc-card mc-card-paid';
            } else {
                $card_class = 'mc-card mc-card-unpaid';
            }
        ?>
            <div class="<?php echo $card_class; ?>">

                <!-- Card Top -->
                <div class="mc-card-top">
                    <?php if ($row['photo_file']): ?>
                        <img src="/tutorpk/<?php echo htmlspecialchars($row['photo_file']); ?>" class="mc-avatar">
                    <?php else: ?>
                        <div class="mc-avatar-init">👤</div>
                    <?php endif; ?>
                    <div class="mc-card-info">
                        <div class="mc-tutor-name"><?php echo htmlspecialchars($row['tutor_name']); ?></div>
                        <div class="mc-subject-name"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                    </div>
                    <!-- Status Badge -->
                    <?php if ($is_completed): ?>
                        <span class="mc-badge mc-badge-completed">🎓 DONE</span>
                    <?php elseif ($is_paid): ?>
                        <span class="mc-badge mc-badge-paid">✓ PAID</span>
                    <?php else: ?>
                        <span class="mc-badge mc-badge-unpaid">UNPAID</span>
                    <?php endif; ?>
                </div>

                <!-- Card Meta -->
                <div class="mc-meta">
                    <div class="mc-meta-row">
                        <span class="mc-meta-label">Roll No</span>
                        <span class="mc-meta-val mc-roll"><?php echo htmlspecialchars($student_roll_number); ?></span>
                    </div>
                    <div class="mc-meta-row">
                        <span class="mc-meta-label">Package</span>
                        <span class="mc-meta-val">
                            <?php
                            $pkg_label = ['1' => '1 Class', '5' => '5 Classes', '12' => 'Full Course'];
                            echo $pkg_label[$row['package_type']] ?? $row['package_type'] . ' Class(es)';
                            ?>
                        </span>
                    </div>
                    <div class="mc-meta-row">
                        <span class="mc-meta-label">Amount Paid</span>
                        <span class="mc-meta-val">Rs. <?php echo number_format($row['paid_amount'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <!-- Course Completed Banner -->
                <?php if ($is_completed): ?>
                <div class="mc-completed-banner">
                    <div class="mc-completed-icon">🎓</div>
                    <div class="mc-completed-text">Course Completed!</div>
                    <div class="mc-completed-sub"><?php echo $done; ?> / <?php echo $total_classes; ?> Classes Finished</div>
                </div>
                <?php endif; ?>

                <!-- Progress Bar (only for paid / completed) -->
                <?php if ($is_paid || $is_completed): ?>
                <div class="mc-progress-section">
                    <div class="mc-progress-label">
                        <span>Classes Progress</span>
                        <span class="mc-progress-count"><?php echo $done; ?> / <?php echo $total_classes; ?> Done</span>
                    </div>
                    <div class="mc-progress-bar">
                        <div class="mc-progress-fill <?php echo $is_completed ? 'mc-progress-fill-gold' : ''; ?>" style="width: <?php echo $progress_pct; ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Activate Button for Unpaid -->
                <?php if (!$is_paid && !$is_completed): ?>
                    <?php $pay_url = 'payment.php?enroll_id=' . (int)$row['id'] . '&schedule_id=' . (int)($row['schedule_id'] ?? 0); ?>
                    <a href="<?php echo $pay_url; ?>" class="mc-activate-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Activate &amp; Pay Now
                    </a>
                <?php endif; ?>

                <!-- Archive Button for Completed -->
                <?php if ($is_completed): ?>
                <div class="mc-completed-action-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span>Course completed! Your achievement is preserved.</span>
                </div>
                <?php endif; ?>

                <!-- Action Buttons (hide for completed) -->
                <?php if (!$is_completed): ?>
                <div class="mc-actions">
                    <a href="chat.php?target_id=<?php echo $row['tutor_id']; ?>" class="mc-btn mc-btn-msg">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        Message
                    </a>
                    <button type="button" class="mc-btn mc-btn-leave leave-trigger-btn" data-target="leave-panel-<?php echo (int)$row['id']; ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Leave
                    </button>
                    <button type="button" class="mc-btn mc-btn-report report-trigger-btn" data-target="report-panel-<?php echo (int)$row['id']; ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Report
                    </button>
                </div>
                <?php endif; ?>

                <!-- Leave Panel -->
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="enroll_id" value="<?php echo $row['id']; ?>">
                    <div id="leave-panel-<?php echo (int)$row['id']; ?>" class="mc-expand-panel">
                        <p class="mc-panel-title">Why are you leaving?</p>
                        <textarea name="leave_reason" class="mc-textarea" placeholder="Minimum 10 characters required..." minlength="10" maxlength="500"></textarea>
                        <div class="mc-panel-actions">
                            <button type="submit" name="leave_course" class="mc-panel-btn mc-panel-btn-danger">Confirm Leave</button>
                            <button type="button" class="mc-panel-btn mc-panel-btn-cancel leave-cancel-btn" data-target="leave-panel-<?php echo (int)$row['id']; ?>">Cancel</button>
                        </div>
                    </div>
                </form>

                <!-- Report Panel -->
                <form method="POST" style="margin:0;" id="report-panel-<?php echo (int)$row['id']; ?>" class="mc-expand-panel">
                    <input type="hidden" name="tutor_id" value="<?php echo (int)$row['tutor_id']; ?>">
                    <input type="hidden" name="tutor_name" value="<?php echo htmlspecialchars($row['tutor_name']); ?>">
                    <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($row['subject_name']); ?>">
                    <p class="mc-panel-title">Report this Tutor</p>
                    <textarea name="report_reason" class="mc-textarea" placeholder="Please describe the issue..."></textarea>
                    <div class="mc-panel-actions">
                        <button type="submit" name="report_tutor" class="mc-panel-btn mc-panel-btn-warning">Send Report</button>
                        <button type="button" class="mc-panel-btn mc-panel-btn-cancel report-cancel-btn" data-target="report-panel-<?php echo (int)$row['id']; ?>">Cancel</button>
                    </div>
                </form>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="st-empty" style="grid-column: 1 / -1;">
            <span class="st-empty-icon">📚</span>
            <div>You haven't enrolled in any active courses yet.</div>
            <a href="search_tutor.php" class="st-view-btn" style="margin-top:10px;">Find a Tutor</a>
        </div>
    <?php endif; ?>
</div>

<!-- COMPLETED COURSES SECTION -->
<?php if ($enrollments_completed->num_rows > 0): ?>
<div class="st-section-head" style="margin-top: 50px;">
    <h1 class="st-section-title">🎓 Completed Courses</h1>
    <span class="st-result-count"><?php echo $enrollments_completed->num_rows; ?> Completed</span>
</div>

<div class="st-tutor-grid">
    <?php while ($row = $enrollments_completed->fetch_assoc()):
        $pkg_map       = ['1' => 1, '5' => 5, '12' => 12];
        $total_classes = $pkg_map[$row['package_type']] ?? (int)$row['package_type'];
        $done          = (int)($row['classes_done'] ?? 0);
        $progress_pct  = $total_classes > 0 ? min(100, round(($done / $total_classes) * 100)) : 0;
    ?>
        <div class="mc-card mc-card-completed">

            <!-- Card Top -->
            <div class="mc-card-top">
                <?php if ($row['photo_file']): ?>
                    <img src="/tutorpk/<?php echo htmlspecialchars($row['photo_file']); ?>" class="mc-avatar">
                <?php else: ?>
                    <div class="mc-avatar-init">👤</div>
                <?php endif; ?>
                <div class="mc-card-info">
                    <div class="mc-tutor-name"><?php echo htmlspecialchars($row['tutor_name']); ?></div>
                    <div class="mc-subject-name"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                </div>
                <!-- Status Badge -->
                <span class="mc-badge mc-badge-completed">🎓 DONE</span>
            </div>

            <!-- Card Meta -->
            <div class="mc-meta">
                <div class="mc-meta-row">
                    <span class="mc-meta-label">Roll No</span>
                    <span class="mc-meta-val mc-roll"><?php echo htmlspecialchars($student_roll_number); ?></span>
                </div>
                <div class="mc-meta-row">
                    <span class="mc-meta-label">Package</span>
                    <span class="mc-meta-val">
                        <?php
                        $pkg_label = ['1' => '1 Class', '5' => '5 Classes', '12' => 'Full Course'];
                        echo $pkg_label[$row['package_type']] ?? $row['package_type'] . ' Class(es)';
                        ?>
                    </span>
                </div>
                <div class="mc-meta-row">
                    <span class="mc-meta-label">Amount Paid</span>
                    <span class="mc-meta-val">Rs. <?php echo number_format($row['paid_amount'] ?? 0, 2); ?></span>
                </div>
            </div>

            <!-- Course Completed Banner -->
            <div class="mc-completed-banner">
                <div class="mc-completed-icon">🎓</div>
                <div class="mc-completed-text">Course Completed!</div>
                <div class="mc-completed-sub"><?php echo $done; ?> / <?php echo $total_classes; ?> Classes Finished</div>
            </div>

            <!-- Progress Bar -->
            <div class="mc-progress-section">
                <div class="mc-progress-label">
                    <span>Classes Progress</span>
                    <span class="mc-progress-count"><?php echo $done; ?> / <?php echo $total_classes; ?> Done</span>
                </div>
                <div class="mc-progress-bar">
                    <div class="mc-progress-fill mc-progress-fill-gold" style="width: <?php echo $progress_pct; ?>%;"></div>
                </div>
            </div>

            <!-- Completion Note -->
            <div class="mc-completed-action-note">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <span>Great job! Your achievement is preserved in your profile.</span>
            </div>

            <!-- View Certificate / Feedback Option -->
            <div class="mc-actions">
                <a href="chat.php?target_id=<?php echo $row['tutor_id']; ?>" class="mc-btn mc-btn-msg">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Message
                </a>
                <button type="button" class="mc-btn mc-btn-report report-trigger-completed" data-target="report-panel-completed-<?php echo (int)$row['id']; ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Rate
                </button>
            </div>

            <!-- Report/Rate Panel for Completed -->
            <form method="POST" style="margin:0;" id="report-panel-completed-<?php echo (int)$row['id']; ?>" class="mc-expand-panel">
                <input type="hidden" name="tutor_id" value="<?php echo (int)$row['tutor_id']; ?>">
                <input type="hidden" name="tutor_name" value="<?php echo htmlspecialchars($row['tutor_name']); ?>">
                <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($row['subject_name']); ?>">
                <p class="mc-panel-title">Share Your Feedback</p>
                <textarea name="report_reason" class="mc-textarea" placeholder="How was your learning experience? Any suggestions..."></textarea>
                <div class="mc-panel-actions">
                    <button type="submit" name="report_tutor" class="mc-panel-btn mc-panel-btn-warning">Send Feedback</button>
                    <button type="button" class="mc-panel-btn mc-panel-btn-cancel report-cancel-completed" data-target="report-panel-completed-<?php echo (int)$row['id']; ?>">Close</button>
                </div>
            </form>

        </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<style>
/* ── Alert ─────────────────────────────────── */
.mc-alert {
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 20px;
}
.mc-alert-success { background: #dcfce7; color: #166534; border-left: 4px solid #16a34a; }
.mc-alert-error   { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

/* ── Card Base ─────────────────────────────── */
.mc-card {
    background: #fff;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(15,23,42,0.07), 0 0 0 1px rgba(148,163,184,0.12);
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.mc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(15,23,42,0.12), 0 0 0 1px rgba(148,163,184,0.15);
}
.mc-card-paid      { border-top: 4px solid #10b981; }
.mc-card-unpaid    { border-top: 4px solid #f59e0b; }
.mc-card-completed {
    border-top: 4px solid #a78bfa;
    background: linear-gradient(160deg, #fefce8 0%, #fff 40%);
}

/* ── Card Top ──────────────────────────────── */
.mc-card-top {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mc-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #f1f5f9;
    flex-shrink: 0;
}
.mc-avatar-init {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.mc-card-info { flex: 1; min-width: 0; }
.mc-tutor-name {
    font-size: 1rem;
    font-weight: 800;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mc-subject-name {
    font-size: 0.82rem;
    color: #64748b;
    margin-top: 2px;
}

/* ── Status Badge ──────────────────────────── */
.mc-badge {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 999px;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}
.mc-badge-paid      { background: #dcfce7; color: #15803d; }
.mc-badge-unpaid    { background: #fef3c7; color: #92400e; }
.mc-badge-completed { background: linear-gradient(135deg, #f59e0b, #a78bfa); color: #fff; }

/* ── Meta Info ─────────────────────────────── */
.mc-meta {
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.mc-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.82rem;
}
.mc-meta-label { color: #94a3b8; font-weight: 600; }
.mc-meta-val   { color: #1e293b; font-weight: 700; }
.mc-roll       { color: #10b981; }

/* ── Progress ──────────────────────────────── */
.mc-progress-section { display: flex; flex-direction: column; gap: 6px; }
.mc-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 600;
}
.mc-progress-count { color: #10b981; font-weight: 800; }
.mc-progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}
.mc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 999px;
    transition: width 0.6s ease;
}
.mc-progress-fill-gold {
    background: linear-gradient(90deg, #f59e0b, #a78bfa) !important;
}

/* ── Completed Banner ──────────────────────── */
.mc-completed-banner {
    text-align: center;
    padding: 18px 12px;
    background: linear-gradient(135deg, #fef3c7, #ede9fe);
    border-radius: 14px;
    border: 2px dashed #a78bfa;
}
.mc-completed-icon  { font-size: 2.2rem; margin-bottom: 4px; }
.mc-completed-text  { font-size: 1.25rem; font-weight: 900; color: #7c3aed; letter-spacing: -0.01em; }
.mc-completed-sub   { font-size: 0.8rem; color: #92400e; font-weight: 600; margin-top: 4px; }

/* ── Archive Button ────────────────────────── */
.mc-archive-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 11px;
    background: #f5f3ff;
    color: #7c3aed;
    border: 2px solid #ddd6fe;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s, transform 0.15s;
    box-sizing: border-box;
}
.mc-archive-btn:hover {
    background: #ede9fe;
    transform: translateY(-1px);
}

/* ── Completed Action Note ─────────────────── */
.mc-completed-action-note {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #fef3c7, #ede9fe);
    border-radius: 10px;
    border: 1px solid #a78bfa;
    font-size: 0.8rem;
    color: #7c3aed;
    font-weight: 600;
}
.mc-completed-action-note svg {
    flex-shrink: 0;
    color: #a78bfa;
}

/* ── Activate Button ───────────────────────── */
.mc-activate-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border-radius: 12px;
    font-size: 0.92rem;
    font-weight: 800;
    text-decoration: none;
    letter-spacing: 0.02em;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
    transition: transform 0.15s, box-shadow 0.15s;
}
.mc-activate-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
}

/* ── Action Buttons Row ────────────────────── */
.mc-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
}
.mc-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 9px 8px;
    border-radius: 10px;
    font-size: 0.78rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.15s, opacity 0.15s;
    white-space: nowrap;
}
.mc-btn:hover { transform: translateY(-1px); opacity: 0.9; }

.mc-btn-msg {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.mc-btn-leave {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.mc-btn-report {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

/* ── Expandable Panel ──────────────────────── */
.mc-expand-panel {
    display: none;
    flex-direction: column;
    gap: 10px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    border: 1px solid #e2e8f0;
    animation: slideDown 0.2s ease;
}
.mc-expand-panel.active { display: flex; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.mc-panel-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #374151;
    margin: 0;
}
.mc-textarea {
    width: 100%;
    min-height: 72px;
    resize: vertical;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px;
    font-size: 0.82rem;
    color: #374151;
    background: #fff;
    font-family: inherit;
    box-sizing: border-box;
}
.mc-textarea:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
.mc-panel-actions {
    display: flex;
    gap: 8px;
}
.mc-panel-btn {
    flex: 1;
    padding: 9px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s;
}
.mc-panel-btn:hover { opacity: 0.85; }
.mc-panel-btn-danger  { background: #ef4444; color: #fff; }
.mc-panel-btn-warning { background: #f59e0b; color: #fff; }
.mc-panel-btn-cancel  { background: #e5e7eb; color: #374151; }
</style>

<script>
(function () {
    // ── Leave trigger ────────────────────────
    document.querySelectorAll('.leave-trigger-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.add('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.setAttribute('required','required'); ta.focus(); }
            btn.style.display = 'none';
        });
    });

    document.querySelectorAll('.leave-cancel-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.remove('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.value = ''; ta.removeAttribute('required'); }
            // Re-show trigger
            const card = btn.closest('.mc-card');
            if (card) {
                const trigger = card.querySelector('.leave-trigger-btn');
                if (trigger) trigger.style.display = '';
            }
        });
    });

    // ── Report trigger ────────────────────────
    document.querySelectorAll('.report-trigger-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.add('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.setAttribute('required','required'); ta.focus(); }
            btn.style.display = 'none';
        });
    });

    document.querySelectorAll('.report-cancel-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.remove('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.value = ''; ta.removeAttribute('required'); }
            const card = btn.closest('.mc-card');
            if (card) {
                const trigger = card.querySelector('.report-trigger-btn');
                if (trigger) trigger.style.display = '';
            }
        });
    });

    // ── Report trigger for Completed Courses ──
    document.querySelectorAll('.report-trigger-completed').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.add('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.focus(); }
            btn.style.display = 'none';
        });
    });

    document.querySelectorAll('.report-cancel-completed').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const panel = document.getElementById(btn.getAttribute('data-target'));
            if (!panel) return;
            panel.classList.remove('active');
            const ta = panel.querySelector('textarea');
            if (ta) { ta.value = ''; }
            const card = btn.closest('.mc-card');
            if (card) {
                const trigger = card.querySelector('.report-trigger-completed');
                if (trigger) trigger.style.display = '';
            }
        });
    });
})();
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>
