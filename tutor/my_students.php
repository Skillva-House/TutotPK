<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// Auth Guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$tutor_id = $_SESSION['id'];
$page_title  = 'My Students';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor';
$active_page = 'my_students';

$has_leave_reason_col = false;
$has_left_at_col = false;

$col_res = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'leave_reason'");
if ($col_res && $col_res->num_rows > 0) $has_leave_reason_col = true;

$col_res = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'left_at'");
if ($col_res && $col_res->num_rows > 0) $has_left_at_col = true;

// Fetch enrolled students
$extra_cols = '';
if ($has_leave_reason_col) {
    $extra_cols .= ', e.leave_reason';
} else {
    $extra_cols .= ", '' AS leave_reason";
}
if ($has_left_at_col) {
    $extra_cols .= ', e.left_at';
} else {
    $extra_cols .= ', NULL AS left_at';
}

$query = "
    SELECT u.id, u.name, u.email, u.photo_file,
           e.subject_name, e.created_at, e.status,
           e.payment_status, e.package_type, e.classes_done
           $extra_cols
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    WHERE e.tutor_id = ?
    ORDER BY e.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$enrollments           = [];
$active_enrollments    = [];
$completed_enrollments = [];
$left_enrollments      = [];
while ($row = $result->fetch_assoc()) {
    $enrollments[] = $row;
    $s = $row['status'] ?? '';
    if ($s === 'left') {
        $left_enrollments[] = $row;
    } elseif ($s === 'completed') {
        $completed_enrollments[] = $row;
    } else {
        $active_enrollments[] = $row;
    }
}
$stmt->close();

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="stylesheet" href="tutor.css?v=<?php echo time(); ?>">

