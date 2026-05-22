 
<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$tutor_id    = $_SESSION['id'];
$page_title  = 'Tutor Dashboard';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor';
$active_page = 'tutor_dashboard';

// Fetch stats
// 1. Enrollments Statistics (Active, Total, Left)
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_enrollments,
    SUM(CASE WHEN status != 'left' OR status IS NULL THEN 1 ELSE 0 END) as active_enrollments,
    SUM(CASE WHEN status = 'left' THEN 1 ELSE 0 END) as left_enrollments 
    FROM enrollments WHERE tutor_id = ?");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$enroll_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_student_count = $enroll_stats['total_enrollments'] ?? 0;
$active_student_count = $enroll_stats['active_enrollments'] ?? 0;
$left_student_count = $enroll_stats['left_enrollments'] ?? 0;

// 2. Upcoming classes (sample - from tutor_schedule)
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM tutor_schedule WHERE tutor_id = ?");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$class_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
$stmt->close();

// 3. Total Earnings (Released)
$stmt = $conn->prepare("
    SELECT SUM(p.amount - p.commission) as earnings 
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.id
    WHERE e.tutor_id = ? AND p.status = 'released'
");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$total_earnings = $stmt->get_result()->fetch_assoc()['earnings'] ?? 0;
$stmt->close();

// 4. Pending Earnings (Paid by student, not yet released by admin)
$stmt = $conn->prepare("
    SELECT SUM(p.amount) as pending 
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.id
    WHERE e.tutor_id = ? AND p.status = 'paid'
");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$pending_earnings = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;
$stmt->close();

// 5. Average Rating
$stmt = $conn->prepare("SELECT AVG(rating) as avg_r FROM tutor_ratings WHERE tutor_id = ?");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$avg_rating = $stmt->get_result()->fetch_assoc()['avg_r'] ?? 0;
$stmt->close();

// 6. Graph Data (Current Year vs Previous Year Enrollments)
$current_year = date('Y');
$prev_year = $current_year - 1;

$stmt = $conn->prepare("
    SELECT YEAR(created_at) as yr, MONTH(created_at) as mth, COUNT(*) as cnt
    FROM enrollments
    WHERE tutor_id = ? AND YEAR(created_at) IN (?, ?)
    GROUP BY yr, mth
");
$stmt->bind_param('iii', $tutor_id, $current_year, $prev_year);
$stmt->execute();
$chart_res = $stmt->get_result();

$current_year_data = array_fill(1, 12, 0);
$prev_year_data = array_fill(1, 12, 0);

while ($row = $chart_res->fetch_assoc()) {
    if ($row['yr'] == $current_year) {
        $current_year_data[$row['mth']] = $row['cnt'];
    } else {
        $prev_year_data[$row['mth']] = $row['cnt'];
    }
}
$stmt->close();

$cy_json = json_encode(array_values($current_year_data));
$py_json = json_encode(array_values($prev_year_data));

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>
<link rel="stylesheet" href="tutor.css?v=<?php echo time(); ?>">

    <div class="ad-header">
        <h1 style="margin:0;">Welcome back, <?php echo escape_output($user_name); ?></h1>
    </div>

    <div class="ad-stats-grid">
        <a href="my_students.php" style="text-decoration:none; color:inherit;">
            <div class="ad-stat-card">
                <span class="ad-stat-label">Active Enrollments</span>
                <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$active_student_count; ?></span>
            </div>
        </a>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Overall Enrollments</span>
            <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$total_student_count; ?></span>
        </div>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Students Left</span>
            <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$left_student_count; ?></span>
        </div>
        <a href="create_schedule.php" style="text-decoration:none; color:inherit;">
            <div class="ad-stat-card">
                <span class="ad-stat-label">Active Schedules</span>
                <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$class_count; ?></span>
            </div>
        </a>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Total Earnings</span>
            <span class="ad-stat-value" style="color: #10b981;">Rs. <?php echo number_format($total_earnings, 2); ?></span>
        </div>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Pending Payout</span>
            <span class="ad-stat-value" style="color: #10b981;">Rs. <?php echo number_format($pending_earnings, 2); ?></span>
        </div>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Tutor Rating</span>
            <span class="ad-stat-value" style="color: #f59e0b;">
                ⭐ <?php echo $avg_rating > 0 ? number_format($avg_rating, 1) : "No ratings yet"; ?> 
            </span>
        </div>
    </div>

    <!-- Enrollments Graph Section -->
    <div style="margin-top: 16px; background: #fff; padding: 16px 20px; border-radius: 14px; box-shadow: 0 4px 16px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.1);">
        <h2 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">📊 Enrollment Activity (Year-over-Year)</h2>
        <canvas id="enrollmentChart" height="60"></canvas>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Current Year (<?php echo $current_year; ?>)',
                    data: <?php echo $cy_json; ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981'
                },
                {
                    label: 'Previous Year (<?php echo $prev_year; ?>)',
                    data: <?php echo $py_json; ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.4,
                    fill: false,
                    pointBackgroundColor: '#f59e0b'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top', labels: { font: { family: "'Inter', sans-serif", weight: 'bold' } } }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { stepSize: 1, font: { family: "'Inter', sans-serif" } },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    ticks: { font: { family: "'Inter', sans-serif" } },
                    grid: { display: false }
                }
            }
        }
    });
</script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>


