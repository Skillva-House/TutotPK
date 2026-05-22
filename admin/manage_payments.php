<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$type = 'success';

// Handle Release Payment
if (isset($_POST['release_payment'])) {
    $payment_id = (int)$_POST['payment_id'];
    $enrollment_id = (int)$_POST['enrollment_id'];
    $amount = (float)$_POST['amount'];
    
    // Calculate 15% commission
    $commission = $amount * 0.15;
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Update payment record
        $upd_pay = $conn->prepare("UPDATE payments SET commission = ?, status = 'released' WHERE payment_id = ?");
        $upd_pay->bind_param('di', $commission, $payment_id);
        $upd_pay->execute();
        
        // Update enrollment record
        $upd_enroll = $conn->prepare("UPDATE enrollments SET payment_status = 'released' WHERE id = ?");
        $upd_enroll->bind_param('i', $enrollment_id);
        $upd_enroll->execute();
        
        $conn->commit();
        $message = "Payment released! Rs. " . number_format($commission, 2) . " commission deducted.";
        $type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error releasing payment: " . $e->getMessage();
        $type = 'error';
    }
}

// Fetch Pending Releases
$query = "
    SELECT p.payment_id, p.amount, p.proof_file, p.created_at, e.id as enrollment_id, e.subject_name, e.package_type,
           s.name as student_name, t.name as tutor_name
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.id
    JOIN users s ON e.student_id = s.id
    JOIN users t ON e.tutor_id = t.id
    WHERE p.status = 'paid'
    ORDER BY p.created_at DESC
";
$result = $conn->query($query);

$page_title = "Manage Payments";
$role = "admin";
$active_page = "manage_payments";
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<div class="ad-header">
    <h1>Payment Management</h1>
    <p>Review and release payments to tutors (15% commission will be deducted automatically).</p>
</div>

<?php if ($message): ?>
    <div class="vt-alert <?php echo $type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="tp-section-card">
    <table style="width:100%; border-collapse: collapse; margin-top:15px;">
        <thead>
            <tr style="background:#f8fafc; text-align:left;">
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Date</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Student</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Tutor</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Subject (Pkg)</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Amount</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Proof</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <?php echo htmlspecialchars($row['subject_name']); ?> 
                            <span style="font-size:0.75rem; background:#e2e8f0; padding:2px 6px; border-radius:4px;"><?php echo $row['package_type']; ?> classes</span>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9; font-weight:700; color:#1e293b;">
                            Rs. <?php echo number_format($row['amount'], 2); ?>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <?php if ($row['proof_file']): ?>
                                <a href="../<?php echo $row['proof_file']; ?>" target="_blank" style="color:#4f46e5; font-size:0.75rem; text-decoration:underline;">View Proof</a>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:0.75rem;">No Proof</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                <input type="hidden" name="enrollment_id" value="<?php echo $row['enrollment_id']; ?>">
                                <input type="hidden" name="amount" value="<?php echo $row['amount']; ?>">
                                <button type="submit" name="release_payment" class="tp-sch-enroll-btn" style="padding:6px 12px; font-size:0.75rem;">Release to Tutor</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding:40px; text-align:center; color:#94a3b8;">No pending payments to release.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

