<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Manage Profile';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor';
$active_page = 'manage_profile';
$tutor_id    = (int) $_SESSION['id'];
$success     = '';
$error       = '';

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch current tutor data
$stmt = $conn->prepare("SELECT name, email, qualification, teaching_level, subjects, photo_file, bio FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile save
if (isset($_POST['save'])) {
    $subjects       = clean_input($_POST['subjects'] ?? '');
    $bio            = clean_input($_POST['bio'] ?? '');
    $qualification  = clean_input($_POST['qualification'] ?? '');
    $teaching_level = clean_input($_POST['teaching_level'] ?? '');
    $photo_file    = $user['photo_file'];

    // CSRF token verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Invalid request (missing CSRF token).';
    }

    // Validate teaching level against allowlist
    $allowed_levels = [
        'Primary (Class 1-5)', 'Middle (Class 6-8)', 'High School / Matric',
        'Intermediate / FSc / A-Levels', 'Bachelor / University', 'All Levels'
    ];
    if ($error === '' && !in_array($teaching_level, $allowed_levels, true)) {
        $error = 'Invalid teaching level selected.';
    }

    // Validate bio length
    if ($error === '' && !validate_length($bio, 1, 500)) {
        $error = 'Bio must be between 1 and 500 characters.';
    }

    // Validate subjects (comma separated tokens)
    if ($error === '') {
        $sub_tokens = array_filter(array_map('trim', explode(',', $subjects)));
        if (count($sub_tokens) === 0) {
            $error = 'Please provide at least one subject.';
        } else {
            foreach ($sub_tokens as $tok) {
                if (strlen($tok) > 50 || !preg_match('/^[a-zA-Z0-9\s&+\-]+$/', $tok)) {
                    $error = 'Each subject should be alphanumeric (max 50 chars).';
                    break;
                }
            }
        }
    }

    // Handle photo upload using centralized helper with MIME/size checks
    if ($error === '' && isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        // Enforce square photo (equal width and height)
        $img_info = @getimagesize($_FILES['photo_file']['tmp_name']);
        if ($img_info === false) {
            $error = 'Invalid image file.';
        } else {
            $img_w = (int)($img_info[0] ?? 0);
            $img_h = (int)($img_info[1] ?? 0);
            if ($img_w <= 0 || $img_h <= 0 || $img_w !== $img_h) {
                $error = 'Profile photo must be square (equal width and height).';
            }
        }
    }

    if ($error === '' && isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_err = '';
        $saved_name = save_uploaded_file(
            $_FILES['photo_file'],
            __DIR__ . '/../uploads',
            ['jpg', 'jpeg', 'png', 'webp'],
            5 * 1024 * 1024,
            $upload_err
        );

        if ($saved_name === '') {
            $error = 'Photo upload error: ' . $upload_err;
        } else {
            $new_rel = 'uploads/' . $saved_name;
            // remove old file if different and owned by user
            if (!empty($user['photo_file']) && $user['photo_file'] !== $new_rel) {
                $old_path = __DIR__ . '/../' . $user['photo_file'];
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }
            $photo_file = $new_rel;
        }
    }

    if ($error === '') {
        $upd = $conn->prepare("UPDATE users SET subjects = ?, bio = ?, qualification = ?, teaching_level = ?, photo_file = ? WHERE id = ?");
        $upd->bind_param('sssssi', $subjects, $bio, $qualification, $teaching_level, $photo_file, $tutor_id);
        if ($upd->execute()) {
            $success               = 'Profile updated successfully!';
            $user['subjects']      = $subjects;
            $user['bio']            = $bio;
            $user['qualification']  = $qualification;
            $user['teaching_level'] = $teaching_level;
            $user['photo_file']     = $photo_file;
        } else {
            $error = 'Database update failed.';
        }
        $upd->close();
    }
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

<link rel="stylesheet" href="tutor.css?v=<?php echo time(); ?>">

<div class="mp-main-container">
    <div class="mp-header">
        <h1>Manage Your Profile</h1>
        <p>Keep your profile up-to-date to attract more students</p>
    </div>

    <?php if ($success !== ''): ?>
        <div class="mp-alert mp-alert-success">
            <span class="mp-alert-icon">✓</span>
            <span><?php echo escape_output($success); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="mp-alert mp-alert-error">
            <span class="mp-alert-icon">⚠</span>
            <span><?php echo escape_output($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mp-form">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <!-- Profile Photo Section -->
        <div class="mp-card">
            <div class="mp-card-title">
                <span class="mp-card-icon">📸</span>
                <h2>Profile Photo</h2>
            </div>
            <div class="mp-photo-section">
                <div class="mp-photo-preview-wrap">
                    <?php if (!empty($user['photo_file'])): ?>
                        <img src="/tutorpk/<?php echo escape_output($user['photo_file']); ?>" class="mp-photo-preview" id="photoPreview">
                    <?php else: ?>
                        <div class="mp-photo-placeholder" id="photoPreview">
                            📸
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mp-photo-upload">
                    <label for="photo_input" class="mp-upload-btn">
                        <span>📁 Choose Photo</span>
                    </label>
                    <input type="file" id="photo_input" name="photo_file" accept="image/*" class="mp-file-input">
                    <p class="mp-upload-hint">JPG, PNG, or WebP (Max 5MB, square image required)</p>
                </div>
            </div>
        </div>

        <!-- Account Info Section (Read-only) -->>

        <!-- Teaching Info Section (Editable) -->
        <div class="mp-card">
            <div class="mp-card-title">
                <span class="mp-card-icon">📚</span>
                <h2>Teaching Information</h2>
            </div>

            <div class="mp-form-group">
                <label>Qualification</label>
                <input type="text" class="mp-input" value="<?php echo escape_output($user['qualification']); ?>" name="qualification" required>
            </div>

            <div class="mp-form-group">
                <label>Teaching Level</label>
                <select name="teaching_level" required class="mp-input">
                    <option value="">Select Level of Teaching</option>
                    <option value="Primary (Class 1-5)" <?php echo (($user['teaching_level'] ?? '') === 'Primary (Class 1-5)') ? 'selected' : ''; ?>>Primary (Class 1-5)</option>
                    <option value="Middle (Class 6-8)" <?php echo (($user['teaching_level'] ?? '') === 'Middle (Class 6-8)') ? 'selected' : ''; ?>>Middle (Class 6-8)</option>
                    <option value="High School / Matric" <?php echo (($user['teaching_level'] ?? '') === 'High School / Matric') ? 'selected' : ''; ?>>High School / Matric</option>
                    <option value="Intermediate / FSc / A-Levels" <?php echo (($user['teaching_level'] ?? '') === 'Intermediate / FSc / A-Levels') ? 'selected' : ''; ?>>Intermediate / FSc / A-Levels</option>
                    <option value="Bachelor / University" <?php echo (($user['teaching_level'] ?? '') === 'Bachelor / University') ? 'selected' : ''; ?>>Bachelor / University</option>
                    <option value="All Levels" <?php echo (($user['teaching_level'] ?? '') === 'All Levels') ? 'selected' : ''; ?>>All Levels</option>
                </select>
            </div>

            <div class="mp-form-group">
                <label>Subjects You Teach <span class="mp-required">*</span></label>
                <input type="text" name="subjects" value="<?php echo escape_output($user['subjects']); ?>" class="mp-input" placeholder="e.g., Math, Physics, Chemistry (comma-separated)" required>
                <small class="mp-field-hint">Separate multiple subjects with commas</small>
            </div>

            <div class="mp-form-group">
                <label>Bio <span class="mp-required">*</span></label>
                <textarea name="bio" class="mp-textarea" placeholder="Tell students about your expertise, teaching style, and experience..." required><?php echo escape_output($user['bio']); ?></textarea>
                <small class="mp-field-hint">Write a compelling bio to attract more students (500 characters max)</small>
            </div>
        </div>

        <!-- Action Button -->
        <div class="mp-footer">
            <button type="submit" name="save" class="mp-btn-save">
                <span>💾</span> Save Changes
            </button>
        </div>
    </form>
</div>

<script>
// Photo preview functionality
const fileInput = document.getElementById('photo_input');
const photoPreview = document.getElementById('photoPreview');

fileInput.addEventListener('change', function (event) {
    const file = event.target.files[0];
    if (file) {
        fileInput.setCustomValidity('');

        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            fileInput.setCustomValidity('File size must be less than 5MB');
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.onload = function () {
                if (img.naturalWidth !== img.naturalHeight) {
                    alert('Profile photo must be square (equal width and height).');
                    fileInput.setCustomValidity('Profile photo must be square (equal width and height).');
                    fileInput.value = '';
                    return;
                }

                fileInput.setCustomValidity('');
                photoPreview.innerHTML = '';
                img.className = 'mp-photo-preview';
                photoPreview.appendChild(img);
            };
            img.onerror = function () {
                alert('Invalid image file.');
                fileInput.setCustomValidity('Invalid image file.');
                fileInput.value = '';
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Prevent form submission if no subjects or bio
document.querySelector('.mp-form').addEventListener('submit', function(e) {
    const subjects = document.querySelector('input[name="subjects"]').value.trim();
    const bio = document.querySelector('textarea[name="bio"]').value.trim();
    
    if (!subjects) {
        e.preventDefault();
        alert('Please enter the subjects you teach');
        return;
    }
    
    if (!bio) {
        e.preventDefault();
        alert('Please write a bio');
        return;
    }

    if (!fileInput.checkValidity()) {
        e.preventDefault();
        fileInput.reportValidity();
        return;
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

