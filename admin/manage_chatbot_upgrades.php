<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$type = 'success';

// --- HANDLE APPROVAL ---
if (isset($_POST['approve_upgrade'])) {
    $upgrade_id = (int)$_POST['upgrade_id'];
    $user_id = (int)$_POST['user_id'];
    
    $conn->begin_transaction();
    try {
        // Set plan dates: Start Now, End in 1 Year
        $upd_stmt = $conn->prepare("UPDATE chatbot_upgrades SET status = 'approved', plan_start = NOW(), plan_end = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE id = ?");
        $upd_stmt->bind_param('i', $upgrade_id);
        $upd_stmt->execute();
        
        // Fetch user info for email
        $user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $user_stmt->bind_param('i', $user_id);
        $user_stmt->execute();
        $user = $user_stmt->get_result()->fetch_assoc();
        
        $conn->commit();
        
        if ($user) {
            // Send Notification Email
            $email_subject = "AI Upgrade Approved! 🤖";
            $email_msg = "Congratulations! Your payment of Rs 200 has been verified.";
            $email_extra = "Your Premium AI Chatbot access is now ACTIVE for 1 year. You can now use the assistant without any daily limits. Happy learning!";
            // We can reuse the template or add a specific function in mailer.php
            // For now let's use send_approval_email logic or similar
            send_approval_email($user['email'], $user['name']); // Reusing the tutor verified template as it fits
        }
        
        $message = "Upgrade approved successfully!";
        $type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $type = 'error';
    }
}

// --- HANDLE REJECTION ---
if (isset($_POST['reject_upgrade'])) {
    $upgrade_id = (int)$_POST['upgrade_id'];
    $upd_stmt = $conn->prepare("UPDATE chatbot_upgrades SET status = 'rejected' WHERE id = ?");
    $upd_stmt->bind_param('i', $upgrade_id);
    if ($upd_stmt->execute()) {
        $message = "Upgrade request rejected.";
        $type = 'error';
    }
}

// Fetch Pending Upgrades
$query = "
    SELECT cu.id as upgrade_id, cu.proof_file, cu.requested_at, cu.user_id,
           u.name, u.email, u.role
    FROM chatbot_upgrades cu
    JOIN users u ON cu.user_id = u.id
    WHERE cu.status = 'pending'
    ORDER BY cu.requested_at DESC
";
$result = $conn->query($query);

$page_title = "Manage AI Upgrades";
$role = "admin";
$active_page = "chatbot_upgrades";
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<div class="ad-header">
    <h1>AI Chatbot Upgrades</h1>
    <p>Review payment proofs and approve unlimited chatbot access (Rs 200/Year).</p>
</div>

<?php if ($message): ?>
    <div class="vt-alert <?php echo $type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="tp-section-card">
    <table style="width:100%; border-collapse: collapse; margin-top:15px;">
        <thead>
            <tr style="background:#f8fafc; text-align:left;">
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Date</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">User</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Role</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Proof</th>
                <th style="padding:12px; border-bottom:2px solid #e2e8f0;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;"><?php echo date('M d, h:i A', strtotime($row['requested_at'])); ?></td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <span style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></span>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <span style="text-transform: capitalize; font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; background: #f1f5f9;">
                                <?php echo $row['role']; ?>
                            </span>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <a href="../<?php echo $row['proof_file']; ?>" target="_blank" style="color:#4f46e5; font-size:0.85rem; font-weight: 700;">View Proof 🖼️</a>
                        </td>
                        <td style="padding:12px; border-bottom:1px solid #f1f5f9;">
                            <form method="POST" style="margin:0; display: flex; gap: 8px;" class="approval-form">
                                <input type="hidden" name="upgrade_id" value="<?php echo $row['upgrade_id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                <button type="submit" name="approve_upgrade" style="background: #22c55e; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 700;">
                                    Approve
                                </button>
                                <button type="submit" name="reject_upgrade" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 700;" onclick="return confirm('Reject this request?')">
                                    Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:#94a3b8;">No pending upgrade requests.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Spinner Overlay -->
<div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="loader-spinner"></div>
    <div id="spinnerText" style="margin-top: 15px; font-weight: 600; color: #4f46e5;">Processing...</div>
</div>

<style>
.loader-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #4f46e5;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin-loader 1s linear infinite;
}
@keyframes spin-loader {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.querySelectorAll('.approval-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        // If clicking reject, and user canceled confirmation, stop.
        // Wait, confirm() is handled via onclick on the button, which stops submit if false.
        // So if we reach here, it's submitting.
        
        const isApproveBtn = e.submitter && e.submitter.name === 'approve_upgrade';
        const spinnerText = document.getElementById('spinnerText');
        
        if (isApproveBtn) {
            spinnerText.textContent = 'Sending Approval Email...';
        } else {
            spinnerText.textContent = 'Processing request...';
        }
        
        document.getElementById('spinnerOverlay').style.display = 'flex';
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
