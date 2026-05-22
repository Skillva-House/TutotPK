<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// ----- Student Guard -----
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Search Tutor';
$role        = 'student';
$user_name   = $_SESSION['name'] ?? 'Student';
$active_page = 'search_tutor';
$student_id  = (int) $_SESSION['id'];

// ----- Recommendation engine (personalized + top rated + pioneer fallback) -----
$recommended_ids = [];

$subject_stmt = $conn->prepare(
    "SELECT subject_name, COUNT(*) AS taught_count
     FROM enrollments
     WHERE student_id = ?
       AND (payment_status = 'paid' OR payment_status = 'released')
       AND (status IS NULL OR status != 'left')
       AND subject_name IS NOT NULL
       AND subject_name != ''
     GROUP BY subject_name
     ORDER BY taught_count DESC, subject_name ASC
     LIMIT 5"
);
$subject_stmt->bind_param('i', $student_id);
$subject_stmt->execute();
$subject_res = $subject_stmt->get_result();

$top_subjects = [];
while ($row = $subject_res->fetch_assoc()) {
    $subject_name = trim((string)($row['subject_name'] ?? ''));
    if ($subject_name !== '') {
        $top_subjects[] = $subject_name;
    }
}
$subject_stmt->close();

if (!empty($top_subjects)) {
    $subject_match_clauses = [];
    foreach ($top_subjects as $sub) {
        $safe_sub = $conn->real_escape_string($sub);
        $subject_match_clauses[] = "u.subjects LIKE '%$safe_sub%'";
        $subject_match_clauses[] = "ts.subject_name LIKE '%$safe_sub%'";
    }

    $match_where = implode(' OR ', $subject_match_clauses);

    $top_rated_sql = "
        SELECT u.id, COALESCE(AVG(tr.rating), 0) AS avg_rating
        FROM users u
        LEFT JOIN tutor_ratings tr ON tr.tutor_id = u.id
        LEFT JOIN tutor_schedule ts ON ts.tutor_id = u.id
        WHERE u.role = 'tutor'
          AND u.tutor_status = 'approved'
          AND ($match_where)
        GROUP BY u.id
        HAVING avg_rating >= 4.0
        ORDER BY avg_rating DESC, u.experience DESC, u.id DESC
        LIMIT 6
    ";

    $top_rated_result = $conn->query($top_rated_sql);
    if ($top_rated_result) {
        while ($row = $top_rated_result->fetch_assoc()) {
            $tid = (int)$row['id'];
            if (!in_array($tid, $recommended_ids, true)) {
                $recommended_ids[] = $tid;
            }
        }
    }
}

// Fetch ALL tutors so client-side search works instantly
$all_tutors_sql = "
    SELECT u.id, u.name, u.email, u.qualification, u.subjects, u.experience, u.photo_file,
           COALESCE(AVG(tr.rating), 0) AS avg_rating,
           GROUP_CONCAT(ts.subject_name SEPARATOR ' ') as schedule_subjects
    FROM users u
    LEFT JOIN tutor_schedule ts ON u.id = ts.tutor_id
    LEFT JOIN tutor_ratings tr ON tr.tutor_id = u.id
    WHERE u.role = 'tutor'
      AND u.tutor_status = 'approved'
    GROUP BY u.id
    ORDER BY avg_rating DESC, u.experience DESC, u.id DESC
";
$all_tutors_res = $conn->query($all_tutors_sql);
$all_tutors = $all_tutors_res->fetch_all(MYSQLI_ASSOC);

