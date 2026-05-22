<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = (int) $_SESSION['id'];
$user_name = $_SESSION['name'] ?? 'User';
$total_amount = 200.00;

// Handle Upgrade Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_upgrade'])) {
    $proof_error = "";
    $proof_name = save_uploaded_file(
        $_FILES['payment_proof'],
        __DIR__ . "/../assets/uploads/payments",
        ["jpg", "jpeg", "png", "webp"],
        3 * 1024 * 1024,
        $proof_error
    );

    if ($proof_name === "") {
        $_SESSION['upgrade_msg'] = "Error uploading proof: " . $proof_error;
        $_SESSION['upgrade_type'] = 'error';
    } else {
        $proof_path = "assets/uploads/payments/" . $proof_name;

        // Insert into chatbot_upgrades
        $stmt = $conn->prepare("INSERT INTO chatbot_upgrades (user_id, proof_file, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param('is', $user_id, $proof_path);
        
        if ($stmt->execute()) {
            $_SESSION['upgrade_msg'] = "Your upgrade request has been submitted! Admin will verify your payment proof shortly. Once approved, you will have unlimited chatbot access for 1 year.";
            $_SESSION['upgrade_type'] = 'success';
            
            // Redirect back to assistant
            $redirect = ($_SESSION['role'] === 'tutor') ? '../tutor/assistant.php' : 'assistant.php';
            header("Location: $redirect");
            exit();
        } else {
            $_SESSION['upgrade_msg'] = "Error submitting request. Please try again.";
            $_SESSION['upgrade_type'] = 'error';
        }
        $stmt->close();
    }
}

$page_title = "Upgrade Chatbot";
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="stylesheet" href="student.css?v=<?php echo time(); ?>">

<div class="payment-container" style="min-height: 80vh; align-items: center; display: flex; justify-content: center; padding: 40px 20px;">
    <div class="payment-card" style="background: #ffffff; max-width: 500px; width: 100%; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); padding: 40px; text-align: center; border: 1px solid #f1f5f9;">
        
        <?php if (isset($_SESSION['upgrade_msg'])): ?>
            <div style="margin-bottom: 20px; padding: 15px; border-radius: 12px; background: <?php echo $_SESSION['upgrade_type'] === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $_SESSION['upgrade_type'] === 'success' ? '#166534' : '#991b1b'; ?>; font-size: 0.9rem; border-left: 4px solid <?php echo $_SESSION['upgrade_type'] === 'success' ? '#16a34a' : '#ef4444'; ?>;">
                <?php echo htmlspecialchars($_SESSION['upgrade_msg']); ?>
            </div>
            <?php unset($_SESSION['upgrade_msg'], $_SESSION['upgrade_type']); ?>
        <?php endif; ?>

        <div class="payment-header">
            <div style="font-size: 4rem; margin-bottom: 15px;">🚀</div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 0;">Upgrade to Premium</h1>
            <p style="color: #64748b; margin-top: 10px; line-height: 1.5;">Get unlimited AI Chatbot access for a full year and boost your learning journey!</p>
        </div>

        <div class="payment-summary" style="background: #f8fafc; border-radius: 16px; padding: 25px; margin: 30px 0; border: 1px solid #e2e8f0; text-align: left;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <span style="color: #64748b;">Plan Type</span>
                <strong style="color: #1e293b;">1 Year Unlimited AI</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <span style="color: #64748b;">Includes</span>
                <strong style="color: #1e293b;">Unlimited Prompts</strong>
            </div>
            <div style="height: 1px; background: #e2e8f0; margin: 15px 0;"></div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: #1e293b; font-weight: 700;">Total Investment</span>
                <span style="font-size: 1.5rem; font-weight: 900; color: #f59e0b;">Rs 200</span>
            </div>
        </div>

        <div class="payment-instructions" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 20px; text-align: left; margin-bottom: 30px;">
            <p style="margin: 0 0 10px 0; font-size: 0.85rem; font-weight: 800; color: #92400e; display: flex; align-items: center; gap: 8px;">
                <span>💳</span> How to Pay:
            </p>
            <ul style="margin: 0; padding-left: 20px; font-size: 0.8rem; color: #92400e; line-height: 1.6;">
                <li>Send <strong>Rs 200</strong> to <strong>0321-1234567</strong> (EasyPaisa/JazzCash).</li>
                <li>Write your Name in the payment reference.</li>
                <li>Take a screenshot of the confirmation and upload it below.</li>
            </ul>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div style="text-align: left; margin-bottom: 25px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 10px;">Upload Payment Screenshot</label>
                <div style="position: relative; overflow: hidden;">
                    <input type="file" name="payment_proof" accept="image/*" required 
                           style="width: 100%; padding: 12px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; cursor: pointer;">
                </div>
                <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 8px;">Max file size: 3MB (JPG, PNG, WebP)</p>
            </div>
            
            <button type="submit" name="submit_upgrade" 
                    style="width: 100%; padding: 18px; background: linear-gradient(135deg, #10b981 0%, #1a7d30 100%); color: #fff; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: transform 0.2s;">
                Submit Upgrade Proof
            </button>
            
            <?php 
                $cancel_url = ($_SESSION['role'] === 'tutor') ? '../tutor/assistant.php' : 'assistant.php';
            ?>
            <a href="<?php echo $cancel_url; ?>" style="display: block; margin-top: 20px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Cancel and go back</a>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
