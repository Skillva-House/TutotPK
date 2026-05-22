<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Tutor Profile';
$role        = 'student';
$user_name   = $_SESSION['name'] ?? 'Student';
$active_page = 'search_tutor';

$tutor_id = (int) ($_GET['id'] ?? 0);
if ($tutor_id <= 0) {
    header('Location: search_tutor.php');
    exit();
}

// Fetch approved tutor
$stmt = $conn->prepare(
    "SELECT id, name, email, qualification, teaching_level, subjects, experience, photo_file, bio, gender
     FROM users
     WHERE id = ? AND role = 'tutor' AND tutor_status = 'approved'
     LIMIT 1"
);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tutor) {
    header('Location: search_tutor.php');
    exit();
}

// Fetch tutor schedules with pricing bundles
$sch_stmt = $conn->prepare(
    "SELECT id, subject_name, topic_name, class_date, class_time, repeat_type, repeat_days_per_week, selected_days, repeat_until,
            price_1, price_2, price_5, price_12
     FROM tutor_schedule
     WHERE tutor_id = ?
     ORDER BY class_date ASC, class_time ASC"
);
$sch_stmt->bind_param('i', $tutor_id);
$sch_stmt->execute();
$sch_result = $sch_stmt->get_result();
$schedules = [];
while ($row = $sch_result->fetch_assoc()) {
    $schedules[] = $row;
}
$sch_stmt->close();

// Fetch active paid/released enrollments for this student with this tutor.
// Left/cancelled/completed enrollments should not block a fresh enrollment.
$enroll_stmt = $conn->prepare("SELECT subject_name FROM enrollments WHERE student_id = ? AND tutor_id = ? AND (status = 'active' OR status IS NULL) AND (payment_status = 'paid' OR payment_status = 'released')");
$enroll_stmt->bind_param('ii', $_SESSION['id'], $tutor_id);
$enroll_stmt->execute();
$enroll_res = $enroll_stmt->get_result();
$enrolled_subjects = [];
while($row = $enroll_res->fetch_assoc()) {
    $enrolled_subjects[] = $row['subject_name'];
}
$enroll_stmt->close();

// Fetch average rating for THIS tutor
$rate_stmt = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM tutor_ratings WHERE tutor_id = ?");
$rate_stmt->bind_param('i', $tutor_id);
$rate_stmt->execute();
$rate_data = $rate_stmt->get_result()->fetch_assoc();
$avg_rating = $rate_data['avg_r'] ?? 0;
$total_ratings = $rate_data['cnt'] ?? 0;
$rate_stmt->close();

// Check if current student has already rated
$my_rate_stmt = $conn->prepare("SELECT rating FROM tutor_ratings WHERE tutor_id = ? AND student_id = ?");
$my_rate_stmt->bind_param('ii', $tutor_id, $_SESSION['id']);
$my_rate_stmt->execute();
$my_rating = $my_rate_stmt->get_result()->fetch_assoc()['rating'] ?? 0;
$my_rate_stmt->close();

// Parse subjects
$subjects_arr = array_filter(array_map('trim', explode(',', $tutor['subjects'] ?? '')));

// ── Tutor Enrollment Statistics ──────────────────────────────
$stat_stmt = $conn->prepare("
    SELECT
        COUNT(*)                                                                                                                           AS active_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END)                                                                                  AS completed_count,
        COUNT(DISTINCT CASE WHEN status = 'left' THEN student_id END)                                                                     AS left_count,
        COUNT(DISTINCT student_id)                                                                                                         AS total_students
    FROM enrollments
    WHERE tutor_id = ?
      AND (status IS NULL OR status = 'active')
      AND (payment_status = 'paid' OR payment_status = 'released')
");
$stat_stmt->bind_param('i', $tutor_id);
$stat_stmt->execute();
$stats = $stat_stmt->get_result()->fetch_assoc();
$stat_stmt->close();

