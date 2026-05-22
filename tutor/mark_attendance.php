<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// Guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

function format_student_roll_number($student_id) {
    return 'STD-' . str_pad((string)((int)$student_id), 6, '0', STR_PAD_LEFT);
}

$tutor_id = (int)$_SESSION['id'];
$page_title = 'Attendance Tracker';
$role = 'tutor';
$active_page = 'attendance';

$selected_subject = trim($_GET['subject'] ?? '');

// Step 1: Fetch active subjects where at least 1 student is enrolled.
$subjects_query = "
    SELECT e.subject_name, COUNT(DISTINCT e.student_id) AS student_count
    FROM enrollments e
    WHERE e.tutor_id = ?
      AND (e.payment_status = 'paid' OR e.payment_status = 'released')
      AND (e.status IS NULL OR e.status != 'left')
      AND e.subject_name IS NOT NULL
      AND e.subject_name != ''
    GROUP BY e.subject_name
    HAVING student_count >= 1
    ORDER BY e.subject_name ASC
";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param('i', $tutor_id);
$subjects_stmt->execute();
$subject_rows = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subjects_stmt->close();

$valid_subjects = array_map(function ($s) {
    return $s['subject_name'];
}, $subject_rows);

if ($selected_subject !== '' && !in_array($selected_subject, $valid_subjects, true)) {
    $selected_subject = '';
}

// Step 2: If subject is selected, fetch enrolled students for that subject.
$students = [];
if ($selected_subject !== '') {
    $students_query = "
        SELECT e.id as enrollment_id, e.student_id, e.subject_name, e.package_type,
               e.classes_done, e.status,
               u.name as student_name, u.photo_file
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        WHERE e.tutor_id = ?
          AND e.subject_name = ?
          AND (e.payment_status = 'paid' OR e.payment_status = 'released')
          AND (e.status IS NULL OR e.status NOT IN ('left'))
        ORDER BY e.status ASC, u.name ASC
    ";
    $students_stmt = $conn->prepare($students_query);
    $students_stmt->bind_param('is', $tutor_id, $selected_subject);
    $students_stmt->execute();
    $students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $students_stmt->close();
}

