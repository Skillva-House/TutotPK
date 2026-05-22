<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Manage Tutors';
$role        = 'admin';
$user_name   = $_SESSION['name'] ?? 'Admin';
$active_page = 'manage_tutors';

$selected_tutor_id = (int) ($_GET['id'] ?? ($_POST['selected_tutor_id'] ?? 0));
$search_query      = trim($_GET['q'] ?? '');

$message = '';
$error   = '';

if ($selected_tutor_id <= 0 && $search_query !== '') {
    $like = '%' . $search_query . '%';
    $search_stmt = $conn->prepare(
        "SELECT id
         FROM users
         WHERE role = 'tutor' AND (name LIKE ? OR email LIKE ? OR subjects LIKE ?)
         ORDER BY CASE tutor_status
                      WHEN 'approved' THEN 1
                      WHEN 'pending' THEN 2
                      ELSE 3
                  END, id DESC
         LIMIT 1"
    );
    $search_stmt->bind_param('sss', $like, $like, $like);
    $search_stmt->execute();
    $search_hit = $search_stmt->get_result()->fetch_assoc();
    $search_stmt->close();

    if ($search_hit) {
        $selected_tutor_id = (int) $search_hit['id'];
    } else {
        $error = 'No tutor found for your search.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tutor_id'])) {
    $delete_tutor_id = (int) ($_POST['delete_tutor_id'] ?? 0);
    if ($delete_tutor_id > 0 && $delete_tutor_id === $selected_tutor_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'tutor'");
        $stmt->bind_param('i', $delete_tutor_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Could not delete tutor.';
        }
        $stmt->close();
    }
}

$tutor = null;
$schedules = [];

