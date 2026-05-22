<?php
session_start();

include "../connect.php";
require_once "../validation.php";
require_once __DIR__ . "/../includes/mailer.php";

$error = "";
$success = "";


if (isset($_POST['submit'])) {
    $full_name     = clean_input($_POST['full_name'] ?? "");
    $email         = clean_input($_POST['email'] ?? "");
    $password      = $_POST['password'] ?? "";
    $cnic          = clean_input($_POST['cnic'] ?? "");
    $gender        = clean_input($_POST['gender'] ?? "");
    $qualification = clean_input($_POST['qualification'] ?? "");
    $degree_field   = clean_input($_POST['degree_field'] ?? "");
    $teaching_level = clean_input($_POST['teaching_level'] ?? "");
    $subjects       = clean_input($_POST['subjects'] ?? "");
    $experience     = (int)($_POST['experience'] ?? 0);

    $allowed_gender = ["Male", "Female", "Other"];

    if (
        is_empty($full_name) ||
        is_empty($email) ||
        is_empty($password) ||
        is_empty($cnic) ||
        is_empty($gender) ||
        is_empty($qualification) ||
        is_empty($degree_field) ||
        is_empty($teaching_level) ||
        is_empty($subjects) ||
        $experience < 0
    ) {
        $error = "All fields are required.";
    } elseif (!validate_email($email)) {
        $error = "Invalid email address.";
    } elseif (!validate_password($password)) {
        $error = "Password must be at least 6 characters.";
    } elseif (!in_array($gender, $allowed_gender, true)) {
        $error = "Invalid gender selection.";
    } elseif (!isset($_FILES['cv']) || !isset($_FILES['photo'])) {
        $error = "CV and profile photo are required.";
    } else {
        $check_stmt = mysqli_prepare($conn, "SELECT email, cnic FROM users WHERE email = ? OR cnic = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "ss", $email, $cnic);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $row = mysqli_fetch_assoc($check_result);
            if ($row['email'] === $email) {
                $error = "This email is used by another user. Please try with a different email.";
            } else {
                $error = "This CNIC is used by another user. Please try with a different CNIC.";
            }
        } else {
            // Basic CNIC format validation — require exactly 13 numeric digits (no letters or other characters)
            if (!preg_match('/^\d{13}$/', $cnic)) {
                $error = "CNIC must be exactly 13 digits (numeric only).";
            }

            // stop early if CNIC invalid
            if ($error === "") {
                // Validate uploaded CV and photo MIME and size before saving
                $cv_file_upload = $_FILES['cv'];
                $photo_file_upload = $_FILES['photo'];

                // CV: size and MIME
                if (!isset($cv_file_upload['tmp_name']) || !is_uploaded_file($cv_file_upload['tmp_name'])) {
                    $error = "Invalid CV upload.";
                } elseif ($cv_file_upload['size'] > 5 * 1024 * 1024) {
                    $error = "CV file is too large (max 5MB).";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $cv_mime = finfo_file($finfo, $cv_file_upload['tmp_name']);
                    finfo_close($finfo);
                    if ($cv_mime !== 'application/pdf') {
                        $error = "CV must be a PDF file.";
                    }
                }

                // Photo: size and image verification
                if ($error === "") {
                    if (!isset($photo_file_upload['tmp_name']) || !is_uploaded_file($photo_file_upload['tmp_name'])) {
                        $error = "Invalid photo upload.";
                    } elseif ($photo_file_upload['size'] > 3 * 1024 * 1024) {
                        $error = "Photo is too large (max 3MB).";
                    } else {
                        $img_info = @getimagesize($photo_file_upload['tmp_name']);
                        $allowed_image_mimes = ['image/jpeg', 'image/png', 'image/webp'];
                        if ($img_info === false || !in_array($img_info['mime'], $allowed_image_mimes, true)) {
                            $error = "Profile photo must be a valid JPG/PNG/WebP image.";
                        } else {
                            // Enforce square image on server-side as well
                            $img_w = (int)($img_info[0] ?? 0);
                            $img_h = (int)($img_info[1] ?? 0);
                            if ($img_w === 0 || $img_h === 0 || $img_w !== $img_h) {
                                $error = "Profile photo must be square (width and height equal).";
                            }
                        }
                    }
                }
            }

            // Proceed only if no errors so far
            if ($error === "") {
            $cv_error = "";
            $photo_error = "";

                $cv_name = save_uploaded_file(
                    $_FILES['cv'],
                    __DIR__ . "/../assets/uploads/cv",
                    ["pdf"],
                    5 * 1024 * 1024,
                    $cv_error
                );

            if ($cv_name === "") {
                $error = "CV upload error: " . $cv_error;
            } else {
                $photo_name = save_uploaded_file(
                    $_FILES['photo'],
                    __DIR__ . "/../assets/uploads/photos",
                    ["jpg", "jpeg", "png", "webp"],
                    3 * 1024 * 1024,
                    $photo_error
                );

                if ($photo_name === "") {
                    $error = "Photo upload error: " . $photo_error;
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $cv_path = "assets/uploads/cv/" . $cv_name;
                    $photo_path = "assets/uploads/photos/" . $photo_name;
                    $role = "tutor";
                    $tutor_status = "pending";

                    $full_qualification = trim($qualification . (!empty($degree_field) ? " in " . $degree_field : ""));

                    $insert_sql = "INSERT INTO users
                        (name, email, password, role, cnic, gender, qualification, teaching_level, subjects, experience, cv_file, photo_file, tutor_status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $insert_stmt = mysqli_prepare($conn, $insert_sql);

                    if ($insert_stmt) {
                        mysqli_stmt_bind_param(
                            $insert_stmt,
                            "sssssssssisss",
                            $full_name,
                            $email,
                            $password_hash,
                            $role,
                            $cnic,
                            $gender,
                            $full_qualification,
                            $teaching_level,
                            $subjects,
                            $experience,
                            $cv_path,
                            $photo_path,
                            $tutor_status
                        );

                        if (mysqli_stmt_execute($insert_stmt)) {
                            // Send welcome email
                            send_welcome_email($email, $full_name, 'tutor');
                            header("Location: registration_success.php");
                            exit();
                        }

                        $error = "Could not save tutor registration.";
                    } else {
                        $error = "Could not prepare registration query.";
                    }
                }
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tutor Registration – TutorPK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
    <link rel="stylesheet" href="tutor.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <div style="text-align:center; margin-bottom: 20px;">
    <img src="/tutorpk/assets/index_nav.png" alt="TutorPk" style="height: 80px; object-fit: contain;">
</div>
<h2>Tutor Registration</h2>
    <p class="subtitle">Create your TutorPK account and start teaching online.</p>

    <?php if ($error !== ""): ?>
        <p class="message-error"><?php echo escape_output($error); ?></p>
    <?php endif; ?>

    <?php if ($success !== ""): ?>
        <p class="message-success"><?php echo escape_output($success); ?></p>
    <?php endif; ?>

    <div id="clientError" class="message-error" style="display:none; margin-bottom:12px;"></div>

    <form id="registrationForm" method="POST" action="" enctype="multipart/form-data">
        <div id="step1" style="display: flex; flex-direction: column; gap: 0.75rem;">
            <input type="text" name="full_name" placeholder="Full Name" value="<?php echo escape_output($_POST['full_name'] ?? ''); ?>" required>

            <input type="email" name="email" placeholder="Email Address" value="<?php echo escape_output($_POST['email'] ?? ''); ?>" required>

            <input type="password" name="password" placeholder="Password" required>

            <input type="text" name="cnic" placeholder="CNIC (13 digits)" value="<?php echo escape_output($_POST['cnic'] ?? ''); ?>" required pattern="\d{13}" maxlength="13">

            <div style="font-size:0.85rem; color:#4b5563; text-align:left; margin-top:4px;">
                Gender:
            </div>
            <div style="display:flex; gap:10px; margin:4px 0 10px; font-size:0.8rem; color:#374151;">
                <label><input type="radio" name="gender" value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'checked' : ''; ?> required> Male</label>
                <label><input type="radio" name="gender" value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'checked' : ''; ?>> Female</label>
                <label><input type="radio" name="gender" value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'checked' : ''; ?>> Other</label>
            </div>

            <button type="button" onclick="nextStep()" style="width: 100%; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-weight: 700; border-radius: 999px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);">Next Step</button>
        </div>

        <div id="step2" style="display: none; flex-direction: column; gap: 0.75rem;">
            <select name="qualification" required>
                <option value="">Latest Qualification</option>
                <option value="Intermediate" <?php echo (($_POST['qualification'] ?? '') === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                <option value="Bachelor" <?php echo (($_POST['qualification'] ?? '') === 'Bachelor') ? 'selected' : ''; ?>>Bachelor</option>
                <option value="Master" <?php echo (($_POST['qualification'] ?? '') === 'Master') ? 'selected' : ''; ?>>Master</option>
                <option value="MPhil" <?php echo (($_POST['qualification'] ?? '') === 'MPhil') ? 'selected' : ''; ?>>MPhil</option>
                <option value="PhD" <?php echo (($_POST['qualification'] ?? '') === 'PhD') ? 'selected' : ''; ?>>PhD</option>
            </select>

            <input type="text" name="degree_field" placeholder="Field of Study (e.g. Biology, Computer Science)" value="<?php echo escape_output($_POST['degree_field'] ?? ''); ?>" required>

            <select name="teaching_level" required>
                <option value="">Select Level of Teaching</option>
                <option value="Primary (Class 1-5)" <?php echo (($_POST['teaching_level'] ?? '') === 'Primary (Class 1-5)') ? 'selected' : ''; ?>>Primary (Class 1-5)</option>
                <option value="Middle (Class 6-8)" <?php echo (($_POST['teaching_level'] ?? '') === 'Middle (Class 6-8)') ? 'selected' : ''; ?>>Middle (Class 6-8)</option>
                <option value="High School / Matric" <?php echo (($_POST['teaching_level'] ?? '') === 'High School / Matric') ? 'selected' : ''; ?>>High School / Matric</option>
                <option value="Intermediate / FSc / A-Levels" <?php echo (($_POST['teaching_level'] ?? '') === 'Intermediate / FSc / A-Levels') ? 'selected' : ''; ?>>Intermediate / FSc / A-Levels</option>
                <option value="Bachelor / University" <?php echo (($_POST['teaching_level'] ?? '') === 'Bachelor / University') ? 'selected' : ''; ?>>Bachelor / University</option>
                <option value="All Levels" <?php echo (($_POST['teaching_level'] ?? '') === 'All Levels') ? 'selected' : ''; ?>>All Levels</option>
            </select>

            <input type="text" name="subjects" placeholder="Subjects to Teach (e.g. Maths, Physics)" value="<?php echo escape_output($_POST['subjects'] ?? ''); ?>" required>

            <input type="number" name="experience" placeholder="Teaching Experience (in years)" min="0" value="<?php echo escape_output($_POST['experience'] ?? ''); ?>" required>

            <div style="font-size:0.8rem; color:#4b5563; text-align:left; margin-top:4px;">
                Upload CV (PDF only)
            </div>
            <input type="file" name="cv" accept="application/pdf" required style="padding: 5px; background: #f9fafb;">

            <div style="font-size:0.8rem; color:#4b5563; text-align:left; margin-top:4px;">
                Profile Photo (square image required)
            </div>
            <input type="file" name="photo" accept="image/*" required style="padding: 5px; background: #f9fafb;">

            <div style="display:flex; gap:8px; margin-top:8px;">
                <button type="button" onclick="prevStep()" style="background:#e5e7eb; color:#111827; box-shadow:none; flex: 1; border-radius: 999px;">Back</button>
                <button type="submit" name="submit" style="flex: 2; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; font-weight: 700; border-radius: 999px; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);">Submit Registration</button>
            </div>
        </div>
    </form>

    <!-- Spinner Overlay -->
    <div id="spinnerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
        <div class="loader"></div>
        <div style="margin-top: 15px; font-weight: 600; color: #10b981;">Sending Welcome Email...</div>
    </div>
    
    <style>
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #10b981;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        // Client-side validation utilities
        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function validateCNIC(cnic) {
            // Require exactly 13 numeric digits
            return /^\d{13}$/.test(cnic);
        }

        function showClientError(msg) {
            const el = document.getElementById('clientError');
            if (!el) return;
            el.style.display = 'block';
            el.innerText = msg;
            window.scrollTo({ top: el.offsetTop - 20, behavior: 'smooth' });
        }

        function clearClientError() {
            const el = document.getElementById('clientError');
            if (!el) return;
            el.style.display = 'none';
            el.innerText = '';
        }

        async function validateForm() {
            clearClientError();
            const fullName = (document.querySelector('input[name="full_name"]').value || '').trim();
            const email = (document.querySelector('input[name="email"]').value || '').trim();
            const password = (document.querySelector('input[name="password"]').value || '');
            const cnic = (document.querySelector('input[name="cnic"]').value || '').trim();
            const gender = document.querySelector('input[name="gender"]:checked');
            const qualification = (document.querySelector('select[name="qualification"]').value || '').trim();
            const degree_field = (document.querySelector('input[name="degree_field"]').value || '').trim();
            const teaching_level = (document.querySelector('select[name="teaching_level"]').value || '').trim();
            const subjects = (document.querySelector('input[name="subjects"]').value || '').trim();
            const experience = parseInt(document.querySelector('input[name="experience"]').value || '0', 10);

            if (fullName.length < 3) { showClientError('Please enter your full name (at least 3 characters).'); return false; }
            if (!validateEmail(email)) { showClientError('Please enter a valid email address.'); return false; }
            if (password.length < 6) { showClientError('Password must be at least 6 characters.'); return false; }
            if (!validateCNIC(cnic)) { showClientError('Please enter a valid CNIC: 13 digits (XXXXXXXXXXXXX) or formatted 12345-1234567-1.'); return false; }
            if (!gender) { showClientError('Please select your gender.'); return false; }
            if (qualification === '') { showClientError('Please select your latest qualification.'); return false; }
            if (degree_field.length < 2) { showClientError('Please enter your field of study.'); return false; }
            if (teaching_level === '') { showClientError('Please select level(s) you will teach.'); return false; }
            if (subjects.length < 2) { showClientError('Please list at least one subject to teach.'); return false; }
            if (isNaN(experience) || experience < 0) { showClientError('Please enter a valid non-negative number for experience.'); return false; }

            // Files
            const cvInput = document.querySelector('input[name="cv"]');
            const photoInput = document.querySelector('input[name="photo"]');
            if (!cvInput || !cvInput.files || cvInput.files.length === 0) { showClientError('Please upload your CV in PDF format.'); return false; }
            if (!photoInput || !photoInput.files || photoInput.files.length === 0) { showClientError('Please upload a profile photo (JPG/PNG/WebP).'); return false; }

            const cvFile = cvInput.files[0];
            if (cvFile.size > 5 * 1024 * 1024) { showClientError('CV file is too large (max 5MB).'); return false; }
            const cvNameLower = (cvFile.name || '').toLowerCase();
            if (!cvNameLower.endsWith('.pdf')) { showClientError('CV must be a PDF file.'); return false; }

            const photoFile = photoInput.files[0];
            if (photoFile.size > 3 * 1024 * 1024) { showClientError('Photo is too large (max 3MB).'); return false; }
            const allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (photoFile.type && !allowedPhotoTypes.includes(photoFile.type)) { showClientError('Profile photo must be JPG, PNG or WebP.'); return false; }

            // Check that the photo is square (read image dimensions)
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
                    reader.readAsDataURL(photoFile);
                } catch (e) { resolve(false); }
            });

            if (!isSquare) { showClientError('Profile photo must be square (width and height equal).'); return false; }

            return true;
        }

        document.getElementById('registrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!(await validateForm())) return;
            document.getElementById('spinnerOverlay').style.display = 'flex';
            // submit after giving visual feedback
            setTimeout(() => { e.target.submit(); }, 200);
        });

        function showSpinnerAndExecute(callback) {
            document.getElementById('spinnerOverlay').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('spinnerOverlay').style.display = 'none';
                callback();
            }, 1000);
        }

        function nextStep() {
            let step1Inputs = document.querySelectorAll('#step1 input, #step1 select');
            let allValid = true;
            for (let input of step1Inputs) {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    allValid = false;
                    break;
                }
            }
            if (allValid) {
                showSpinnerAndExecute(() => {
                    document.getElementById('step1').style.display = 'none';
                    document.getElementById('step2').style.display = 'flex';
                });
            }
        }

        function prevStep() {
            showSpinnerAndExecute(() => {
                document.getElementById('step1').style.display = 'flex';
                document.getElementById('step2').style.display = 'none';
            });
        }
    </script>

    <a href="../login.php">Already have an account? Login</a>
</div>

</body>
</html>