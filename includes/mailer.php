<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

/**
 * Configure common SMTP settings for PHPMailer.
 */
function configure_smtp($mail) {
    // --- REAL GMAIL SMTP CONFIGURATION ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'YOUR_EMAIL@gmail.com'; // Add your own email here
    $mail->Password   = 'YOUR_16_DIGIT_APP_PASSWORD'; // Add your 16-digit App password here
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('YOUR_EMAIL@gmail.com', 'TutorPK Official'); // Add your own email here
    $mail->addReplyTo('info@tutorpk.com', 'TutorPK Support');
}

/**
 * Send a welcome email using PHPMailer.
 */
function send_welcome_email($to_email, $user_name, $role = 'student')
{
    $mail = new PHPMailer(true);
    try {
        configure_smtp($mail);
        $mail->addAddress($to_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = "Welcome to TutorPK – Thanks for Joining!";

        $role_label = ucfirst($role);
        $extra_message = ($role === 'tutor') 
            ? "Your tutor profile is currently under review. Our admin team will verify your credentials and approve your account shortly."
            : "You can now log in to your dashboard, search for tutors, and start learning right away!";

        $mail->Body = generate_email_template("Welcome to TutorPK!", $user_name, "Thank you for joining <strong>TutorPK</strong> as a <strong>{$role_label}</strong>! We are excited to help you achieve your goals.", $extra_message, "Go to My Dashboard");

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Send an approval email when a tutor is verified.
 */
function send_approval_email($to_email, $user_name)
{
    $mail = new PHPMailer(true);
    try {
        configure_smtp($mail);
        $mail->addAddress($to_email, $user_name);
        $mail->isHTML(true);
        $mail->Subject = "Congratulations! Your TutorPK Profile is Approved";

        $message = "Great news! Your tutor application has been reviewed and approved by our admin team.";
        $extra = "Congratulations! You are now verified. You can start your teaching journey on TutorPK, post subjects, and connect with students right away.";

        $mail->Body = generate_email_template("Profile Verified!", $user_name, $message, $extra, "Start Teaching Now");

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Reusable HTML template for emails.
 */
function generate_email_template($title, $user_name, $main_text, $extra_box, $button_text) {
    return "
    <html>
    <body style='margin:0; padding:0; font-family: Arial, sans-serif; background:#f3f4f6;'>
        <div style='max-width:520px; margin:30px auto; background:#ffffff; border-radius:32px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg, #4f46e5, #0ea5e9); padding:40px 25px; text-align:center;'>
                <h1 style='margin:0; color:#ffffff; font-size:1.8rem; font-weight:800; letter-spacing:-0.02em;'>{$title}</h1>
            </div>
            <div style='padding:35px 30px;'>
                <p style='font-size:1.1rem; color:#1e293b; margin-bottom:15px;'>Hi <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                <p style='color:#475569; line-height:1.6; font-size:0.95rem;'>
                    {$main_text}
                </p>
                <div style='color:#475569; line-height:1.6; font-size:0.95rem; background:#f8fafc; padding:15px; border-radius:12px; border-left:4px solid #4f46e5;'>
                    {$extra_box}
                </div>
                <div style='margin:35px 0; text-align:center;'>
                    <a href='http://localhost/tutorpk/login.php' style='display:inline-block; padding:14px 40px; background:#4f46e5; color:#ffffff; text-decoration:none; border-radius:12px; font-weight:700; font-size:1rem; box-shadow:0 4px 12px rgba(79, 70, 229, 0.3);'>{$button_text}</a>
                </div>
                <p style='font-size:0.8rem; color:#94a3b8; text-align:center;'>
                    If you have any questions, simply reply to this email or visit our support center.
                </p>
            </div>
            <div style='background:#f1f5f9; padding:20px 25px; text-align:center;'>
                <p style='margin:0; font-size:0.8rem; color:#64748b;'>&copy; " . date('Y') . " TutorPK Education. Pakistan's Leading Tutor Platform.</p>
            </div>
        </div>
    </body>
    </html>";
}
?>
