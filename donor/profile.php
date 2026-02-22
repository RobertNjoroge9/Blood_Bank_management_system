<?php
// donor/profile.php
require_once '../includes/auth.php';
requireDonor();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get donor profile
$stmt = $pdo->prepare("
    SELECT u.*, dp.blood_group, dp.last_donation_date, dp.is_available, 
           dp.weight, dp.age, dp.gender, dp.medical_history
    FROM users u
    LEFT JOIN donor_profiles dp ON u.id = dp.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$donor = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $weight = $_POST['weight'];
        $age = $_POST['age'];
        $gender = $_POST['gender'];
        $medical_history = $_POST['medical_history'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        try {
            $pdo->beginTransaction();
            
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $address, $user_id]);
            
            // Update donor_profiles table
            $stmt = $pdo->prepare("
                UPDATE donor_profiles 
                SET weight = ?, age = ?, gender = ?, medical_history = ?, is_available = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$weight, $age, $gender, $medical_history, $is_available, $user_id]);
            
            $pdo->commit();
            $success = "Profile updated successfully!";
            
            // Refresh donor data
            $stmt = $pdo->prepare("SELECT u.*, dp.* FROM users u LEFT JOIN donor_profiles dp ON u.id = dp.user_id WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $donor = $stmt->fetch();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2><i class="bi bi-person-circle text-danger"></i> Donor Profile</h2>
        <hr>
    </div>
</div>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($donor['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($donor['email']); ?>" readonly disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($donor['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Blood Group</label>
                            <select class="form-control" disabled readonly>
                                <option><?php echo $donor['blood_group'] ?? 'Not set'; ?></option>
                            </select>
                            <small class="text-muted">Blood group cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" name="age" min="18" max="65"
                                   value="<?php echo htmlspecialchars($donor['age'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" name="weight" step="0.1" min="45"
                                   value="<?php echo htmlspecialchars($donor['weight'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-control" name="gender">
                                <option value="">Select</option>
                                <option value="Male" <?php echo ($donor['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($donor['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($donor['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($donor['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Medical History</label>
                        <textarea class="form-control" name="medical_history" rows="3"><?php echo htmlspecialchars($donor['medical_history'] ?? ''); ?></textarea>
                        <small class="text-muted">Include any conditions, medications, or allergies</small>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="is_available" id="is_available" 
                               <?php echo ($donor['is_available'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_available">
                            <i class="bi bi-check-circle text-success"></i> Available to donate blood
                        </label>
                        <small class="d-block text-muted">Uncheck if you're temporarily unavailable</small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-danger">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Summary -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Profile Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle display-1 text-danger"></i>
                    <h4><?php echo htmlspecialchars($donor['full_name']); ?></h4>
                    <span class="badge bg-danger fs-6"><?php echo $donor['blood_group'] ?? 'N/A'; ?></span>
                </div>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-envelope"></i> Email:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($donor['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-telephone"></i> Phone:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($donor['phone'] ?? 'Not set'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-gender-<?php echo strtolower($donor['gender'] ?? 'unknown'); ?>"></i> Gender:</span>
                        <span class="text-muted"><?php echo $donor['gender'] ?? 'Not set'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i bi bi-calendar"></i> Age:</span>
                        <span class="text-muted"><?php echo $donor['age'] ?? 'Not set'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-weight"></i> Weight:</span>
                        <span class="text-muted"><?php echo $donor['weight'] ? $donor['weight'] . ' kg' : 'Not set'; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-clock"></i> Member Since:</span>
                        <span class="text-muted"><?php echo date('d M Y', strtotime($donor['created_at'])); ?></span>
                    </li>
                </ul>
                
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Donation Eligibility Status -->
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-heart"></i> Donation Status</h5>
            </div>
            <div class="card-body">
                <?php
                $last_donation = $donor['last_donation_date'];
                $eligible = true;
                $message = '';
                
                if($last_donation) {
                    $days_since = (time() - strtotime($last_donation)) / (60 * 60 * 24);
                    if($days_since < 56) { // 8 weeks for whole blood
                        $eligible = false;
                        $days_left = 56 - ceil($days_since);
                        $message = "You can donate again in $days_left days";
                    } else {
                        $message = "You are eligible to donate now!";
                    }
                } else {
                    $message = "You are eligible to donate now!";
                }
                ?>
                
                <div class="text-center">
                    <?php if($eligible): ?>
                        <i class="bi bi-check-circle-fill text-success display-4"></i>
                        <h5 class="text-success mt-2">Eligible</h5>
                    <?php else: ?>
                        <i class="bi bi-clock-fill text-warning display-4"></i>
                        <h5 class="text-warning mt-2">On Cooldown</h5>
                    <?php endif; ?>
                    <p><?php echo $message; ?></p>
                    <?php if($last_donation): ?>
                        <small class="text-muted">Last donation: <?php echo date('d M Y', strtotime($last_donation)); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>