<?php
// receiver/dashboard.php
require_once '../includes/auth.php';
requireReceiver();

$user_id = $_SESSION['user_id'];

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

// Get blood requests statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_requests,
        SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_requests
    FROM blood_requests 
    WHERE receiver_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get recent blood requests
$stmt = $pdo->prepare("
    SELECT * FROM blood_requests 
    WHERE receiver_id = ? 
    ORDER BY 
        CASE urgency 
            WHEN 'emergency' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END,
        created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_requests = $stmt->fetchAll();

// Get matched donations
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name as donor_name, u.phone as donor_phone,
           dp.blood_group
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    JOIN donor_profiles dp ON u.id = dp.user_id
    JOIN blood_requests br ON d.request_id = br.id
    WHERE br.receiver_id = ? AND d.status = 'scheduled'
    ORDER BY d.donation_date ASC
");
$stmt->execute([$user_id]);
$matched_donations = $stmt->fetchAll();

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
        <h2><i class="bi bi-person-badge text-primary"></i> Receiver Dashboard</h2>
        <p class="lead">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>! We're here to help you find blood donors.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Requests</h6>
                        <h2 class="mb-0"><?php echo $stats['total_requests'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-file-text fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Pending</h6>
                        <h2 class="mb-0"><?php echo $stats['pending_requests'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-hourglass fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Matched</h6>
                        <h2 class="mb-0"><?php echo $stats['matched_requests'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Fulfilled</h6>
                        <h2 class="mb-0"><?php echo $stats['fulfilled_requests'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-check-all fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="request-blood.php" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Request Blood
                    </a>
                    <a href="my-requests.php" class="btn btn-outline-secondary">
                        <i class="bi bi-list"></i> My Requests
                    </a>
                    <a href="search-donors.php" class="btn btn-outline-info">
                        <i class="bi bi-search"></i> Search Donors
                    </a>
                    <a href="profile.php" class="btn btn-outline-success">
                        <i class="bi bi-person"></i> Update Profile
                    </a>
                    <a href="messages.php" class="btn btn-outline-warning">
                        <i class="bi bi-envelope"></i> Messages
                        <span class="badge bg-danger"><?php echo count($messages); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Matched Donations -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-heart"></i> Matched Donors</h5>
            </div>
            <div class="card-body">
                <?php if(count($matched_donations) > 0): ?>
                    <div class="list-group">
                        <?php foreach($matched_donations as $donation): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($donation['donor_name']); ?></h6>
                                    <span class="badge bg-primary"><?php echo $donation['blood_group']; ?></span>
                                </div>
                                <p class="mb-1">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('d M Y', strtotime($donation['donation_date'])); ?><br>
                                    <i class="bi bi-telephone"></i> 
                                    <?php echo htmlspecialchars($donation['donor_phone'] ?? 'N/A'); ?>
                                </p>
                                <small class="text-muted">Contact the donor to confirm</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-heart-broken display-6 d-block"></i>
                        No matched donors yet
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Requests -->
    <div class="col-md-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Requests</h5>
            </div>
            <div class="card-body">
                <?php if(count($recent_requests) > 0): ?>
                    <div class="list-group">
                        <?php foreach($recent_requests as $req): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">Blood: <?php echo $req['blood_group']; ?></h6>
                                    <span class="badge bg-<?php 
                                        echo $req['status'] == 'fulfilled' ? 'success' : 
                                            ($req['status'] == 'matched' ? 'info' : 
                                            ($req['status'] == 'pending' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </div>
                                <p class="mb-1">
                                    <i class="bi bi-hospital"></i> <?php echo htmlspecialchars($req['hospital_name']); ?><br>
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($req['request_date'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">
                        <i class="bi bi-file-text display-6 d-block"></i>
                        No blood requests yet
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="my-requests.php" class="btn btn-sm btn-outline-primary w-100">
                    View All Requests
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Messages -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-envelope"></i> Recent Messages</h5>
            </div>
            <div class="card-body">
                <?php if(count($messages) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($messages as $msg): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($msg['subject'] ?? 'No subject'); ?></td>
                                    <td><?php echo substr(htmlspecialchars($msg['message']), 0, 50); ?>...</td>
                                    <td><?php echo date('d M Y', strtotime($msg['created_at'])); ?></td>
                                    <td>
                                        <?php if(!$msg['is_read']): ?>
                                            <span class="badge bg-danger">New</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Read</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No messages yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>