if ($selected_tutor_id > 0) {
    $stmt = $conn->prepare(
        "SELECT id, name, email, gender, qualification, teaching_level, subjects, experience, photo_file, bio, tutor_status
         FROM users
         WHERE id = ? AND role = 'tutor'
         LIMIT 1"
    );
    $stmt->bind_param('i', $selected_tutor_id);
    $stmt->execute();
    $tutor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($tutor) {
        $sch_stmt = $conn->prepare(
            "SELECT subject_name, topic_name, class_date, class_time, repeat_type, repeat_days_per_week, repeat_until
             FROM tutor_schedule
             WHERE tutor_id = ?
             ORDER BY class_date ASC, class_time ASC"
        );
        $sch_stmt->bind_param('i', $selected_tutor_id);
        $sch_stmt->execute();
        $sch_res = $sch_stmt->get_result();
        while ($row = $sch_res->fetch_assoc()) {
            $schedules[] = $row;
        }
        $sch_stmt->close();
    } elseif ($error === '') {
        $error = 'Tutor not found.';
    }
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>



<div style="padding: 4px 0 0;">
    <div class="vt-header">
        <!-- <div>
            <h1>Manage Tutor</h1>
            <p>Search tutor, view details, view class schedules, email, or delete tutor.</p>
        </div> -->
    </div>

    <div class="mt-search-row">
        <form method="GET" class="mt-search-form">
            <input
                type="text"
                name="q"
                class="mt-search-input"
                value="<?php echo escape_output($search_query); ?>"
                placeholder="Search tutor by name, email or subject"
            >
            <button type="submit" class="btn-search">Search</button>
            <?php if ($selected_tutor_id > 0): ?>
                <a href="manage_Tutors.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($message !== ''): ?>
        <div class="vt-alert success"><?php echo escape_output($message); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="vt-alert error"><?php echo escape_output($error); ?></div>
    <?php endif; ?>

    <?php if ($selected_tutor_id > 0): ?>
        <div class="badge-row">
            <span class="badge badge-verified">Tutor ID: #<?php echo (int)$selected_tutor_id; ?></span>
        </div>
    <?php endif; ?>

    <!-- All Tutors List -->
    <div class="section-heading">All Tutors</div>
    <div class="tp-section-card">
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Name</th>
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Email</th>
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Subjects</th>
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Experience</th>
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Status</th>
                    <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $all_tutors_stmt = $conn->prepare(
                        "SELECT id, name, email, subjects, experience, tutor_status
                         FROM users
                         WHERE role = 'tutor'
                         ORDER BY tutor_status DESC, name ASC"
                    );
                    $all_tutors_stmt->execute();
                    $all_tutors_result = $all_tutors_stmt->get_result();
                    
                    if ($all_tutors_result->num_rows > 0):
                        while ($tutor_row = $all_tutors_result->fetch_assoc()):
                            $status = strtolower((string)($tutor_row['tutor_status'] ?? 'pending'));
                            $status_label = $status === 'approved' ? 'Approved' : ($status === 'pending' ? 'Pending' : 'Unknown');
                            $status_class = $status === 'approved' ? 'status-verified' : 'status-pending';
                            $subjects = array_filter(array_map('trim', explode(',', $tutor_row['subjects'] ?? '')));
                ?>
                <tr>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9; font-weight:600;"><?php echo escape_output($tutor_row['name']); ?></td>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9; color:#475569;"><?php echo escape_output($tutor_row['email']); ?></td>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                        <?php if (!empty($subjects)): ?>
                            <span style="font-size:0.8rem; background:#e0e7ff; color:#4f46e5; padding:3px 8px; border-radius:4px; display:inline-block;">
                                <?php echo escape_output(implode(', ', array_slice($subjects, 0, 2))); ?>
                                <?php if (count($subjects) > 2): ?>
                                    <br><small>+<?php echo count($subjects) - 2; ?> more</small>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;"><?php echo isset($tutor_row['experience']) ? escape_output($tutor_row['experience']) . ' yrs' : '-'; ?></td>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                        <span class="status-tag <?php echo $status_class; ?>"><?php echo escape_output($status_label); ?></span>
                    </td>
                    <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                        <a href="manage_Tutors.php?id=<?php echo (int)$tutor_row['id']; ?>" class="btn-search" style="padding:6px 12px; font-size:0.75rem; text-decoration:none;">View</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6" style="padding:40px; text-align:center; color:#94a3b8;">No tutors found.</td>
                </tr>
                <?php endif; ?>
                <?php $all_tutors_stmt->close(); ?>
            </tbody>
        </table>
    </div>

    <div class="section-heading"> Details of Tutor</div>

    <?php if (!$tutor): ?>
        <div class="empty-state">
            <span class="empty-icon">📋</span>
            Search a tutor or click "Manage Tutors" from dashboard card.
        </div>
    <?php else: ?>
        <div class="tutor-grid">
            <?php
                $subjects = array_filter(array_map('trim', explode(',', $tutor['subjects'] ?? '')));
                $status = strtolower((string)($tutor['tutor_status'] ?? 'pending'));
                $status_label = $status === 'approved' ? 'Approved' : ($status === 'pending' ? 'Pending' : 'Unknown');
                $status_class = $status === 'approved' ? 'status-verified' : 'status-pending';
                $tutor_email = (string)($tutor['email'] ?? '');
            ?>
            <div class="tutor-card">
                <div class="tutor-top">
                    <?php if (!empty($tutor['photo_file'])): ?>
                        <img
                            src="/tutorpk/<?php echo escape_output($tutor['photo_file']); ?>"
                            alt="Photo"
                            class="tutor-avatar"
                        >
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            👤
                        </div>
                    <?php endif; ?>
                    <div class="tutor-identity">
                        <h3><?php echo escape_output($tutor['name']); ?></h3>
                        <span><?php echo escape_output($tutor_email); ?></span>
                    </div>
                </div>

                <div class="tutor-meta">
                    <div class="meta-item">
                        <div class="meta-label">Qualification</div>
                        <div class="meta-value"><?php echo escape_output($tutor['qualification'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Teaching Level</div>
                        <div class="meta-value"><?php echo escape_output($tutor['teaching_level'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Gender</div>
                        <div class="meta-value"><?php echo escape_output($tutor['gender'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Experience</div>
                        <div class="meta-value"><?php echo isset($tutor['experience']) ? escape_output($tutor['experience']) . ' yr(s)' : 'N/A'; ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Tutor ID</div>
                        <div class="meta-value">#<?php echo (int) $tutor['id']; ?></div>
                    </div>
                </div>

                <?php if (!empty($subjects)): ?>
                    <div class="mt-subjects">
                        <?php foreach ($subjects as $subject): ?>
                            <span class="mt-subject-pill"><?php echo escape_output($subject); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tutor['bio'])): ?>
                    <p class="mt-bio"><?php echo nl2br(escape_output($tutor['bio'])); ?></p>
                <?php endif; ?>

                <div style="display:flex;align-items:center;justify-content:space-between; margin-top:2px;">
                    <span class="status-tag <?php echo $status_class; ?>"><?php echo escape_output($status_label); ?></span>
                </div>

                <div class="tutor-actions">
                    <a
                        href="mailto:<?php echo escape_output($tutor_email); ?>?subject=<?php echo rawurlencode('TutorPk Admin Message'); ?>"
                        class="btn-email"
                    >Email</a>
                    <form method="POST" style="display:flex; flex:1;">
                        <input type="hidden" name="selected_tutor_id" value="<?php echo (int) $selected_tutor_id; ?>">
                        <input type="hidden" name="delete_tutor_id" value="<?php echo (int) $tutor['id']; ?>">
                        <button
                            type="submit"
                            class="btn-reject"
                            onclick="return confirm('Delete this tutor permanently from database?');"
                        >Delete Tutor</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="section-heading">Tutor Class Schedules</div>
        <?php if (empty($schedules)): ?>
            <div class="empty-state">
                <span class="empty-icon">🗓️</span>
                This tutor has not added schedules yet.
            </div>
        <?php else: ?>
            <div class="mt-sch-grid">
                <?php foreach ($schedules as $sch): ?>
                    <?php
                        $repeat_label = $sch['repeat_type'] ?? 'once';
                        if ($repeat_label === 'daily') {
                            $repeat_text = 'Every Day';
                        } elseif (preg_match('/^weekly_([1-7])$/', $repeat_label, $rm) === 1) {
                            $repeat_text = $rm[1] . ' Day(s)/Week';
                        } else {
                            $repeat_text = 'One-time';
                        }
                    ?>
                    <div class="mt-sch-card">
                        <div class="mt-sch-subject"><?php echo escape_output($sch['subject_name']); ?></div>
                        <?php if (!empty($sch['topic_name'])): ?>
                            <div class="mt-sch-topic">Topic: <?php echo escape_output($sch['topic_name']); ?></div>
                        <?php endif; ?>
                        <div class="mt-sch-chips">
                            <span class="mt-sch-chip">Date: <?php echo escape_output($sch['class_date']); ?></span>
                            <span class="mt-sch-chip">Time: <?php echo date('h:i A', strtotime($sch['class_time'])); ?></span>
                            <span class="mt-sch-chip">Repeat: <?php echo escape_output($repeat_text); ?></span>
                            <?php if (!empty($sch['repeat_until'])): ?>
                                <span class="mt-sch-chip">Until: <?php echo escape_output($sch['repeat_until']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

