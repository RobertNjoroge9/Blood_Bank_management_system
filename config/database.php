<?php
// For cPanel hosting - REPLACE with your actual credentials
$db_host = 'localhost';
$db_name = 'msingico_bloodbank';        // Your database name with prefix
$db_user = 'msingico_bloodbank_user';    // Your database username with prefix
$db_pass = 'msingico_bloodbank';       // The password you set in cPanel

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Test connection silently
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    // Show user-friendly error
    die("Database connection failed. Please check your credentials.");
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>