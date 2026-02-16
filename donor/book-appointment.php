<?php
require_once '../includes/auth.php';
requireDonor();

// Get available blood requests
$stmt = $pdo->prepare("
    SELECT br.*, u.full_name as receiver_name, u.phone as receiver_phone
    FROM blood_requests br
    JOIN users u ON br.receiver_id = u.id
    WHERE br.status = 'pending'
    ORDER BY 
        CASE br.urgency 
            WHEN 'emergency' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END,
        br.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $request_id = $_POST['request_id'];
    $donation_date = $_POST['donation_date'];
    
    $stmt = $pdo->prepare("INSERT INTO donations (donor_id, request_id, donation_date, status) VALUES (?, ?, ?, 'scheduled')");
    if ($stmt->execute([$_SESSION['user_id'], $request_id, $donation_date])) {
        // Update request status
        $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'matched' WHERE id = ?");
        $stmt->execute([$request_id]);
        
        $success = "Appointment booked successfully!";
    } else {
        $error = "Failed to book appointment.";
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Book Donation Appointment</h2>
    </div>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Available Blood Requests</h5>
            </div>
            <div class="card-body">
                <?php if(count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Blood Group</th>
                                    <th>Units Needed</th>
                                    <th>Hospital</th>
                                    <th>Urgency</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requests as $request): ?>
                                <tr>
                                    <td><?php echo $request['receiver_name']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $request['blood_group']; ?></span></td>
                                    <td><?php echo $request['units_needed']; ?></td>
                                    <td><?php echo $request['hospital_name']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $request['urgency'] == 'emergency' ? 'danger' : ($request['urgency'] == 'urgent' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($request['urgency']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bookModal<?php echo $request['id']; ?>">
                                            Book Appointment
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Booking Modal -->
                                <div class="modal fade" id="bookModal<?php echo $request['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Book Appointment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Patient: <?php echo $request['receiver_name']; ?></label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Hospital: <?php echo $request['hospital_name']; ?></label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="donation_date" class="form-label">Preferred Donation Date</label>
                                                        <input type="date" class="form-control" id="donation_date" name="donation_date" min="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="book_appointment" class="btn btn-danger">Book Appointment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No blood requests available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>