<?php
// donor/dashboard.php
require_once '../includes/auth.php';
requireDonor(); // Only donors can access

$user_id = $_SESSION['user_id'];

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

// Get donation statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_donations,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_donations,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as upcoming_donations
    FROM donations 
    WHERE donor_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get upcoming appointments
$stmt = $pdo->prepare("
    SELECT d.*, br.hospital_name, br.blood_group, br.urgency,
           u.full_name as receiver_name
    FROM donations d
    JOIN blood_requests br ON d.request_id = br.id
    JOIN users u ON br.receiver_id = u.id
    WHERE d.donor_id = ? AND d.status = 'scheduled'
    ORDER BY d.donation_date ASC
    LIMIT 5
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();

// Get recent messages
$stmt = $pdo->prepare("
    SELECT m.*, u.full_name as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? 
    ORDER BY m.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-heart text-danger"></i> Donor Dashboard</h2>
            <div>
                <span class="badge bg-<?php echo ($donor['is_available'] ?? 1) ? 'success' : 'secondary'; ?> p-2">
                    <i class="bi bi-circle-fill"></i> 
                    <?php echo ($donor['is_available'] ?? 1) ? 'Available to Donate' : 'Currently Unavailable'; ?>
                </span>
            </div>
        </div>
        <p class="lead">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>! Your generosity saves lives.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Blood Group</h6>
                        <h2 class="mb-0"><?php echo $donor['blood_group'] ?? 'Not Set'; ?></h2>
                    </div>
                    <i class="bi bi-droplet fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Donations</h6>
                        <h2 class="mb-0"><?php echo $stats['completed_donations'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-graph-up fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Upcoming</h6>
                        <h2 class="mb-0"><?php echo $stats['upcoming_donations'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-calendar-check fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Last Donation</h6>
                        <h2 class="mb-0 fs-4">
                            <?php 
                            if($donor['last_donation_date']) {
                                echo date('d M Y', strtotime($donor['last_donation_date']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </h2>
                    </div>
                    <i class="bi bi-clock-history fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="book-appointment.php" class="btn btn-outline-danger">
                        <i class="bi bi-calendar-plus"></i> Book Appointment
                    </a>
                    <a href="my-appointments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar-check"></i> My Appointments
                    </a>
                    <a href="donation-history.php" class="btn btn-outline-info">
                        <i class="bi bi-clock-history"></i> Donation History
                    </a>
                    <a href="profile.php" class="btn btn-outline-primary">
                        <i class="bi bi-person"></i> Update Profile
                    </a>
                    <a href="messages.php" class="btn btn-outline-success">
                        <i class="bi bi-envelope"></i> Messages 
                        <span class="badge bg-danger"><?php echo count($messages); ?></span>
                    </a>
                    <a href="../receiver/search-donors.php" class="btn btn-outline-warning">
                        <i class="bi bi-search"></i> Find Donors
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Appointments -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-calendar"></i> Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if(count($appointments) > 0): ?>
                    <div class="list-group">
                        <?php foreach($appointments as $apt): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($apt['hospital_name']); ?></h6>
                                    <small class="text-<?php echo $apt['urgency'] == 'emergency' ? 'danger' : 'secondary'; ?>">
                                        <?php echo ucfirst($apt['urgency']); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <i class="bi bi-droplet"></i> Blood: <?php echo $apt['blood_group']; ?><br>
                                    <i class="bi bi-calendar"></i> Date: <?php echo date('d M Y', strtotime($apt['donation_date'])); ?>
                                </p>
                                <small class="text-muted">For: <?php echo htmlspecialchars($apt['receiver_name']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-calendar-x display-6 d-block"></i>
                        No upcoming appointments
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Messages -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Recent Messages</h5>
            </div>
            <div class="card-body">
                <?php if(count($messages) > 0): ?>
                    <div class="list-group">
                        <?php foreach($messages as $msg): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($msg['sender_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d M', strtotime($msg['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo substr(htmlspecialchars($msg['message']), 0, 50); ?>...</p>
                                <?php if(!$msg['is_read']): ?>
                                    <span class="badge bg-danger">New</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-envelope-open display-6 d-block"></i>
                        No messages yet
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Blood Donation Tips -->
<div class="row">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-body">
                <h5><i class="bi bi-info-circle text-danger"></i> Donation Tips</h5>
                <div class="row">
                    <div class="col-md-3">
                        <small><i class="bi bi-check-circle text-success"></i> Stay hydrated</small>
                    </div>
                    <div class="col-md-3">
                        <small><i class="bi bi-check-circle text-success"></i> Eat iron-rich foods</small>
                    </div>
                    <div class="col-md-3">
                        <small><i class="bi bi-check-circle text-success"></i> Get good sleep</small>
                    </div>
                    <div class="col-md-3">
                        <small><i class="bi bi-check-circle text-success"></i> Avoid alcohol</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>