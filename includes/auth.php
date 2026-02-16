<?php
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isDonor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'donor';
}

function isReceiver() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'receiver';
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /bloodbank/login.php');
        exit();
    }
}

function requireDonor() {
    requireLogin();
    if (!isDonor()) {
        header('Location: /bloodbank/index.php');
        exit();
    }
}

function requireReceiver() {
    requireLogin();
    if (!isReceiver()) {
        header('Location: /bloodbank/index.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /bloodbank/index.php');
        exit();
    }
}
?>