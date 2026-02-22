<?php
// receiver/profile.php
require_once '../includes/auth.php';
requireReceiver();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get receiver profile
$stmt = $pdo->prepare("
    SELECT u.*, rp.medical_condition, rp.emergency_contact, 
           rp.emergency_contact_name, rp.hospital_registered
    FROM users u
    LEFT JOIN receiver_profiles rp ON u.id = rp.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$receiver = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $medical_condition = $_POST['medical_condition'];
        $emergency_contact = $_POST['emergency_contact'];
        $emergency_contact_name = $_POST['emergency_contact_name'];
        $hospital_registered = $_POST['hospital_registered'];
        
        try {
            $pdo->beginTransaction();
            
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $address, $user_id]);
            
            // Update receiver_profiles table
            $stmt = $pdo->prepare("
                UPDATE receiver_profiles 
                SET medical_condition = ?, emergency_contact = ?, 
                    emergency_contact_name = ?, hospital_registered = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$medical_condition, $emergency_contact, $emergency_contact_name, $hospital_registered, $user_id]);
            
            $pdo->commit();
            $success = "Profile updated successfully!";
            
            // Refresh receiver data
            $stmt = $pdo->prepare("
                SELECT u.*, rp.* 
                FROM users u 
                LEFT JOIN receiver_profiles rp ON u.id = rp.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $receiver = $stmt->fetch();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Update failed: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2><i class="bi bi-person-circle text-primary"></i> Receiver Profile</h2>
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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($receiver['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($receiver['email']); ?>" readonly disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($receiver['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($receiver['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Medical Condition</label>
                        <input type="text" class="form-control" name="medical_condition" 
                               value="<?php echo htmlspecialchars($receiver['medical_condition'] ?? ''); ?>">
                        <small class="text-muted">Describe your medical condition requiring blood transfusion</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Registered Hospital</label>
                        <input type="text" class="form-control" name="hospital_registered" 
                               value="<?php echo htmlspecialchars($receiver['hospital_registered'] ?? ''); ?>">
                    </div>
                    
                    <h5 class="mt-4 mb-3">Emergency Contact Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Emergency Contact Name</label>
                            <input type="text" class="form-control" name="emergency_contact_name" 
                                   value="<?php echo htmlspecialchars($receiver['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Emergency Contact Phone</label>
                            <input type="tel" class="form-control" name="emergency_contact" 
                                   value="<?php echo htmlspecialchars($receiver['emergency_contact'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Summary -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Profile Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle display-1 text-primary"></i>
                    <h4><?php echo htmlspecialchars($receiver['full_name']); ?></h4>
                </div>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-envelope"></i> Email:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($receiver['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-telephone"></i> Phone:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($receiver['phone'] ?? 'Not set'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-hospital"></i> Hospital:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($receiver['hospital_registered'] ?? 'Not set'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-clock"></i> Member Since:</span>
                        <span class="text-muted"><?php echo date('d M Y', strtotime($receiver['created_at'])); ?></span>
                    </li>
                </ul>
                
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Emergency Contact Card -->
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Emergency Contact</h5>
            </div>
            <div class="card-body">
                <?php if(!empty($receiver['emergency_contact_name'])): ?>
                    <p>
                        <strong>Name:</strong> <?php echo htmlspecialchars($receiver['emergency_contact_name']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($receiver['emergency_contact']); ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted">No emergency contact set</p>
                <?php endif; ?>
                <small class="text-muted">This information helps hospitals contact your family in emergencies</small>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>