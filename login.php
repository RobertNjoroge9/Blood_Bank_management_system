<?php
// login.php
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                $success = "Login successful! Redirecting...";
                
                // Redirect based on user type
                switch($user['user_type']) {
                    case 'donor':
                        $redirect = BASE_PATH . 'donor/dashboard.php';
                        break;
                    case 'receiver':
                        $redirect = BASE_PATH . 'receiver/dashboard.php';
                        break;
                    case 'admin':
                        $redirect = BASE_PATH . 'admin/dashboard.php';
                        break;
                    default:
                        $redirect = BASE_PATH . 'index.php';
                }
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirect';
                    }, 1500);
                </script>";
                
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 rounded-lg mt-5">
            <div class="card-header bg-danger text-white text-center py-4">
                <h3 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Login</h3>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo BASE_PATH; ?>login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person"></i> Username or Email
                        </label>
                        <input type="text" class="form-control form-control-lg" 
                               id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               placeholder="Enter your username or email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control form-control-lg" 
                               id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-2">Don't have an account?</p>
                    <div class="row">
                        <div class="col-6">
                            <a href="<?php echo BASE_PATH; ?>register.php?type=donor" class="btn btn-outline-danger w-100">
                                <i class="bi bi-heart"></i> Register as Donor
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_PATH; ?>register.php?type=receiver" class="btn btn-outline-primary w-100">
                                <i class="bi bi-person-plus"></i> Register as Receiver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>