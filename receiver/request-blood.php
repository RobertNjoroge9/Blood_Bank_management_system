<?php
// receiver/request-blood.php
require_once '../includes/auth.php';
requireReceiver();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

// Get user's hospital if set
$stmt = $pdo->prepare("SELECT hospital_registered FROM receiver_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$receiver = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_group = $_POST['blood_group'];
    $units_needed = $_POST['units_needed'];
    $hospital_name = $_POST['hospital_name'];
    $hospital_address = $_POST['hospital_address'];
    $request_date = $_POST['request_date'];
    $urgency = $_POST['urgency'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate
    if (empty($blood_group) || empty($units_needed) || empty($hospital_name) || empty($request_date)) {
        $error = "Please fill in all required fields";
    } elseif ($units_needed < 1 || $units_needed > 10) {
        $error = "Units needed must be between 1 and 10";
    } elseif (strtotime($request_date) < strtotime(date('Y-m-d'))) {
        $error = "Request date must be in the future";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blood_requests 
                (receiver_id, blood_group, units_needed, hospital_name, hospital_address, request_date, urgency, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $blood_group, $units_needed, $hospital_name, $hospital_address, $request_date, $urgency, $notes])) {
                $success = "Blood request submitted successfully! Donors will be notified.";
                
                // Clear POST data
                $_POST = array();
            } else {
                $error = "Failed to submit request";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-plus-circle text-primary"></i> Request Blood</h2>
        <p class="lead">Submit a request for blood donation</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
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
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-file-text"></i> Blood Request Form</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="blood_group" class="form-label">Blood Group Needed *</label>
                            <select class="form-select" id="blood_group" name="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <?php foreach($blood_groups as $bg): ?>
                                    <option value="<?php echo $bg; ?>" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; ?>>
                                        <?php echo $bg; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="units_needed" class="form-label">Units Needed (1-10) *</label>
                            <input type="number" class="form-control" id="units_needed" name="units_needed" 
                                   min="1" max="10" value="<?php echo htmlspecialchars($_POST['units_needed'] ?? '1'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hospital_name" class="form-label">Hospital Name *</label>
                        <input type="text" class="form-control" id="hospital_name" name="hospital_name" 
                               value="<?php echo htmlspecialchars($_POST['hospital_name'] ?? $receiver['hospital_registered'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hospital_address" class="form-label">Hospital Address</label>
                        <textarea class="form-control" id="hospital_address" name="hospital_address" rows="2"><?php echo htmlspecialchars($_POST['hospital_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="request_date" class="form-label">Required By Date *</label>
                            <input type="date" class="form-control" id="request_date" name="request_date" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo htmlspecialchars($_POST['request_date'] ?? date('Y-m-d', strtotime('+7 days'))); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="urgency" class="form-label">Urgency Level *</label>
                            <select class="form-select" id="urgency" name="urgency" required>
                                <option value="normal" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                                <option value="urgent" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                <option value="emergency" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'emergency') ? 'selected' : ''; ?>>Emergency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any additional information for donors..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Note:</strong> Your request will be visible to all compatible blood donors. You'll be notified when a donor accepts.
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    var units = document.getElementById('units_needed').value;
    var date = document.getElementById('request_date').value;
    
    if (units < 1 || units > 10) {
        alert('Units needed must be between 1 and 10');
        return false;
    }
    
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>