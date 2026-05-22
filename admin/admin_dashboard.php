<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';



// ----- Admin Guard -----
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name   = $_SESSION['name'] ?? 'Admin';
$page_title  = 'Admin Dashboard';
$role        = 'admin';
$active_page = 'admin_dashboard';

// ----- Fetch Stats & Tutors -----

// 1. Total pending applications
$pending_query = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'tutor' AND (tutor_status = 'pending' OR tutor_status IS NULL)");
$pending_count = $pending_query ? $pending_query->fetch_assoc()['c'] : 0;

// 2. Total approved tutors
$approved_query = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'tutor' AND tutor_status = 'approved'");
$approved_count = $approved_query ? $approved_query->fetch_assoc()['c'] : 0;

// 3. Total students
$students_query = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'student'");
$students_count = $students_query ? $students_query->fetch_assoc()['c'] : 0;

// 5. Graph Data for Current Year
$current_year = date('Y');

// 5a. Student Registrations
$stmt = $conn->prepare("SELECT MONTH(created_at) as mth, COUNT(*) as cnt FROM users WHERE role = 'student' AND YEAR(created_at) = ? GROUP BY mth");
$stmt->bind_param('i', $current_year);
$stmt->execute();
$res_stu = $stmt->get_result();
$stu_data = array_fill(1, 12, 0);
while ($r = $res_stu->fetch_assoc()) $stu_data[$r['mth']] = $r['cnt'];
$stmt->close();
$stu_json = json_encode(array_values($stu_data));

// 5b. Tutor Verifications
$stmt = $conn->prepare("SELECT MONTH(created_at) as mth, COUNT(*) as cnt FROM users WHERE role = 'tutor' AND tutor_status = 'approved' AND YEAR(created_at) = ? GROUP BY mth");
$stmt->bind_param('i', $current_year);
$stmt->execute();
$res_tut = $stmt->get_result();
$tut_data = array_fill(1, 12, 0);
while ($r = $res_tut->fetch_assoc()) $tut_data[$r['mth']] = $r['cnt'];
$stmt->close();
$tut_json = json_encode(array_values($tut_data));

// 5c. Admin Earnings
$stmt = $conn->prepare("SELECT MONTH(created_at) as mth, SUM(commission) as amt FROM payments WHERE status IN ('paid', 'released') AND YEAR(created_at) = ? GROUP BY mth");
$stmt->bind_param('i', $current_year);
$stmt->execute();
$res_earn = $stmt->get_result();
$earn_data = array_fill(1, 12, 0);
while ($r = $res_earn->fetch_assoc()) $earn_data[$r['mth']] = (float)$r['amt'];
$stmt->close();
$earn_json = json_encode(array_values($earn_data));


include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Load specific styles manually just to ensure they work reliably -->


<div style="padding: 4px 0 0;">

    <div class="ad-header">
        <h1>Welcome, <?php echo escape_output($user_name); ?></h1>
    </div>

    <!-- Stats Overview -->
    <div class="ad-stats-grid">
        <div class="ad-stat-card">
            <span class="ad-stat-label">Pending Tutor Applications</span>
            <span class="ad-stat-value" style="color: #f59e0b;"><?php echo (int)$pending_count; ?></span>
        </div>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Approved Tutors</span>
            <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$approved_count; ?></span>
        </div>
        <div class="ad-stat-card">
            <span class="ad-stat-label">Registered Students</span>
            <span class="ad-stat-value" style="color: #10b981;"><?php echo (int)$students_count; ?></span>
        </div>
    </div>
    <!-- ══ ANALYTICS GRAPHS ══ -->
    <div class="dash-section-head" style="margin-top: 40px; display:flex; align-items:center; gap:10px;">
        <span class="dash-section-dot" style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span>
        <span class="dash-section-head-line" style="font-size:1.05rem; font-weight:800; color:#111827;">Platform Analytics (<?php echo $current_year; ?>)</span>
        <span class="dash-section-divider" style="flex:1; height:1px; background:linear-gradient(90deg, #e5e7eb, transparent);"></span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 20px; margin-top:20px;">
        <!-- Chart 1 -->
        <div style="background: #fff; border-radius: 18px; padding: 20px; box-shadow: 0 4px 16px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.1);">
            <h3 style="font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-user-graduate" style="color: #10b981;"></i> Student Registrations</h3>
            <canvas id="studentChart" height="200"></canvas>
        </div>
        <!-- Chart 2 -->
        <div style="background: #fff; border-radius: 18px; padding: 20px; box-shadow: 0 4px 16px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.1);">
            <h3 style="font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-chalkboard-user" style="color: #10b981;"></i> Tutor Verifications</h3>
            <canvas id="tutorChart" height="200"></canvas>
        </div>
        <!-- Chart 3 -->
        <div style="background: #fff; border-radius: 18px; padding: 20px; box-shadow: 0 4px 16px rgba(15,23,42,0.06), 0 0 0 1px rgba(148,163,184,0.1);">
            <h3 style="font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-sack-dollar" style="color: #f59e0b;"></i> Platform Earnings</h3>
            <canvas id="earningChart" height="200"></canvas>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const commonOptions = {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { font: { family: "'Inter', sans-serif" } }, grid: { color: '#f1f5f9' } },
            x: { ticks: { font: { family: "'Inter', sans-serif" } }, grid: { display: false } }
        }
    };
    const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Student Chart
    new Chart(document.getElementById('studentChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: <?php echo $stu_json; ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 4
            }]
        },
        options: { ...commonOptions, scales: { ...commonOptions.scales, y: { ...commonOptions.scales.y, ticks: { stepSize: 1, font: {family: "'Inter', sans-serif"} } } } }
    });

    // Tutor Chart
    new Chart(document.getElementById('tutorChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: <?php echo $tut_json; ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 4
            }]
        },
        options: { ...commonOptions, scales: { ...commonOptions.scales, y: { ...commonOptions.scales.y, ticks: { stepSize: 1, font: {family: "'Inter', sans-serif"} } } } }
    });

    // Earnings Chart
    new Chart(document.getElementById('earningChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: <?php echo $earn_json; ?>,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#f59e0b'
            }]
        },
        options: { ...commonOptions, plugins: { tooltip: { callbacks: { label: function(context) { return 'Rs. ' + context.parsed.y.toFixed(2); } } } } }
    });
</script>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
