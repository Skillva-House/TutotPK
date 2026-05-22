<?php

session_start();

// check if user logged in
function check_login()
{
    if(!isset($_SESSION['id']))
    {
        header("location:login.php");
        exit();
    }
}

// logout user
function logout_user()
{
    session_unset();
    session_destroy();

    header("location:login.php");
    exit();
}

?>