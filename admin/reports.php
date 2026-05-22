<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

// Admin Guard
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_name   = $_SESSION['name'] ?? 'Admin';
$page_title  = 'Platform Reports';
$role        = 'admin';
$active_page = 'reports';

// --- DATA FETCHING ---

// 1. User Statistics
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'student'")->fetch_assoc()['c'] ?? 0;
$total_tutors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'tutor'")->fetch_assoc()['c'] ?? 0;

// 2. Enrollment Statistics
$enrolled_total = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status != 'cancelled'")->fetch_assoc()['c'] ?? 0;
$enrolled_cancelled = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE status = 'cancelled'")->fetch_assoc()['c'] ?? 0;

// 3. Financial Statistics
$financials = $conn->query("SELECT SUM(amount) as gross, SUM(commission) as system_earned FROM payments WHERE status IN ('paid', 'released')")->fetch_assoc();
$total_revenue = $financials['gross'] ?? 0;
$system_earnings = $financials['system_earned'] ?? 0;

// 4. Tutor Reports Statistics (Soft handle if table missing)
$reports_total = 0;
$reports_solved = 0;
try {
    $rep_check = $conn->query("SHOW TABLES LIKE 'tutor_reports'");
    if ($rep_check && $rep_check->num_rows > 0) {
        $rep_data = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'solved' OR status = 'resolved' THEN 1 ELSE 0 END) as solved FROM tutor_reports")->fetch_assoc();
        $reports_total = $rep_data['total'] ?? 0;
        $reports_solved = $rep_data['solved'] ?? 0;
    }
} catch (Exception $e) {}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<div class="report-header-row no-print">
    <div class="ad-header">
        <h1>Platform Analytics & Reports</h1>
        <p>Comprehensive overview of registrations, enrollments, and financial health.</p>
    </div>
    <button onclick="window.print()" class="print-btn">🖨️ Print Full Report</button>
</div>

<!-- ══ PRINTABLE REPORT CONTAINER ══ -->
<div class="report-document">
    
    <!-- Report Letterhead (Print Only) -->
    <div class="print-only report-letterhead">
        <div style="font-size: 2rem; font-weight: 800; color: #1e293b;">Tutor<span style="color:#22c55e;">Pk</span></div>
        <div style="color: #64748b; font-weight: 600;">OFFICIAL PLATFORM SUMMARY REPORT</div>
        <div style="margin-top: 10px; font-size: 0.9rem; color: #94a3b8;">Generated on: <?php echo date('F d, Y - h:i A'); ?></div>
        <hr style="margin: 20px 0; border: none; border-top: 2px solid #f1f5f9;">
    </div>

    <div class="report-section">
        <h2 class="report-section-title">👥 User & Enrollment Metrics</h2>
        <div class="report-grid">
            <div class="report-card">
                <span class="report-card-label">Students Registered</span>
                <span class="report-card-val" style="color: #10b981;"><?php echo number_format($total_students); ?></span>
            </div>
            <div class="report-card">
                <span class="report-card-label">Tutors Registered</span>
                <span class="report-card-val" style="color: #10b981;"><?php echo number_format($total_tutors); ?></span>
            </div>
            <div class="report-card">
                <span class="report-card-label">Active Enrollments</span>
                <span class="report-card-val" style="color: #10b981;"><?php echo number_format($enrolled_total); ?></span>
            </div>
            <div class="report-card">
                <span class="report-card-label">Cancelled Enrollments</span>
                <span class="report-card-val" style="color: #f59e0b;"><?php echo number_format($enrolled_cancelled); ?></span>
            </div>
        </div>
    </div>

    <div class="report-section">
        <h2 class="report-section-title">💰 Financial Performance</h2>
        <div class="report-grid">
            <div class="report-card highlight-card">
                <span class="report-card-label">Total Payments Received</span>
                <span class="report-card-val" style="color: #10b981;">Rs. <?php echo number_format($total_revenue, 2); ?></span>
            </div>
            <div class="report-card highlight-card">
                <span class="report-card-label">System Earnings (Comm.)</span>
                <span class="report-card-val" style="color: #f59e0b;">Rs. <?php echo number_format($system_earnings, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="report-section">
        <h2 class="report-section-title">🚩 Quality Control & Reports</h2>
        <div class="report-grid">
            <div class="report-card">
                <span class="report-card-label">Reports Against Tutors</span>
                <span class="report-card-val" style="color: #f59e0b;"><?php echo number_format($reports_total); ?></span>
            </div>
            <div class="report-card">
                <span class="report-card-label">Solved Reports</span>
                <span class="report-card-val" style="color: #10b981;"><?php echo number_format($reports_solved); ?></span>
            </div>
        </div>
    </div>

    <!-- Print Footer -->
    <div class="print-only" style="margin-top: 50px; text-align: center; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px;">
        This is a system-generated document from TutorPk Admin Panel.
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>


