<?php
session_start();
$page_title  = 'Subscription / Payment';
$role        = 'student';
$user_name   = $_SESSION['name'] ?? 'Student';
$active_page = 'subscription';

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

    <div class="card">
        <h1>Subscription / Payment</h1>
        <p>Plans, billing details, and payment history will be rendered here from the backend.</p>
    </div>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>

