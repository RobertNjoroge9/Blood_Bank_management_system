<?php
// config/database.php
$db_host = 'localhost';
$db_name = 'msingico_bloodbank';        // Your cPanel database name
$db_user = 'msingico_bloodbank_user';    // Your cPanel username
$db_pass = 'msingico_bloodbank_user';         // Your cPanel password

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define base path - Since files are in public_html root
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/');  // Just '/' for root directory
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>