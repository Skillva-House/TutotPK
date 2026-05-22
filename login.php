<?php

session_start();
include "connect.php";
require_once "validation.php";

$error = "";
$saved_email = $_COOKIE['email'] ?? "";


/* ---------------- AUTO LOGIN USING TOKEN ---------------- */

if(!isset($_SESSION['id']) && isset($_COOKIE['remember_token']))
{
    $token = $_COOKIE['remember_token'];

    $stmt = $conn->prepare("SELECT id,name,role FROM users WHERE remember_token=? LIMIT 1");
    $stmt->bind_param("s",$token);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 1)
    {
        $row = $result->fetch_assoc();

        $_SESSION['id']   = $row['id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];

        if($row['role']=="student")
            header("location:student/student_dashboard.php");

        elseif($row['role']=="tutor")
            header("location:tutor/tutor_dashboard.php");

        elseif($row['role']=="admin")
            header("location:admin/admin_dashboard.php");

        exit();
    }
}


/* ---------------- LOGIN FORM SUBMIT ---------------- */

if(isset($_POST['login']))
{
    $email    = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);

    if(is_empty($email) || is_empty($password))
    {
        $error = "Email and password required";
    }
    elseif(!validate_email($email))
    {
        $error = "Invalid Email";
    }
    else
    {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1)
        {
            $row = $result->fetch_assoc();

            /* -------- TUTOR APPROVAL CHECK -------- */

            if($row['role']=="tutor" && $row['tutor_status']=="pending")
            {
                $error = "Your tutor account is waiting for admin approval.";
            }
            elseif($row['role']=="tutor" && $row['tutor_status']=="rejected")
            {
                $error = "Your tutor request was rejected by admin.";
            }

            /* -------- PASSWORD VERIFY -------- */

            elseif(password_verify($password,$row['password']))
            {

                $_SESSION['id']   = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];


                /* -------- REMEMBER ME COOKIE -------- */

                if(isset($_POST['remember']))
                {
                    setcookie("email",$email,time()+(86400*30),"/");

                    $token = bin2hex(random_bytes(32));

                    setcookie("remember_token",$token,time()+(86400*30),"/","",false,true);

                    $stmt2 = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
                    $stmt2->bind_param("si",$token,$row['id']);
                    $stmt2->execute();
                }
                else
                {
                    setcookie("remember_token","",time()-3600,"/");

                    $stmt2 = $conn->prepare("UPDATE users SET remember_token=NULL WHERE id=?");
                    $stmt2->bind_param("i",$row['id']);
                    $stmt2->execute();
                }


                /* -------- ROLE REDIRECT -------- */

                if($row['role']=="student")
                    header("location:student/student_dashboard.php");

                elseif($row['role']=="tutor")
                    header("location:tutor/tutor_dashboard.php");

                elseif($row['role']=="admin")
                    header("location:admin/admin_dashboard.php");

                exit();
            }
            else
            {
                $error = "Invalid Login";
            }
        }
        else
        {
            $error = "Invalid Login";
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<div class="auth-container">

<div style="text-align:center; margin-bottom: 20px;">
    <img src="/tutorpk/assets/index_nav.png" alt="TutorPk" style="height: 80px; object-fit: contain;">
</div>
<h2>Welcome Back</h2>
<p class="subtitle" style="text-align:center; color:#64748b; margin-bottom:20px;">Please enter your credentials to continue</p>

<?php if($error !== ""): ?>
<p class="message-error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>


<form method="POST" id="loginForm">

<input type="email" 
name="email" 
placeholder="Enter Email" 
value="<?php echo escape_output($saved_email); ?>" 
required>


<input type="password" 
name="password" 
placeholder="Enter Password" 
required>


<label class="remember-row">
<input type="checkbox" 
class="remember-checkbox" 
name="remember"
<?php echo $saved_email!==""?'checked':''; ?>>
Remember Me
</label>


<button type="submit" name="login">Login</button>

</form>

<div style="margin-top:25px; text-align:center; border-top:1px solid #f1f5f9; padding-top:20px;">
    <a href="index.php" class="auth-create-btn">Create Account</a>
</div>

<style>
.auth-create-btn {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 12px;
    border: 2px solid #10b981 !important;
    color: #10b981 !important;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s ease;
    margin-top: 5px !important;
}
.auth-create-btn:hover {
    background: #10b981;
    color: #fff !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2);
}
</style>

</div>

<!-- Spinner Overlay -->
<div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
    <div class="loader-spinner"></div>
    <div style="margin-top: 15px; font-weight: 600; color: #10b981;">Authenticating...</div>
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
document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('spinnerOverlay').style.display = 'flex';
});
</script>

</body>
</html>