<div style="padding: 10px 0;">
    <div class="vt-header">
        <div>
            <h1>My Enrolled Students</h1>
            <p>View active students and see leave reasons when someone exits a course.</p>
        </div>
        <div class="student-search-wrapper" style="position:relative; width: 100%; max-width: 340px; margin-top: 10px;">
            <input type="text" id="studentSearchInput" placeholder="Search by name, email, or subject..." style="width:100%; padding:12px 16px 12px 42px; border:2px solid #e2e8f0; border-radius:12px; font-size:0.95rem; outline:none; transition:all 0.2s; color:#1e293b; box-shadow:0 4px 10px rgba(0,0,0,0.02);">
            <span style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1.1rem;">🔍</span>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('studentSearchInput');
        const studentCards = document.querySelectorAll('.student-card-premium');

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                studentCards.forEach(card => {
                    const name = card.querySelector('.student-name-premium')?.textContent.toLowerCase() || '';
                    const email = card.querySelector('.student-email-premium')?.textContent.toLowerCase() || '';
                    const vals = Array.from(card.querySelectorAll('.val')).map(el => el.textContent.toLowerCase()).join(' ');
                    
                    if (name.includes(term) || email.includes(term) || vals.includes(term)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
            
            // Add focus ring style dynamically
            searchInput.addEventListener('focus', () => {
                searchInput.style.borderColor = '#10b981';
                searchInput.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
            });
            searchInput.addEventListener('blur', () => {
                searchInput.style.borderColor = '#e2e8f0';
                searchInput.style.boxShadow = '0 4px 10px rgba(0,0,0,0.02)';
            });
        }
    });
    </script>

    <?php if (empty($active_enrollments)): ?>
        <div class="empty-state">
            <span class="empty-icon">👥</span>
            No active students right now.
        </div>
    <?php else: ?>
        <div class="tutor-grid">
            <?php foreach ($active_enrollments as $enr): ?>
                <div class="student-card-premium">
                    <div class="card-header-gradient">
                        <div class="student-avatar-wrap">
                            <?php if (!empty($enr['photo_file'])): ?>
                                <img src="/tutorpk/<?php echo escape_output($enr['photo_file']); ?>" class="st-avatar-img">
                            <?php else: ?>
                                <div class="st-avatar-init-large">
                                    <?php echo strtoupper(substr($enr['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="student-card-body">
                        <h3 class="student-name-premium"><?php echo escape_output($enr['name']); ?></h3>
                        <span class="student-email-premium"><?php echo escape_output($enr['email']); ?></span>

                        <!-- Payment Status Badge -->
                        <?php 
                            $is_paid = ($enr['payment_status'] === 'paid' || $enr['payment_status'] === 'released');
                            $status_class = $is_paid ? 'payment-paid' : 'payment-unpaid';
                            $status_text = $is_paid ? 'Fee Paid to Admin' : 'Payment Pending';
                        ?>
                        <div class="payment-status-premium <?php echo $status_class; ?>">
                            <span class="payment-dot"></span>
                            <?php echo $status_text; ?>
                        </div>

                        <div class="stats-container">
                            <div class="stat-box">
                                <div class="label">Subject</div>
                                <div class="val"><?php echo escape_output($enr['subject_name']); ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Package</div>
                                <div class="val"><?php echo $enr['package_type']; ?> Classes</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Enrolled On</div>
                                <div class="val"><?php echo date('M d', strtotime($enr['created_at'])); ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="label">S. ID</div>
                                <div class="val">#<?php echo $enr['id']; ?></div>
                            </div>
                        </div>

                        <div class="card-actions-premium">
                            <a href="chat.php?target_id=<?php echo (int) $enr['id']; ?>" class="btn-message-premium">
                                💬 Send Message
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Completed Students Section -->
    <div class="section-heading" style="margin-top:32px; display:flex; align-items:center; gap:10px;">
        🏆 Completed Students (Passout)
        <span style="background:#fef3c7; color:#92400e; font-size:0.75rem; font-weight:800; padding:3px 10px; border-radius:999px;"><?php echo count($completed_enrollments); ?></span>
    </div>

    <?php if (empty($completed_enrollments)): ?>
        <div class="empty-state">
            <span class="empty-icon">🎓</span>
            No students have completed a course yet.
        </div>
    <?php else: ?>
        <div class="tutor-grid">
            <?php foreach ($completed_enrollments as $enr):
                $pkg_labels = ['1'=>'1 Class','5'=>'5 Classes','12'=>'Full Course'];
                $pkg_label  = $pkg_labels[$enr['package_type']] ?? $enr['package_type'].' Classes';
                $done       = (int)($enr['classes_done'] ?? 0);
                $total_map  = ['1'=>1,'5'=>5,'12'=>12];
                $total      = $total_map[$enr['package_type']] ?? (int)$enr['package_type'];
            ?>
                <div class="student-card-premium" style="border:2px solid #fde68a; box-shadow:0 10px 25px rgba(245,158,11,0.12);">
                    <div class="card-header-gradient" style="background:linear-gradient(135deg,#f59e0b,#a78bfa);">
                        <div class="student-avatar-wrap">
                            <?php if (!empty($enr['photo_file'])): ?>
                                <img src="/tutorpk/<?php echo escape_output($enr['photo_file']); ?>" class="st-avatar-img">
                            <?php else: ?>
                                <div class="st-avatar-init-large"><?php echo strtoupper(substr($enr['name'],0,1)); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="student-card-body">
                        <h3 class="student-name-premium"><?php echo escape_output($enr['name']); ?></h3>
                        <span class="student-email-premium"><?php echo escape_output($enr['email']); ?></span>

                        <div class="payment-status-premium" style="background:#fef9c3; border-color:#fde68a; color:#92400e;">
                            <span class="payment-dot" style="background:#f59e0b;"></span>
                            🎓 Course Completed
                        </div>

                        <div style="margin:10px 0 6px;">
                            <div style="display:flex; justify-content:space-between; font-size:0.74rem; font-weight:700; color:#64748b; margin-bottom:4px;">
                                <span>Progress</span>
                                <span style="color:#10b981;"><?php echo $done; ?> / <?php echo $total; ?> Done</span>
                            </div>
                            <div style="height:6px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                                <div style="height:100%; width:100%; background:linear-gradient(90deg,#f59e0b,#a78bfa); border-radius:999px;"></div>
                            </div>
                        </div>

                        <div class="stats-container">
                            <div class="stat-box"><div class="label">Subject</div><div class="val"><?php echo escape_output($enr['subject_name']); ?></div></div>
                            <div class="stat-box"><div class="label">Package</div><div class="val"><?php echo $pkg_label; ?></div></div>
                            <div class="stat-box"><div class="label">Enrolled</div><div class="val"><?php echo date('M d', strtotime($enr['created_at'])); ?></div></div>
                            <div class="stat-box"><div class="label">S. ID</div><div class="val">#<?php echo $enr['id']; ?></div></div>
                        </div>
                        <div class="card-actions-premium">
                            <a href="chat.php?target_id=<?php echo (int)$enr['id']; ?>" class="btn-message-premium">💬 Send Message</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-heading" style="margin-top: 32px;">Left Students History</div>

    <?php if (empty($left_enrollments)): ?>
        <div class="empty-state">
            <span class="empty-icon">🗂️</span>
            No student has left your courses yet.
        </div>
    <?php else: ?>
        <div class="tutor-grid">
            <?php foreach ($left_enrollments as $enr): ?>
                <div class="student-card-premium" style="border:1px solid #fecaca; box-shadow: 0 10px 25px rgba(239,68,68,0.08);">
                    <div class="card-header-gradient" style="background: linear-gradient(135deg, #ef4444, #f97316);">
                        <div class="student-avatar-wrap">
                            <?php if (!empty($enr['photo_file'])): ?>
                                <img src="/tutorpk/<?php echo escape_output($enr['photo_file']); ?>" class="st-avatar-img">
                            <?php else: ?>
                                <div class="st-avatar-init-large">
                                    <?php echo strtoupper(substr($enr['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="student-card-body">
                        <h3 class="student-name-premium"><?php echo escape_output($enr['name']); ?></h3>
                        <span class="student-email-premium"><?php echo escape_output($enr['email']); ?></span>

                        <div class="payment-status-premium payment-unpaid" style="background:#fef2f2; border-color:#fecaca; color:#ef4444;">
                            <span class="payment-dot" style="background:#ef4444;"></span>
                            Student Left Course
                        </div>

                        <div class="stats-container">
                            <div class="stat-box">
                                <div class="label">Subject</div>
                                <div class="val"><?php echo escape_output($enr['subject_name']); ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Package</div>
                                <div class="val"><?php echo $enr['package_type']; ?> Classes</div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Enrolled On</div>
                                <div class="val"><?php echo date('M d', strtotime($enr['created_at'])); ?></div>
                            </div>
                            <div class="stat-box">
                                <div class="label">Left On</div>
                                <div class="val"><?php echo !empty($enr['left_at']) ? date('M d', strtotime($enr['left_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>

                        <div style="margin-top: 10px; background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:12px; color:#9a3412; font-size:0.82rem; line-height:1.45;">
                            <strong>Leave Reason:</strong><br>
                            <?php echo !empty($enr['leave_reason']) ? escape_output($enr['leave_reason']) : 'No reason provided.'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