$attendance_progress_map = [];
if (!empty($students)) {
    $enrollment_ids = array_map(function ($st) {
        return (int)$st['enrollment_id'];
    }, $students);

    $in_list = implode(',', $enrollment_ids);
    if ($in_list !== '') {
        $progress_q = $conn->query(
            "SELECT enrollment_id, COUNT(*) AS marked_count
             FROM attendance
             WHERE enrollment_id IN ($in_list)
               AND (status = 'present' OR status = 'absent')
             GROUP BY enrollment_id"
        );

        if ($progress_q) {
            while ($pr = $progress_q->fetch_assoc()) {
                $attendance_progress_map[(int)$pr['enrollment_id']] = (int)$pr['marked_count'];
            }
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<div class="ad-header">
    <h1>Today's Attendance 📝</h1>
    <p>Select a subject first, then mark each enrolled student as Present or Absent.</p>
</div>

<div class="tp-section-card" style="margin-bottom: 20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
        <h2 style="margin:0; font-size:1.1rem; color:#1e293b;">Subjects With Enrollments</h2>
        <?php if ($selected_subject !== ''): ?>
            <a href="mark_attendance.php" class="st-view-btn" style="font-size:0.8rem; padding:8px 12px;">← Back to Subjects</a>
        <?php endif; ?>
    </div>

    <?php if (empty($subject_rows)): ?>
        <div class="empty-state" style="margin-top: 8px;">
            <span class="empty-icon">📚</span>
            No subjects found with active paid enrollments.
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
            <?php foreach ($subject_rows as $sb): ?>
                <?php
                    $sub_name = $sb['subject_name'];
                    $is_active = ($selected_subject === $sub_name);
                ?>
                <a
                    href="mark_attendance.php?subject=<?php echo urlencode($sub_name); ?>"
                    style="text-decoration:none; border:1px solid <?php echo $is_active ? '#4f46e5' : '#e2e8f0'; ?>; background:<?php echo $is_active ? '#eef2ff' : '#fff'; ?>; border-radius:12px; padding:12px 14px; display:block;"
                >
                    <div style="font-size:0.96rem; font-weight:800; color:#1e293b; margin-bottom:4px;"><?php echo escape_output($sub_name); ?></div>
                    <div style="font-size:0.8rem; color:#64748b;"><?php echo (int)$sb['student_count']; ?> student<?php echo ((int)$sb['student_count'] !== 1) ? 's' : ''; ?> enrolled</div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($selected_subject !== ''): ?>
<div class="ad-header" style="margin-top: 4px; margin-bottom: 12px;">
    <h2 style="margin:0; font-size:1.2rem;">Subject: <?php echo escape_output($selected_subject); ?></h2>
    <p>Mark attendance for students enrolled in this subject.</p>
</div>

<div class="attendance-banner-list" style="margin-top: 12px;">
    <?php if (empty($students)): ?>
        <div class="empty-state" style="grid-column: span 3; background:#fff; padding: 50px; border-radius:16px; text-align:center;">
            <div style="font-size: 3rem; margin-bottom: 15px;">🔍</div>
            <h3 style="color:#1e293b;">No Students In This Subject</h3>
            <p style="color:#64748b;">No active paid enrollments found for this subject.</p>
        </div>
    <?php else: ?>
    <?php foreach ($students as $st):
            $eid = (int)$st['enrollment_id'];
            $today = date('Y-m-d');
            $pkg_map = ['1'=>1,'5'=>5,'12'=>12];
            $planned_classes = $pkg_map[$st['package_type']] ?? (int)$st['package_type'];
            $marked_classes  = (int)($attendance_progress_map[$eid] ?? 0);
            $classes_done    = (int)($st['classes_done'] ?? 0);
            if ($marked_classes > $planned_classes) $marked_classes = $planned_classes;

            // Skip students who have completed all their classes
            if ($st['status'] === 'completed' || $classes_done >= $planned_classes) continue;

            $chk = $conn->query("SELECT status FROM attendance WHERE enrollment_id = $eid AND class_date = '$today' LIMIT 1");
            $today_status = null;
            if ($chk && $chk->num_rows > 0) {
                $today_status = $chk->fetch_assoc()['status'] ?? null;
            }
        ?>
            <div class="attendance-banner">
                <div class="attendance-student-info">
                    <?php if (!empty($st['photo_file'])): ?>
                        <img src="/tutorpk/<?php echo escape_output($st['photo_file']); ?>" class="attendance-avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder attendance-avatar-fallback">👤</div>
                    <?php endif; ?>

                    <div class="attendance-meta">
                        <h3 class="attendance-name"><?php echo escape_output($st['student_name']); ?></h3>
                        <p class="attendance-detail"><?php echo escape_output($st['subject_name']); ?> · <?php echo (int)$st['package_type']; ?> Classes</p>
                        <p class="attendance-progress" id="attendance-progress-<?php echo $eid; ?>">Progress: <?php echo (int)$marked_classes; ?>/<?php echo (int)$planned_classes; ?></p>
                        <p class="attendance-roll">Roll No: <?php echo escape_output(format_student_roll_number($st['student_id'])); ?></p>
                    </div>
                </div>

                <div id="attendance-status-<?php echo $eid; ?>" class="attendance-action-zone">
                    <?php if ($today_status === 'present'): ?>
                        <span class="attendance-marked present">✅ Present (Today)</span>
                    <?php elseif ($today_status === 'absent'): ?>
                        <span class="attendance-marked absent">❌ Absent (Today)</span>
                    <?php else: ?>
                        <div class="attendance-actions">
                            <button 
                                onclick="markPresent(<?php echo $eid; ?>, <?php echo (int)$st['student_id']; ?>)" 
                                class="attendance-btn present"
                            >Present ✅</button>
                            <button 
                                onclick="markAbsent(<?php echo $eid; ?>, <?php echo (int)$st['student_id']; ?>)" 
                                class="attendance-btn absent"
                            >Absent ❌</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
async function markPresent(enrollmentId, studentId) {
    if (!confirm("Are you sure you want to mark this student as present today? This will award +150 XP!")) return;

    const formData = new FormData();
    formData.append('enrollment_id', enrollmentId);
    formData.append('student_id', studentId);

    try {
        const res = await fetch('../ajax/mark_present.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById(`attendance-status-${enrollmentId}`).innerHTML = 
                `<span class="attendance-marked present" style="animation: bounce 0.5s;">✅ Present (Today)</span>`;
            if (data.progress_text) {
                const progressEl = document.getElementById(`attendance-progress-${enrollmentId}`);
                if (progressEl) progressEl.textContent = `Progress: ${data.progress_text}`;
            }
            alert("Attendance marked and +150 XP awarded! ✨");
        } else {
            alert("Error: " + data.message);
        }
    } catch (e) {
        alert("An error occurred while linking to the server.");
    }
}

async function markAbsent(enrollmentId, studentId) {
    if (!confirm("Mark this student absent for today? This will deduct 100 XP.")) return;

    const formData = new FormData();
    formData.append('enrollment_id', enrollmentId);
    formData.append('student_id', studentId);

    try {
        const res = await fetch('../ajax/mark_absent.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById(`attendance-status-${enrollmentId}`).innerHTML =
                `<span class="attendance-marked absent" style="animation: bounce 0.5s;">❌ Absent (Today)</span>`;
            if (data.progress_text) {
                const progressEl = document.getElementById(`attendance-progress-${enrollmentId}`);
                if (progressEl) progressEl.textContent = `Progress: ${data.progress_text}`;
            }
            alert("Attendance marked absent and -100 XP applied.");
        } else {
            alert("Error: " + data.message);
        }
    } catch (e) {
        alert("An error occurred while linking to the server.");
    }
}
</script>

<style>
.attendance-banner-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.attendance-banner {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.attendance-student-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 240px;
}

.attendance-avatar,
.attendance-avatar-fallback {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.attendance-avatar-fallback {
    font-size: 1.05rem;
}

.attendance-meta {
    min-width: 0;
}

.attendance-name {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attendance-detail {
    margin: 2px 0 0;
    font-size: 0.8rem;
    color: #64748b;
}

.attendance-roll {
    margin: 3px 0 0;
    font-size: 0.74rem;
    font-weight: 700;
    color: #4f46e5;
}

.attendance-progress {
    margin: 3px 0 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: #0f766e;
}

.attendance-action-zone {
    margin-left: auto;
    min-width: 300px;
}

.attendance-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.attendance-btn {
    width: 100%;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 9px 10px;
    cursor: pointer;
}

.attendance-btn.present {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.attendance-btn.absent {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.attendance-marked {
    display: block;
    border-radius: 10px;
    padding: 10px;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: center;
}

.attendance-marked.present {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.attendance-marked.absent {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 820px) {
    .attendance-banner {
        flex-direction: column;
        align-items: stretch;
    }

    .attendance-action-zone {
        min-width: 0;
        margin-left: 0;
        width: 100%;
    }
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
