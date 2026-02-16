<?php
require_once 'config/database.php';

$user_type = isset($_GET['type']) ? $_GET['type'] : 'donor';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $user_type = $_POST['user_type'];
    
    try {
        $pdo->beginTransaction();
        
        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, address, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $full_name, $phone, $address, $user_type]);
        $user_id = $pdo->lastInsertId();
        
        // Insert into role-specific table
        if ($user_type == 'donor') {
            $blood_group = $_POST['blood_group'];
            $stmt = $pdo->prepare("INSERT INTO donor_profiles (user_id, blood_group) VALUES (?, ?)");
            $stmt->execute([$user_id, $blood_group]);
        } else if ($user_type == 'receiver') {
            $medical_condition = $_POST['medical_condition'];
            $emergency_contact = $_POST['emergency_contact'];
            $stmt = $pdo->prepare("INSERT INTO receiver_profiles (user_id, medical_condition, emergency_contact) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $medical_condition, $emergency_contact]);
        }
        
        $pdo->commit();
        $success = "Registration successful! You can now login.";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Register as <?php echo ucfirst($user_type); ?></h4>
            </div>
            <div class="card-body">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" onsubmit="return validateForm()">
                    <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <?php if($user_type == 'donor'): ?>
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-control" id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <?php elseif($user_type == 'receiver'): ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="medical_condition" class="form-label">Medical Condition</label>
                            <input type="text" class="form-control" id="medical_condition" name="medical_condition">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact" class="form-label">Emergency Contact</label>
                            <input type="tel" class="form-control" id="emergency_contact" name="emergency_contact">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-danger">Register</button>
                    <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
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
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>