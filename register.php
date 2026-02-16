<?php
// register.php
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . $_SESSION['user_type'] . '/dashboard.php');
    exit();
}

$user_type = isset($_GET['type']) && in_array($_GET['type'], ['donor', 'receiver']) ? $_GET['type'] : 'donor';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $user_type = $_POST['user_type'] ?? 'donor';
    
    // Validation
    if (empty($username) || empty($password) || empty($email) || empty($full_name)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Username or email already exists";
            } else {
                $pdo->beginTransaction();
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert into users table
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $full_name, $phone, $address, $user_type]);
                $user_id = $pdo->lastInsertId();
                
                // Insert into role-specific table
                if ($user_type == 'donor') {
                    $blood_group = $_POST['blood_group'] ?? '';
                    if (empty($blood_group)) {
                        throw new Exception("Please select blood group");
                    }
                    $stmt = $pdo->prepare("INSERT INTO donor_profiles (user_id, blood_group) VALUES (?, ?)");
                    $stmt->execute([$user_id, $blood_group]);
                } else if ($user_type == 'receiver') {
                    $medical_condition = $_POST['medical_condition'] ?? '';
                    $emergency_contact = $_POST['emergency_contact'] ?? '';
                    $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
                    $hospital_registered = $_POST['hospital_registered'] ?? '';
                    
                    $stmt = $pdo->prepare("INSERT INTO receiver_profiles (user_id, medical_condition, emergency_contact, emergency_contact_name, hospital_registered) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $medical_condition, $emergency_contact, $emergency_contact_name, $hospital_registered]);
                }
                
                $pdo->commit();
                $success = "Registration successful! You can now login.";
                
                // Clear POST data
                $_POST = array();
                
            }
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}

// Get blood groups for dropdown
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-8">
        <div class="card shadow-lg border-0 rounded-lg mt-4">
            <div class="card-header bg-danger text-white text-center py-3">
                <h3 class="mb-0"><i class="bi bi-person-plus"></i> Register as <?php echo ucfirst($user_type); ?></h3>
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
                
                <!-- User Type Tabs -->
                <ul class="nav nav-pills nav-justified mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $user_type == 'donor' ? 'active bg-danger' : 'text-danger'; ?>" 
                           href="<?php echo BASE_PATH; ?>register.php?type=donor">
                            <i class="bi bi-heart"></i> Register as Donor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $user_type == 'receiver' ? 'active bg-primary' : 'text-primary'; ?>" 
                           href="<?php echo BASE_PATH; ?>register.php?type=receiver">
                            <i class="bi bi-person-plus"></i> Register as Receiver
                        </a>
                    </li>
                </ul>
                
                <form method="POST" action="<?php echo BASE_PATH; ?>register.php" onsubmit="return validateForm()">
                    <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <?php if($user_type == 'donor'): ?>
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group *</label>
                        <select class="form-control" id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <?php foreach($blood_groups as $bg): ?>
                                <option value="<?php echo $bg; ?>" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; ?>>
                                    <?php echo $bg; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php elseif($user_type == 'receiver'): ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="medical_condition" class="form-label">Medical Condition</label>
                            <input type="text" class="form-control" id="medical_condition" name="medical_condition"
                                   value="<?php echo htmlspecialchars($_POST['medical_condition'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hospital_registered" class="form-label">Registered Hospital</label>
                            <input type="text" class="form-control" id="hospital_registered" name="hospital_registered"
                                   value="<?php echo htmlspecialchars($_POST['hospital_registered'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name"
                                   value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact" class="form-label">Emergency Contact Phone</label>
                            <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact"
                                   value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-<?php echo $user_type == 'donor' ? 'danger' : 'primary'; ?> btn-lg">
                            <i class="bi bi-person-plus"></i> Register as <?php echo ucfirst($user_type); ?>
                        </button>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p>Already have an account? <a href="<?php echo BASE_PATH; ?>login.php" class="text-danger">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password != confirm) {
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>