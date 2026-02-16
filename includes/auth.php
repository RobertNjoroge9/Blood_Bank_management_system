<?php
// includes/auth.php
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
        header('Location: ' . BASE_PATH . 'login.php');
        exit();
    }
}

function requireDonor() {
    requireLogin();
    if (!isDonor()) {
        header('Location: ' . BASE_PATH . 'index.php');
        exit();
    }
}

function requireReceiver() {
    requireLogin();
    if (!isReceiver()) {
        header('Location: ' . BASE_PATH . 'index.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . 'index.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>