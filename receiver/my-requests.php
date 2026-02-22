<?php
// receiver/my-requests.php
require_once '../includes/auth.php';
requireReceiver();

$user_id = $_SESSION['user_id'];

// Get all requests
$stmt = $pdo->prepare("
    SELECT br.*, 
           COUNT(d.id) as donation_count,
           GROUP_CONCAT(CONCAT(d.donor_id, ':', d.status) SEPARATOR '|') as donation_info
    FROM blood_requests br
    LEFT JOIN donations d ON br.id = d.request_id
    WHERE br.receiver_id = ?
    GROUP BY br.id
    ORDER BY 
        CASE br.urgency 
            WHEN 'emergency' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END,
        br.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_request'])) {
    $request_id = $_POST['request_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = ? AND receiver_id = ?");
        if ($stmt->execute([$request_id, $user_id])) {
            $success = "Request cancelled successfully";
            header("Refresh:2");
        }
    } catch(PDOException $e) {
        $error = "Cancellation failed: " . $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-list text-primary"></i> My Blood Requests</h2>
        <p class="lead">Track and manage your blood donation requests</p>
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
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list"></i> All Requests</h5>
                <a href="request-blood.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle"></i> New Request
                </a>
            </div>
            <div class="card-body">
                <?php if(count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Blood Group</th>
                                    <th>Units</th>
                                    <th>Hospital</th>
                                    <th>Required By</th>
                                    <th>Urgency</th>
                                    <th>Status</th>
                                    <th>Donors</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requests as $req): ?>
                                <tr class="<?php echo $req['urgency'] == 'emergency' ? 'table-danger' : ''; ?>">
                                    <td>#<?php echo $req['id']; ?></td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $req['blood_group']; ?></span>
                                    </td>
                                    <td><?php echo $req['units_needed']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($req['hospital_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($req['hospital_address'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($req['request_date'])); ?>
                                        <?php 
                                        $days_left = (strtotime($req['request_date']) - time()) / (60 * 60 * 24);
                                        if($days_left < 3 && $req['status'] == 'pending'): 
                                        ?>
                                            <br><span class="badge bg-warning text-dark">Soon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($req['urgency'] == 'emergency'): ?>
                                            <span class="badge bg-danger">Emergency</span>
                                        <?php elseif($req['urgency'] == 'urgent'): ?>
                                            <span class="badge bg-warning text-dark">Urgent</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($req['status'] == 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif($req['status'] == 'matched'): ?>
                                            <span class="badge bg-info">Matched</span>
                                        <?php elseif($req['status'] == 'fulfilled'): ?>
                                            <span class="badge bg-success">Fulfilled</span>
                                        <?php elseif($req['status'] == 'cancelled'): ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $donor_count = 0;
                                        if($req['donation_info']) {
                                            $donations = explode('|', $req['donation_info']);
                                            $donor_count = count($donations);
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $donor_count > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $donor_count; ?> donor(s)
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($req['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmCancellation(<?php echo $req['id']; ?>)">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        <?php elseif($req['status'] == 'matched'): ?>
                                            <a href="search-donors.php" class="btn btn-sm btn-info">
                                                <i class="bi bi-search"></i> Find More
                                            </a>
                                        <?php elseif($req['status'] == 'fulfilled'): ?>
                                            <span class="text-success"><i class="bi bi-check-circle"></i> Done</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-text display-1 text-muted"></i>
                        <h5 class="mt-3">No blood requests found</h5>
                        <p class="text-muted">You haven't submitted any blood requests yet</p>
                        <a href="request-blood.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Submit Your First Request
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Cancellation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Cancellation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this blood request?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="request_id" id="cancel_request_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                    <button type="submit" name="cancel_request" class="btn btn-danger">
                        Yes, Cancel Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCancellation(requestId) {
    document.getElementById('cancel_request_id').value = requestId;
    var modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>