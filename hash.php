<?php
include "connect.php";

$hash = password_hash("123456", PASSWORD_DEFAULT);

mysqli_query($conn, "UPDATE users SET password='$hash' WHERE email='admin@gmail.com'");

echo "admin password updated!";
?>