<?php
// donor/my-appointments.php
require_once '../includes/auth.php';
requireDonor();

$user_id = $_SESSION['user_id'];

// Get all appointments
$stmt = $pdo->prepare("
    SELECT d.*, br.hospital_name, br.blood_group, br.urgency,
           u.full_name as receiver_name, u.phone as receiver_phone,
           br.hospital_address
    FROM donations d
    JOIN blood_requests br ON d.request_id = br.id
    JOIN users u ON br.receiver_id = u.id
    WHERE d.donor_id = ?
    ORDER BY 
        CASE d.status
            WHEN 'scheduled' THEN 1
            WHEN 'completed' THEN 2
            ELSE 3
        END,
        d.donation_date DESC
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment'])) {
    $donation_id = $_POST['donation_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get request_id before updating
        $stmt = $pdo->prepare("SELECT request_id FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);
        $donation = $stmt->fetch();
        
        // Update donation status
        $stmt = $pdo->prepare("UPDATE donations SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$donation_id]);
        
        // Update request status back to pending
        $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'pending' WHERE id = ?");
        $stmt->execute([$donation['request_id']]);
        
        $pdo->commit();
        $success = "Appointment cancelled successfully";
        
        // Refresh page
        header("Refresh:2");
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Cancellation failed: " . $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-calendar-check text-danger"></i> My Appointments</h2>
        <p class="lead">View and manage your donation appointments</p>
    </div>
</div>

<?php if(isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> All Appointments</h5>
            </div>
            <div class="card-body">
                <?php if(count($appointments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Hospital</th>
                                    <th>Patient</th>
                                    <th>Blood Group</th>
                                    <th>Status</th>
                                    <th>Urgency</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($appointments as $apt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d M Y', strtotime($apt['donation_date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('l', strtotime($apt['donation_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($apt['hospital_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($apt['hospital_address'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($apt['receiver_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($apt['receiver_phone'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $apt['blood_group']; ?></span>
                                    </td>
                                    <td>
                                        <?php if($apt['status'] == 'scheduled'): ?>
                                            <span class="badge bg-warning text-dark">Scheduled</span>
                                        <?php elseif($apt['status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif($apt['status'] == 'cancelled'): ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($apt['urgency'] == 'emergency'): ?>
                                            <span class="badge bg-danger">Emergency</span>
                                        <?php elseif($apt['urgency'] == 'urgent'): ?>
                                            <span class="badge bg-warning text-dark">Urgent</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($apt['status'] == 'scheduled'): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmCancellation(<?php echo $apt['id']; ?>)">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        <?php elseif($apt['status'] == 'completed'): ?>
                                            <button class="btn btn-sm btn-success" disabled>
                                                <i class="bi bi-check-circle"></i> Done
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-1 text-muted"></i>
                        <h5 class="mt-3">No appointments found</h5>
                        <p class="text-muted">You haven't scheduled any donation appointments yet</p>
                        <a href="book-appointment.php" class="btn btn-danger">
                            <i class="bi bi-calendar-plus"></i> Book Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Cancellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this appointment?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="" id="cancelForm">
                    <input type="hidden" name="donation_id" id="cancel_donation_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="submit" name="cancel_appointment" class="btn btn-danger">
                        Yes, Cancel Appointment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCancellation(donationId) {
    document.getElementById('cancel_donation_id').value = donationId;
    var modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>