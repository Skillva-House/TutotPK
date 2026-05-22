<?php
// Simple layout variables
if (!isset($page_title)) {
    $page_title = 'TutorPk';
}
if (!isset($user_name)) {
    $user_name = 'Demo User';
}
if (!isset($role)) {
    $role = 'student';
}

$css_file = 'student/student.css';
if ($role === 'tutor') {
    $css_file = 'tutor/tutor.css';
} elseif ($role === 'admin') {
    $css_file = 'admin/admin.css';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - TutorPk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/tutorpk/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/tutorpk/<?php echo $css_file; ?>?v=<?php echo time(); ?>">
</head>
<body>
<div class="page">
