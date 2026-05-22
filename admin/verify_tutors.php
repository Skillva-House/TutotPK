<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';
require_once __DIR__ . '/../includes/mailer.php';

// ----- Admin Guard -----
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name   = $_SESSION['name'] ?? 'Admin';
$page_title  = 'Verify Tutors';
$role        = 'admin';
$active_page = 'verify_tutors';

$message      = '';
$message_type = '';

// ----- Handle Approve / Reject -----
if (isset($_POST['action'], $_POST['tutor_id'])) {
    $tutor_id = (int) $_POST['tutor_id'];
    $action   = $_POST['action'];

    if ($action === 'approve' && $tutor_id > 0) {
        // Fetch tutor info before approving to send email
        $info_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'tutor'");
        $info_stmt->bind_param('i', $tutor_id);
        $info_stmt->execute();
        $tutor_info = $info_stmt->get_result()->fetch_assoc();
        $info_stmt->close();

        if ($tutor_info) {
            // Set status to 'approved'
            $stmt = $conn->prepare("UPDATE users SET tutor_status = 'approved' WHERE id = ? AND role = 'tutor'");
            $stmt->bind_param('i', $tutor_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // SEND APPROVAL EMAIL
                send_approval_email($tutor_info['email'], $tutor_info['name']);

                $message      = 'Tutor has been approved successfully and notified via email.';
                $message_type = 'success';
            } else {
                $message      = 'Could not approve tutor. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        }

    } elseif ($action === 'reject' && $tutor_id > 0) {
        // Delete the tutor record from database
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'tutor'");
        $stmt->bind_param('i', $tutor_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message      = 'Tutor application has been rejected and removed.';
            $message_type = 'error';
        } else {
            $message      = 'Could not reject tutor. Please try again.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ----- Fetch pending tutors (status = 'pending' OR NULL) -----
$result        = $conn->query(
    "SELECT id, name, email, gender, qualification, teaching_level, subjects, experience, cv_file, photo_file
     FROM users
     WHERE role = 'tutor'
       AND (tutor_status = 'pending' OR tutor_status IS NULL)
     ORDER BY id DESC"
);
$pending_tutors = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Page-specific stylesheet -->
 <link rel="stylesheet" href="admin.css">



<div style="padding: 1rem 0 0;">

    <!-- Header -->
    <!-- <div class="vt-header">
        <div>
            <h1>Verify Tutors</h1>
        </div>
    </div> -->

    <!-- Pending count badge -->


    <!-- Alert message -->
    <?php if ($message !== ''): ?>
        <div class="vt-alert <?php echo $message_type; ?>">
            <?php echo escape_output($message); ?>
        </div>
    <?php endif; ?>


    <?php if (empty($pending_tutors)): ?>
        <div class="empty-state">
            <span class="empty-icon"></span>
            No pending tutor applications right now.
        </div>
    <?php else: ?>
        <div class="tutor-grid">
            <?php foreach ($pending_tutors as $tutor): ?>
                <div class="tutor-card">

                    <!-- Photo + name -->
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
                            <span><?php echo escape_output($tutor['email']); ?></span>
                        </div>
                    </div>

                    <!-- Meta info -->
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
                        <div class="meta-item" style="grid-column: span 2;">
                            <div class="meta-label">Subjects</div>
                            <div class="meta-value"><?php echo escape_output($tutor['subjects'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Experience</div>
                            <div class="meta-value">
                                <?php echo isset($tutor['experience']) ? escape_output($tutor['experience']) . ' yr(s)' : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Tutor ID</div>
                            <div class="meta-value">#<?php echo (int) $tutor['id']; ?></div>
                        </div>
                    </div>

                    <!-- CV link -->
                    <?php if (!empty($tutor['cv_file'])): ?>
                        <a
                            href="/tutorpk/<?php echo escape_output($tutor['cv_file']); ?>"
                            target="_blank"
                            class="cv-link"
                        >📄 View CV / Resume</a>
                    <?php endif; ?>

                    <!-- Approve / Reject buttons -->
                    <form method="POST" class="tutor-actions">
                        <input type="hidden" name="tutor_id" value="<?php echo (int) $tutor['id']; ?>">
                        <button
                            type="submit" name="action" value="approve"
                            class="btn-approve"
                        >✓ Approve</button>
                        <button
                            type="submit" name="action" value="reject"
                            class="btn-reject"
                            onclick="return confirm('Reject and permanently delete this tutor application?')"
                        >✕ Reject</button>
                    </form>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