// If we need more recommended tutors to reach 6, just pick the top from all_tutors
foreach ($all_tutors as $tutor) {
    if (count($recommended_ids) >= 6) break;
    if (!in_array((int)$tutor['id'], $recommended_ids, true)) {
        $recommended_ids[] = (int)$tutor['id'];
    }
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>
<!-- This page needs custom CSS -->
<link rel="stylesheet" href="student.css?v=<?php echo time(); ?>">

<!-- ══ Search Bar ══ -->
<div class="st-search-wrap">
    <div class="st-search-title">Find Your <span style="color: #10b981;">Perfect Tutor</span></div>
    <div class="st-search-sub">Search by tutor name, subject, or qualification instantly.</div>

    <div class="student-search-wrapper" style="position:relative; width: 100%; max-width: 600px; margin: 0 auto;">
        <input type="text" id="searchInput" placeholder="e.g. Math, Physics, Ali..." style="width:100%; padding:18px 24px 18px 56px; border:2px solid #e2e8f0; border-radius:16px; font-size:1.05rem; font-weight:500; outline:none; transition:all 0.2s; color:#1e293b; background: #f8fafc; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
        <span style="position:absolute; left:22px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1.3rem;">🔍</span>
    </div>
</div>

<!-- ══ Results ══ -->
<div class="st-section-head">
    <div class="st-section-title" id="sectionTitle">
        Top Recommended Tutors
    </div>
    <span class="st-result-count" id="resultCount">
        Showing top picks
    </span>
</div>

<div class="st-empty" id="emptyState" style="display:none;">
    <span class="st-empty-icon">😕</span>
    No tutors found matching your search. Try a different keyword!
</div>

<div class="st-tutor-grid" id="tutorGrid">
    <?php foreach ($all_tutors as $tutor): ?>
        <?php
            $is_rec = in_array((int)$tutor['id'], $recommended_ids, true);
            $subjects_arr = array_slice(
                array_map('trim', explode(',', $tutor['subjects'] ?? '')),
                0, 4   // show max 4 pills
            );
            $rating = (float)($tutor['avg_rating'] ?? 0);
            $is_top_rated = $rating >= 4.0;
        ?>
        <div class="st-tutor-card <?php echo $is_rec ? 'rec-tutor' : 'other-tutor'; ?>" style="display: <?php echo $is_rec ? 'flex' : 'none'; ?>;" data-name="<?php echo strtolower(escape_output($tutor['name'])); ?>" data-qual="<?php echo strtolower(escape_output($tutor['qualification'])); ?>" data-subs="<?php echo strtolower(escape_output($tutor['subjects'] . ' ' . $tutor['schedule_subjects'])); ?>">
            <!-- Avatar + name -->
            <div class="st-card-top">
                <?php if (!empty($tutor['photo_file'])): ?>
                    <img
                        src="/tutorpk/<?php echo escape_output($tutor['photo_file']); ?>"
                        alt="<?php echo escape_output($tutor['name']); ?>"
                        class="st-avatar"
                    >
                <?php else: ?>
                    <div class="st-avatar-init">👤</div>
                <?php endif; ?>
                <div>
                    <div class="st-card-name"><?php echo escape_output($tutor['name']); ?></div>
                    <div class="st-card-qual"><?php echo escape_output($tutor['qualification'] ?? 'Tutor'); ?></div>
                </div>
            </div>

            <div class="st-rating-row">
                <span class="st-stars"><?php echo $rating > 0 ? '⭐ ' . number_format($rating, 1) : '⭐ New'; ?></span>
                <?php if ($is_rec): ?>
                    <?php if ($is_top_rated): ?>
                        <span class="st-rec-badge top">Top Rated</span>
                    <?php else: ?>
                        <span class="st-rec-badge pioneer">Pioneer</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Subject pills -->
            <?php if (!empty($subjects_arr[0])): ?>
                <div class="st-subjects">
                    <?php foreach ($subjects_arr as $sub): ?>
                        <?php if (trim($sub) !== ''): ?>
                            <span class="st-subject-pill"><?php echo escape_output($sub); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Footer: experience + view button -->
            <div class="st-card-footer">
                <span class="st-exp-tag">
                    <span class="st-status-dot"></span>
                    <?php
                        $exp = (int)($tutor['experience'] ?? 0);
                        echo $exp > 0 ? $exp . ' yr' . ($exp > 1 ? 's' : '') . ' experience' : 'New tutor';
                    ?>
                </span>
                <a href="/tutorpk/student/tutor_profile.php?id=<?php echo (int) $tutor['id']; ?>" class="st-view-btn">View Profile →</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Live client-side filter as user types -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.st-tutor-card');
    const title = document.getElementById('sectionTitle');
    const count = document.getElementById('resultCount');
    const emptyState = document.getElementById('emptyState');

    if (input) {
        input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            let visibleCount = 0;

            if (term === '') {
                // Show only recommended
                cards.forEach(card => {
                    if (card.classList.contains('rec-tutor')) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                title.innerHTML = 'Top Recommended Tutors';
                count.innerHTML = 'Showing top picks';
            } else {
                // Search all
                cards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    const qual = card.getAttribute('data-qual');
                    const subs = card.getAttribute('data-subs');
                    
                    if (name.includes(term) || qual.includes(term) || subs.includes(term)) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                title.innerHTML = `Search Results for "<strong>${escapeHtml(e.target.value)}</strong>"`;
                count.innerHTML = `${visibleCount} tutor${visibleCount !== 1 ? 's' : ''} found`;
            }

            if (visibleCount === 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }
        });
        
        // Add focus ring style dynamically
        input.addEventListener('focus', () => {
            input.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.3)';
        });
        input.addEventListener('blur', () => {
            input.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
