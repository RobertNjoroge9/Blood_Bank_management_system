<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Management System</title>
    <link rel="stylesheet" href="/bloodbank/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="/bloodbank/index.php">
                <img src="/bloodbank/assets/images/logo.png" alt="Logo" height="30" class="d-inline-block align-text-top">
                Blood Bank System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link text-white">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/bloodbank/<?php echo $_SESSION['user_type']; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/bloodbank/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/bloodbank/index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/bloodbank/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/bloodbank/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">