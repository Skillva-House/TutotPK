<?php

session_start();
include "connect.php";
require_once "validation.php";
require_once "includes/mailer.php";

$error = "";
$success = "";

if(isset($_POST['submit']))
{
    $name     = clean_input($_POST['name']);
    $email    = clean_input($_POST['email']);
    $password = $_POST['password'];

    $role = "student";
    $status = "approved";   // auto approve students

    if(is_empty($name) || is_empty($email) || is_empty($password))
    {
        $error = "All fields required";
    }
    elseif(!validate_email($email))
    {
        $error = "Invalid Email";
    }
    elseif(!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE)
    {
        $error = "Profile photo is required";
    }
    else
    {
        // Check if email exists
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        if(mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0)
        {
            $error = "Email already registered";
        }
        else
        {
            // Process upload
            function save_student_photo($file) {
                $target_dir = __DIR__ . "/assets/uploads/photos";
                $allowed = ["jpg", "jpeg", "png", "webp"];
                $max_size = 3 * 1024 * 1024;
                
                if ($file['size'] > $max_size) return "Large";
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) return "Type";

                // Enforce square profile picture on server-side
                $img_info = @getimagesize($file['tmp_name']);
                if ($img_info === false) return "Type";
                $img_w = (int)($img_info[0] ?? 0);
                $img_h = (int)($img_info[1] ?? 0);
                if ($img_w <= 0 || $img_h <= 0 || $img_w !== $img_h) return "Square";

                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                
                $new_name = uniqid("std_", true) . "." . $ext;
                if (move_uploaded_file($file['tmp_name'], $target_dir . "/" . $new_name)) {
                    return "assets/uploads/photos/" . $new_name;
                }
                return "Fail";
            }

            $photo_path = save_student_photo($_FILES['photo']);
            
            if($photo_path === "Large") $error = "Photo too large (max 3MB)";
            elseif($photo_path === "Type") $error = "Invalid photo type";
            elseif($photo_path === "Square") $error = "Profile photo must be square (equal width and height)";
            elseif($photo_path === "Fail") $error = "Photo upload failed";
            else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, tutor_status, photo_file) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $hashed_password, $role, $status, $photo_path);
                
                if(mysqli_stmt_execute($stmt))
                {
                    // Send welcome email
                    send_welcome_email($email, $name, 'student');
                    header("location:login.php");
                    exit();
                }
                else
                {
                    $error = "Registration failed: " . mysqli_error($conn);
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
</head>
<body class="auth-body">

<div class="auth-container">

<div style="text-align:center; margin-bottom: 20px;">
    <img src="/tutorpk/assets/index_nav.png" alt="TutorPk" style="height: 80px; object-fit: contain;">
</div>
<h2>Student Register</h2>

<?php if($error !== ""): ?>
<p class="message-error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if($success !== ""): ?>
<p class="message-success"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="registerForm">

<input type="text" name="name" placeholder="Enter Full Name" required>

<input type="email" name="email" placeholder="Enter Email" required>

<input type="password" name="password" placeholder="Enter Password" required>

<div style="font-size: 0.85rem; color: #6b7280; text-align: left; margin-bottom: 4px;">Profile Photo (square image required):</div>
<input type="file" name="photo" accept="image/*" required style="padding: 5px; background: #f9fafb;">

<button type="submit" name="submit">Register</button>

</form>

<a href="login.php" style="color: #10b981; font-weight: 600;">Already have account? Login</a>

</div>

<!-- Spinner Overlay -->
<div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="loader-spinner"></div>
    <div style="margin-top: 15px; font-weight: 600; color: #10b981;">Sending Welcome Email...</div>
</div>

<style>
.loader-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #10b981;
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
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const photoInput = document.querySelector('input[name="photo"]');
    if (!photoInput || !photoInput.files || photoInput.files.length === 0) {
        photoInput.reportValidity();
        return;
    }

    const photo = photoInput.files[0];
    const allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (photo.type && !allowedPhotoTypes.includes(photo.type)) {
        photoInput.setCustomValidity('Invalid photo type. Please upload JPG, PNG, or WebP.');
        photoInput.reportValidity();
        return;
    }

    if (photo.size > 3 * 1024 * 1024) {
        photoInput.setCustomValidity('Photo too large (max 3MB).');
        photoInput.reportValidity();
        return;
    }

    const isSquare = await new Promise((resolve) => {
        try {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const img = new Image();
                img.onload = function() {
                    resolve(img.naturalWidth === img.naturalHeight);
                };
                img.onerror = function() { resolve(false); };
                img.src = ev.target.result;
            };
            reader.onerror = function() { resolve(false); };
            reader.readAsDataURL(photo);
        } catch (err) {
            resolve(false);
        }
    });

    if (!isSquare) {
        photoInput.setCustomValidity('Profile photo must be square (equal width and height).');
        photoInput.reportValidity();
        return;
    }

    photoInput.setCustomValidity('');
    document.getElementById('spinnerOverlay').style.display = 'flex';
    e.target.submit();
});
</script>

</body>
</html>