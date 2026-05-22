<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$enroll_id   = (int) ($_GET['enroll_id'] ?? 0);
$schedule_id = (int) ($_GET['schedule_id'] ?? 0);

if ($enroll_id <= 0 || $schedule_id <= 0) {
    die("Invalid payment request.");
}

// Fetch enrollment info
$enroll_stmt = $conn->prepare("
    SELECT e.*, u.name as tutor_name 
    FROM enrollments e 
    JOIN users u ON e.tutor_id = u.id 
    WHERE e.id = ? AND e.student_id = ?
");
$enroll_stmt->bind_param('ii', $enroll_id, $_SESSION['id']);
$enroll_stmt->execute();
$enroll = $enroll_stmt->get_result()->fetch_assoc();
$enroll_stmt->close();

if (!$enroll) {
    die("Enrollment not found.");
}

// Fetch price from schedule
$sch_stmt = $conn->prepare("SELECT price_1, price_2, price_5, price_12 FROM tutor_schedule WHERE id = ?");
$sch_stmt->bind_param('i', $schedule_id);
$sch_stmt->execute();
$sch = $sch_stmt->get_result()->fetch_assoc();
$sch_stmt->close();

$price_col = "price_" . $enroll['package_type'];
$total_amount = $sch[$price_col] ?? 0.00;

// Handle "Payment"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    // 1. Validate File
    $proof_error = "";
    $proof_name = save_uploaded_file(
        $_FILES['payment_proof'],
        __DIR__ . "/../assets/uploads/payments",
        ["jpg", "jpeg", "png", "webp"],
        3 * 1024 * 1024,
        $proof_error
    );

    if ($proof_name === "") {
        $_SESSION['enroll_msg'] = "Error uploading proof: " . $proof_error;
        $_SESSION['enroll_type'] = 'error';
    } else {
        $proof_path = "assets/uploads/payments/" . $proof_name;

        // 2. Update Enrollment status
        $upd_enroll = $conn->prepare("UPDATE enrollments SET payment_status = 'paid', paid_amount = ? WHERE id = ?");
        $upd_enroll->bind_param('di', $total_amount, $enroll_id);
        
        if ($upd_enroll->execute()) {
            // 3. Insert into Payments table
            $pay_stmt = $conn->prepare("INSERT INTO payments (enrollment_id, amount, proof_file, status) VALUES (?, ?, ?, 'paid')");
            $pay_stmt->bind_param('ids', $enroll_id, $total_amount, $proof_path);
            $pay_stmt->execute();
            $pay_stmt->close();

            // --- GROUP CHAT LOGIC (Auto-create if 2+ paid students) ---
            $tutor_id = $enroll['tutor_id'];
            $subject = $enroll['subject_name'];
            
            $count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM enrollments WHERE tutor_id = ? AND subject_name = ? AND payment_status = 'paid'");
            $count_stmt->bind_param('is', $tutor_id, $subject);
            $count_stmt->execute();
            $paid_count = $count_stmt->get_result()->fetch_assoc()['c'] ?? 0;
            $count_stmt->close();
            
            if ($paid_count >= 2) {
                // Find if group exists
                $grp_stmt = $conn->prepare("SELECT group_id FROM chat_groups WHERE tutor_id = ? AND subject_name = ?");
                $grp_stmt->bind_param('is', $tutor_id, $subject);
                $grp_stmt->execute();
                $grp_res = $grp_stmt->get_result();
                
                if ($grp_res->num_rows === 0) {
                    // Create Group
                    $ins_grp = $conn->prepare("INSERT INTO chat_groups (tutor_id, subject_name) VALUES (?, ?)");
                    $ins_grp->bind_param('is', $tutor_id, $subject);
                    $ins_grp->execute();
                    $group_id = $ins_grp->insert_id;
                    $ins_grp->close();
                    
                    // Add Tutor and all paid students
                    $ins_mem = $conn->prepare("INSERT IGNORE INTO chat_group_members (group_id, user_id) VALUES (?, ?)");
                    
                    // Tutor
                    $ins_mem->bind_param('ii', $group_id, $tutor_id);
                    $ins_mem->execute();
                    
                    // All paid students
                    $all_st = $conn->prepare("SELECT student_id FROM enrollments WHERE tutor_id = ? AND subject_name = ? AND payment_status = 'paid'");
                    $all_st->bind_param('is', $tutor_id, $subject);
                    $all_st->execute();
                    $st_res = $all_st->get_result();
                    while($st_row = $st_res->fetch_assoc()) {
                        $sid = $st_row['student_id'];
                        $ins_mem->bind_param('ii', $group_id, $sid);
                        $ins_mem->execute();
                    }
                    $all_st->close();
                    $ins_mem->close();
                } else {
                    // Group exists, just add current student
                    $group_id = $grp_res->fetch_assoc()['group_id'];
                    $ins_mem = $conn->prepare("INSERT IGNORE INTO chat_group_members (group_id, user_id) VALUES (?, ?)");
                    $ins_mem->bind_param('ii', $group_id, $_SESSION['id']);
                    $ins_mem->execute();
                    $ins_mem->close();
                }
                $grp_stmt->close();
            }

            $_SESSION['enroll_msg'] = "Payment successful! You are now enrolled in " . htmlspecialchars($enroll['subject_name']) . ".";
            $_SESSION['enroll_type'] = 'success';
            header("Location: my_classes.php");
            exit();
        }
        $upd_enroll->close();
    }
}

