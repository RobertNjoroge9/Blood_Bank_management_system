<?php
require_once '../includes/auth.php';
requireDonor();

// Get donor statistics
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_donations 
    FROM donations 
    WHERE donor_id = ? AND status = 'completed'
");
$stmt->execute([$_SESSION['user_id']]);
$donations = $stmt->fetch();

// Get upcoming appointments
$stmt = $pdo->prepare("
    SELECT d.*, br.hospital_name, br.urgency 
    FROM donations d
    JOIN blood_requests br ON d.request_id = br.id
    WHERE d.donor_id = ? AND d.status = 'scheduled'
    ORDER BY d.donation_date ASC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Donor Dashboard</h2>
        <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Donations</h5>
                <p class="card-text display-4"><?php echo $donations['total_donations']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Upcoming Appointments</h5>
                <p class="card-text display-4"><?php echo count($appointments); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="book-appointment.php" class="btn btn-danger mb-2">Book Appointment</a>
                <a href="profile.php" class="btn btn-secondary mb-2">Update Profile</a>
                <a href="donation-history.php" class="btn btn-info mb-2">View Donation History</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if(count($appointments) > 0): ?>
                    <ul class="list-group">
                        <?php foreach($appointments as $apt): ?>
                        <li class="list-group-item">
                            <strong>Date:</strong> <?php echo $apt['donation_date']; ?><br>
                            <strong>Hospital:</strong> <?php echo $apt['hospital_name']; ?><br>
                            <strong>Urgency:</strong> 
                            <span class="badge bg-<?php echo $apt['urgency'] == 'emergency' ? 'danger' : ($apt['urgency'] == 'urgent' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($apt['urgency']); ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming appointments.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>