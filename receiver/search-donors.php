<?php
// receiver/search-donors.php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Allow public access to search donors (uncomment below to restrict to logged-in users only)
// requireLogin();

$donors = [];
$search_performed = false;
$error = '';

// Get blood groups for dropdown
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $blood_group = $_GET['blood_group'] ?? '';
    $location = trim($_GET['location'] ?? '');
    
    try {
        $sql = "SELECT u.*, dp.blood_group, dp.last_donation_date, dp.is_available,
                       DATEDIFF(CURDATE(), dp.last_donation_date) as days_since_last_donation
                FROM users u 
                INNER JOIN donor_profiles dp ON u.id = dp.user_id 
                WHERE u.user_type = 'donor' AND dp.is_available = 1";
        
        $params = [];
        
        if (!empty($blood_group)) {
            $sql .= " AND dp.blood_group = ?";
            $params[] = $blood_group;
        }
        
        if (!empty($location)) {
            $sql .= " AND u.address LIKE ?";
            $params[] = "%$location%";
        }
        
        $sql .= " ORDER BY 
                    CASE WHEN dp.last_donation_date IS NULL THEN 0 ELSE 1 END,
                    dp.last_donation_date ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $donors = $stmt->fetchAll();
        $search_performed = true;
        
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-search text-danger"></i> Search Blood Donors</h2>
        <p class="lead">Find blood donors by blood group and location to save lives</p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Search Filters Column -->
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Search Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-select" id="blood_group" name="blood_group">
                            <option value="">All Blood Groups</option>
                            <?php foreach($blood_groups as $bg): ?>
                                <option value="<?php echo $bg; ?>" <?php echo (isset($_GET['blood_group']) && $_GET['blood_group'] == $bg) ? 'selected' : ''; ?>>
                                    <?php echo $bg; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>" 
                               placeholder="Enter city or area">
                    </div>
                    
                    <button type="submit" name="search" class="btn btn-danger w-100">
                        <i class="bi bi-search"></i> Search Donors
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Quick Tips</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Leave all fields empty to see all available donors</li>
                    <li>Select blood group for specific match</li>
                    <li>Enter location to find nearby donors</li>
                    <li>Contact donors directly via phone or message</li>
                    <li>Donors marked with <span class="badge bg-warning">Recent</span> have donated recently</li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-heart"></i> Why Donate Blood?</h5>
            </div>
            <div class="card-body">
                <p>One blood donation can save up to three lives. Blood is needed for:</p>
                <ul>
                    <li>Accident victims</li>
                    <li>Surgery patients</li>
                    <li>Cancer patients</li>
                    <li>Mothers with complications</li>
                    <li>Premature babies</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Results Column -->
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people"></i> Available Donors</h5>
                <?php if($search_performed): ?>
                    <span class="badge bg-light text-dark"><?php echo count($donors); ?> donor(s) found</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if($search_performed): ?>
                    <?php if(count($donors) > 0): ?>
                        <div class="row">
                            <?php foreach($donors as $donor): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-danger">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title text-danger mb-0">
                                                <i class="bi bi-person-circle"></i> 
                                                <?php echo htmlspecialchars($donor['full_name']); ?>
                                            </h5>
                                            <span class="badge bg-danger fs-6"><?php echo $donor['blood_group']; ?></span>
                                        </div>
                                        
                                        <p class="card-text">
                                            <small class="text-muted d-block">
                                                <i class="bi bi-telephone"></i> 
                                                <strong>Phone:</strong> <?php echo htmlspecialchars($donor['phone'] ?? 'Not provided'); ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-geo-alt"></i> 
                                                <strong>Location:</strong> <?php echo htmlspecialchars($donor['address'] ?? 'Not provided'); ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar"></i> 
                                                <strong>Last Donation:</strong> 
                                                <?php 
                                                if($donor['last_donation_date']) {
                                                    $days = $donor['days_since_last_donation'] ?? 0;
                                                    echo date('d M Y', strtotime($donor['last_donation_date']));
                                                    if($days < 90) {
                                                        echo ' <span class="badge bg-warning text-dark">Recent</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-success">Never donated - Ready to donate</span>';
                                                }
                                                ?>
                                            </small>
                                        </p>
                                        
                                        <div class="mt-3">
                                            <?php if(isset($_SESSION['user_id'])): ?>
                                                <a href="<?php echo BASE_PATH; ?>messages/send.php?receiver_id=<?php echo $donor['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-chat"></i> Contact
                                                </a>
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        onclick="copyPhone('<?php echo $donor['phone']; ?>')">
                                                    <i class="bi bi-files"></i> Copy Phone
                                                </button>
                                            <?php else: ?>
                                                <a href="<?php echo BASE_PATH; ?>login.php" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-box-arrow-in-right"></i> Login to Contact
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-emoji-frown display-1 text-muted"></i>
                            <h5 class="mt-3">No donors found</h5>
                            <p class="text-muted">Try adjusting your search criteria or check back later</p>
                            <a href="<?php echo BASE_PATH; ?>receiver/search-donors.php" class="btn btn-outline-danger">
                                <i class="bi bi-arrow-counterclockwise"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search display-1 text-muted"></i>
                        <h5 class="mt-3">Start Searching</h5>
                        <p class="text-muted">Use the filters on the left to find blood donors in your area</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyPhone(phone) {
    navigator.clipboard.writeText(phone).then(function() {
        alert('Phone number copied to clipboard!');
    }, function() {
        alert('Failed to copy phone number');
    });
}
</script>

<?php include '../includes/footer.php'; ?>