$page_title = "Complete Payment";
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="stylesheet" href="student.css?v=<?php echo time(); ?>">

<?php if (isset($_SESSION['enroll_msg'])): ?>
    <div style="margin: 20px; padding: 12px 16px; border-radius: 8px; background: #dcfce7; color: #166534; font-size: 0.9rem; border-left: 4px solid #16a34a;">
        <?php echo htmlspecialchars($_SESSION['enroll_msg']); ?>
    </div>
    <?php unset($_SESSION['enroll_msg'], $_SESSION['enroll_type']); ?>
<?php endif; ?>

<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <span class="payment-icon">💳</span>
            <h1>Secure Checkout</h1>
            <p>Complete your enrollment for <?php echo htmlspecialchars($enroll['subject_name']); ?></p>
        </div>

        <div class="payment-summary">
            <div class="summary-row">
                <span>Tutor</span>
                <strong><?php echo htmlspecialchars($enroll['tutor_name']); ?></strong>
            </div>
            <div class="summary-row">
                <span>Subject</span>
                <strong><?php echo htmlspecialchars($enroll['subject_name']); ?></strong>
            </div>
            <div class="summary-row">
                <span>Package</span>
                <strong><?php echo $enroll['package_type']; ?> Class(es)</strong>
            </div>
            <div class="payment-divider"></div>
            <div class="summary-row total">
                <span>Total Amount</span>
                <span class="total-price">Rs. <?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

        <div class="mock-payment-form">
            <form method="POST" enctype="multipart/form-data">
                <div style="text-align: left; margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #1e293b; margin-bottom: 8px;">Upload Payment Proof (Screenshot)</label>
                    <input type="file" name="payment_proof" accept="image/*" required style="width: 100%; padding: 10px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #fff;">
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">Take a screenshot of your successful transaction and upload it here.</p>
                </div>
                <button type="submit" name="pay_now" class="pay-btn">Confirm & Pay Rs. <?php echo number_format($total_amount, 2); ?></button>
                <a href="tutor_profile.php?id=<?php echo $enroll['tutor_id']; ?>" class="pay-cancel">Cancel</a>
            </form>
        </div>
    </div>
</div>

<style>
.payment-container {
    display: flex;
    justify-content: center;
    padding: 40px 20px;
}
.payment-card {
    background: #ffffff;
    max-width: 450px;
    width: 100%;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 30px;
    text-align: center;
}
.payment-header h1 {
    font-size: 1.5rem;
    color: #1e293b;
    margin: 10px 0;
}
.payment-header p {
    color: #64748b;
    font-size: 0.9rem;
}
.payment-icon {
    font-size: 3rem;
}
.payment-summary {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin: 25px 0;
    text-align: left;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 0.9rem;
}
.summary-row span { color: #64748b; }
.summary-row强 { color: #1e293b; }
.payment-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 15px 0;
}
.summary-row.total {
    font-size: 1.1rem;
    font-weight: 800;
}
.total-price { color: #f59e0b; }

.mock-alert {
    background: #fffbeb;
    color: #92400e;
    padding: 12px;
    border-radius: 10px;
    font-size: 0.8rem;
    margin-bottom: 20px;
    border: 1px solid #fde68a;
}
.pay-btn {
    width: 100%;
    padding: 14px;
    background: #10b981;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.2s;
}
.pay-btn:hover { background: #059669; }
.pay-cancel {
    display: block;
    margin-top: 15px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.9rem;
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

