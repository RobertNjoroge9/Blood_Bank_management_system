<?php
require_once '../includes/auth.php';
requireReceiver();

// Get receiver's requests
$stmt = $pdo->prepare("
    SELECT * FROM blood_requests 
    WHERE receiver_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();

// Get matched donations
$stmt = $pdo->prepare("
    SELECT d.*, u.full_name as donor_name, u.phone as donor_phone
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    JOIN blood_requests br ON d.request_id = br.id
    WHERE br.receiver_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$donations = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Receiver Dashboard</h2>
        <p>Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Requests</h5>
                <p class="card-text display-4"><?php echo count($requests); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Matched Donations</h5>
                <p class="card-text display-4"><?php echo count($donations); ?></p>
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
                <a href="request-blood.php" class="btn btn-danger mb-2">Request Blood</a>
                <a href="my-requests.php" class="btn btn-secondary mb-2">View My Requests</a>
                <a href="search-donors.php" class="btn btn-info mb-2">Search Donors</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Recent Requests</h5>
            </div>
            <div class="card-body">
                <?php if(count($requests) > 0): ?>
                    <ul class="list-group">
                        <?php foreach(array_slice($requests, 0, 5) as $req): ?>
                        <li class="list-group-item">
                            <strong>Blood Group:</strong> <?php echo $req['blood_group']; ?><br>
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $req['status'] == 'fulfilled' ? 'success' : ($req['status'] == 'pending' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst($req['status']); ?>
                            </span>
                            <br>
                            <strong>Date:</strong> <?php echo date('Y-m-d', strtotime($req['created_at'])); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No blood requests yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>