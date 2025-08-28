<?php
// includes/auth.php

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect_to_dashboard() {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /user/dashboard.php');
    }
    exit();
}