// Also get completed & left from a separate full query
$stat2_stmt = $conn->prepare("
    SELECT
        COUNT(CASE WHEN status = 'completed' THEN 1 END)                    AS completed_count,
        COUNT(DISTINCT CASE WHEN status = 'left' THEN student_id END)       AS left_students,
        COUNT(DISTINCT student_id)                                           AS total_students
    FROM enrollments WHERE tutor_id = ?
");
$stat2_stmt->bind_param('i', $tutor_id);
$stat2_stmt->execute();
$stats2 = $stat2_stmt->get_result()->fetch_assoc();
$stat2_stmt->close();

$active_enr     = (int)($stats['active_count']    ?? 0);   // active paid enrollments (rows)
$completed_enr  = (int)($stats2['completed_count'] ?? 0);  // completed enrollment rows
$left_students  = (int)($stats2['left_students']   ?? 0);  // unique students who left
$total_students = (int)($stats2['total_students']  ?? 0);  // unique students ever


include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';

// Display enrollment messages
if (isset($_SESSION['enroll_msg'])) {
    $m_type = $_SESSION['enroll_type'] ?? 'error';
    echo '<div class="vt-alert ' . $m_type . '" style="margin: 20px;">' . escape_output($_SESSION['enroll_msg']) . '</div>';
    unset($_SESSION['enroll_msg'], $_SESSION['enroll_type']);
}
?>
<link rel="stylesheet" href="student.css?v=<?php echo time(); ?>">
<div class="tp-back-row">
    <a href="search_tutor.php" class="tp-back-btn">← Back to Search</a>
</div>

<!-- ══ Hero Card ══ -->
<div class="tp-hero-card">
    <div class="tp-hero-left">
        <?php if (!empty($tutor['photo_file'])): ?>
            <img
                src="/tutorpk/<?php echo escape_output($tutor['photo_file']); ?>"
                alt="<?php echo escape_output($tutor['name']); ?>"
                class="tp-hero-avatar"
            >
        <?php else: ?>
            <div class="tp-hero-avatar-init">
                👤
            </div>
        <?php endif; ?>
    </div>

    <div class="tp-hero-info">
        <h1 class="tp-hero-name"><?php echo escape_output($tutor['name']); ?></h1>
        <div class="tp-hero-qual" style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;">
            <span style="background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; color: #10b981; font-weight: 700;">🎓 Education: <span style="color: #475569; font-weight: 600;"><?php echo escape_output($tutor['qualification'] ?? 'Not specified'); ?></span></span>
            <span style="background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #e2e8f0; color: #10b981; font-weight: 700;">📚 Teaches: <span style="color: #475569; font-weight: 600;"><?php echo escape_output($tutor['teaching_level'] ?? 'All Levels'); ?></span></span>
        </div>

        <div class="tp-rating-display" style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 1.1rem; color: #f59e0b; font-weight: 800;">⭐ <?php echo $avg_rating > 0 ? number_format($avg_rating, 1) : "0.0"; ?></span>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">(<?php echo (int)$total_ratings; ?> reviews)</span>
        </div>

        <div class="tp-hero-meta">
            <?php if (!empty($tutor['gender'])): ?>
                <span class="tp-meta-chip">👤 <?php echo escape_output($tutor['gender']); ?></span>
            <?php endif; ?>
            <?php
                $exp = (int)($tutor['experience'] ?? 0);
                if ($exp > 0):
            ?>
                <span class="tp-meta-chip">🎓 <?php echo $exp; ?> yr<?php echo $exp > 1 ? 's' : ''; ?> experience</span>
            <?php endif; ?>
            <span class="tp-meta-chip tp-verified">✓ Verified Tutor</span>
        </div>

        <?php if (!empty($tutor['bio'])): ?>
            <p class="tp-bio"><?php echo nl2br(escape_output($tutor['bio'])); ?></p>
        <?php endif; ?>
    </div>

    <div class="tp-hero-action">
        <a href="chat.php?target_id=<?php echo (int) $tutor_id; ?>" class="tp-enroll-btn" style="margin-bottom:10px; background:linear-gradient(135deg, #10b981, #1a7d30); color: #fff;">💬 Message Tutor</a>
        
        <?php if (!empty($enrolled_subjects)): ?>
            <div class="rate-tutor-box" style="margin-top:20px; text-align:center; padding:15px; background: #fff; border: 1px solid #e2e8f0; border-radius:12px;">
                <p style="font-size:0.8rem; font-weight:700; color:#1e293b; margin-bottom:10px;">Rate this Tutor</p>
                <div class="star-rating" style="display:flex; justify-content:center; gap:5px; font-size:1.5rem; cursor:pointer;">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <span class="star <?php echo ($i <= $my_rating) ? 'active' : ''; ?>" data-val="<?php echo $i; ?>" onclick="submitRating(<?php echo $i; ?>)">
                            <?php echo ($i <= $my_rating) ? '★' : '☆'; ?>
                        </span>
                    <?php endfor; ?>
                </div>
                <p id="rate-msg" style="font-size:0.7rem; margin-top:8px; color:#64748b; min-height:1rem;"></p>
            </div>
        <?php else: ?>
            <p style="font-size:0.8rem; color:#6b7280;margin-top:15px; text-align:center;">Enroll in any subject below to rate this tutor</p>
        <?php endif; ?>
    </div>

</div>

<!-- ══ Tutor Stats Row ══ -->
<div class="tps-stats-row">
    <div class="tps-stat">
        <div class="tps-stat-icon tps-icon-active">📋</div>
        <div class="tps-stat-val"><?php echo $active_enr; ?></div>
        <div class="tps-stat-label">Active Enrollments</div>
    </div>
    <div class="tps-stat">
        <div class="tps-stat-icon tps-icon-done">🏆</div>
        <div class="tps-stat-val"><?php echo $completed_enr; ?></div>
        <div class="tps-stat-label">Completed Enrollments</div>
    </div>
    <div class="tps-stat">
        <div class="tps-stat-icon tps-icon-left">🚪</div>
        <div class="tps-stat-val"><?php echo $left_students; ?></div>
        <div class="tps-stat-label">Students Left</div>
    </div>
    <div class="tps-stat">
        <div class="tps-stat-icon tps-icon-total">👥</div>
        <div class="tps-stat-val"><?php echo $total_students; ?></div>
        <div class="tps-stat-label">Total Students</div>
    </div>
</div>

<style>
.tps-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin: 0 0 24px;
}
@media (max-width: 640px) { .tps-stats-row { grid-template-columns: repeat(2,1fr); } }
.tps-stat {
    background: #fff;
    border-radius: 16px;
    padding: 20px 12px;
    text-align: center;
    box-shadow: 0 4px 16px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.1);
    transition: transform .2s, box-shadow .2s;
}
.tps-stat:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(15,23,42,0.1); }
.tps-stat-icon  { font-size: 1.8rem; margin-bottom: 8px; }
.tps-stat-val   { font-size: 1.8rem; font-weight: 900; color: #1e293b; line-height: 1; }
.tps-stat-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.tps-icon-active { background: #dcfce7; border-radius: 12px; display:inline-block; width:48px; height:48px; line-height:48px; }
.tps-icon-done   { background: #fef3c7; border-radius: 12px; display:inline-block; width:48px; height:48px; line-height:48px; }
.tps-icon-left   { background: #fee2e2; border-radius: 12px; display:inline-block; width:48px; height:48px; line-height:48px; }
.tps-icon-total  { background: #eff6ff; border-radius: 12px; display:inline-block; width:48px; height:48px; line-height:48px; }
</style>

<script>
async function submitRating(val) {
    const msgEl = document.getElementById('rate-msg');
    msgEl.innerText = "Saving...";
    
    const formData = new FormData();
    formData.append('tutor_id', <?php echo $tutor_id; ?>);
    formData.append('rating', val);

    try {
        const res = await fetch('../ajax/submit_rating.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            msgEl.innerText = "Rating saved! Thank you.";
            msgEl.style.color = "#16a34a";
            // Update stars visually
            const stars = document.querySelectorAll('.star');
            stars.forEach((s, idx) => {
                s.innerText = (idx < val) ? '★' : '☆';
                if (idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });
        } else {
            msgEl.innerText = data.message;
            msgEl.style.color = "#ef4444";
        }
    } catch (e) {
        msgEl.innerText = "Network error.";
    }
}
</script>

<style>
.star { color: #cbd5e1; transition: color 0.2s, transform 0.1s; }
.star.active { color: #f59e0b; }
.star:hover { transform: scale(1.2); }
</style>

<!-- ══ Subjects Section ══ -->
<div class="tp-section-card">
    <h2 class="tp-section-title">Subjects Offered</h2>
    <?php if (!empty($subjects_arr)): ?>
        <div class="tp-subject-grid">
            <?php foreach ($subjects_arr as $sub): ?>
                <div class="tp-subject-item">
                    <span class="tp-subject-icon">📚</span>
                    <span><?php echo escape_output($sub); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="tp-empty-note">No subjects listed yet.</p>
    <?php endif; ?>
</div>

<!-- ══ Schedule Section ══ -->
<div class="tp-section-card">
    <h2 class="tp-section-title">Available Schedules</h2>

    <?php if (empty($schedules)): ?>
        <p class="tp-empty-note">This tutor has not added any schedules yet.</p>
    <?php else: ?>
        <div class="tp-schedule-grid">
            <?php foreach ($schedules as $sch): ?>
                <?php
                    $repeat_label = $sch['repeat_type'] ?? 'once';
                    if ($repeat_label === 'daily') {
                        $repeat_text = 'Every Day';
                    } elseif (preg_match('/^weekly_([1-7])$/', $repeat_label, $rm) === 1) {
                        if (!empty($sch['selected_days'])) {
                            $days_map = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun'];
                            $d_arr = explode(',', $sch['selected_days']);
                            $d_names = array_map(function($d) use ($days_map) { return $days_map[$d] ?? $d; }, $d_arr);
                            $repeat_text = implode(', ', $d_names);
                        } else {
                            $repeat_text = $rm[1] . ' Day(s)/Week';
                        }
                    } else {
                        $repeat_text = 'One-time';
                    }
                ?>
                <div class="tp-schedule-card">
                    <div class="tp-sch-subject"><?php echo escape_output($sch['subject_name']); ?></div>
                    <?php if (!empty($sch['topic_name'])): ?>
                        <div class="tp-sch-topic">Topic: <?php echo escape_output($sch['topic_name']); ?></div>
                    <?php endif; ?>
                    <div class="tp-sch-chips">
                        <span class="tp-sch-chip">📅 <?php echo escape_output($sch['class_date']); ?></span>
                        <span class="tp-sch-chip">🕐 <?php echo date('h:i A', strtotime($sch['class_time'])); ?></span>
                        <span class="tp-sch-chip">🔁 <?php echo escape_output($repeat_text); ?></span>
                        <?php if (!empty($sch['repeat_until'])): ?>
                            <span class="tp-sch-chip">Until: <?php echo escape_output($sch['repeat_until']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (in_array($sch['subject_name'], $enrolled_subjects)): ?>
                        <button class="tp-sch-enroll-btn" style="background:#dcfce7; color:#166534; cursor:default;" disabled>✓ Enrolled</button>
                    <?php else: ?>
                        <form action="book_class.php" method="POST" class="tp-enroll-form">
                            <input type="hidden" name="tutor_id" value="<?php echo (int) $tutor_id; ?>">
                            <input type="hidden" name="subject_name" value="<?php echo escape_output($sch['subject_name']); ?>">
                            <input type="hidden" name="schedule_id" value="<?php echo (int) $sch['id']; ?>">
                            
                            <div class="tp-package-select">
                                <label class="tp-pkg-option">
                                    <input type="radio" name="package_type" value="1" checked> 
                                    <span>1 Class (Rs. <?php echo number_format($sch['price_1'], 2); ?>)</span>
                                </label>
                                <label class="tp-pkg-option">
                                    <input type="radio" name="package_type" value="2"> 
                                    <span>2 Classes (Rs. <?php echo (isset($sch['price_2']) ? number_format($sch['price_2'], 2) : '0.00'); ?>)</span>
                                </label>
                                <label class="tp-pkg-option">
                                    <input type="radio" name="package_type" value="5"> 
                                    <span>5 Classes (Rs. <?php echo number_format($sch['price_5'], 2); ?>)</span>
                                </label>
                                <label class="tp-pkg-option">
                                    <input type="radio" name="package_type" value="12"> 
                                    <span>Full Course (Rs. <?php echo number_format($sch['price_12'], 2); ?>)</span>
                                </label>
                            </div>

                            <button type="submit" class="tp-sch-enroll-btn">Enroll & Pay Now</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>


