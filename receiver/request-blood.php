<?php
require_once '../includes/auth.php';
requireReceiver();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $blood_group = $_POST['blood_group'];
    $units_needed = $_POST['units_needed'];
    $hospital_name = $_POST['hospital_name'];
    $hospital_address = $_POST['hospital_address'];
    $request_date = $_POST['request_date'];
    $urgency = $_POST['urgency'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("
        INSERT INTO blood_requests (receiver_id, blood_group, units_needed, hospital_name, hospital_address, request_date, urgency, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$_SESSION['user_id'], $blood_group, $units_needed, $hospital_name, $hospital_address, $request_date, $urgency, $notes])) {
        $success = "Blood request submitted successfully!";
    } else {
        $error = "Failed to submit request.";
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Request Blood</h2>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Blood Request Form</h5>
            </div>
            <div class="card-body">
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="blood_group" class="form-label">Blood Group Needed</label>
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
                        <div class="col-md-6 mb-3">
                            <label for="units_needed" class="form-label">Units Needed</label>
                            <input type="number" class="form-control" id="units_needed" name="units_needed" min="1" max="10" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hospital_name" class="form-label">Hospital Name</label>
                        <input type="text" class="form-control" id="hospital_name" name="hospital_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hospital_address" class="form-label">Hospital Address</label>
                        <textarea class="form-control" id="hospital_address" name="hospital_address" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="request_date" class="form-label">Required By Date</label>
                            <input type="date" class="form-control" id="request_date" name="request_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="urgency" class="form-label">Urgency Level</label>
                            <select class="form-control" id="urgency" name="urgency" required>
                                <option value="normal">Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">Submit Request</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>