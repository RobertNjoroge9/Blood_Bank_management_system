<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$donors = [];
$search_performed = false;

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $blood_group = $_GET['blood_group'];
    $location = $_GET['location'];
    
    $sql = "SELECT u.*, dp.blood_group, dp.last_donation_date, dp.is_available 
            FROM users u 
            JOIN donor_profiles dp ON u.id = dp.user_id 
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
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $donors = $stmt->fetchAll();
    $search_performed = true;
}
?>
<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Search Blood Donors</h2>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Search Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="mb-3">
                        <label for="blood_group" class="form-label">Blood Group</label>
                        <select class="form-control" id="blood_group" name="blood_group">
                            <option value="">All Blood Groups</option>
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
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="Enter city or area">
                    </div>
                    
                    <button type="submit" name="search" class="btn btn-danger">Search Donors</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Search Results</h5>
            </div>
            <div class="card-body">
                <?php if($search_performed): ?>
                    <?php if(count($donors) > 0): ?>
                        <div class="row">
                            <?php foreach($donors as $donor): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $donor['full_name']; ?></h5>
                                        <p class="card-text">
                                            <strong>Blood Group:</strong> 
                                            <span class="badge bg-danger"><?php echo $donor['blood_group']; ?></span><br>
                                            <strong>Phone:</strong> <?php echo $donor['phone']; ?><br>
                                            <strong>Location:</strong> <?php echo $donor['address']; ?><br>
                                            <strong>Last Donation:</strong> <?php echo $donor['last_donation_date'] ?: 'Never'; ?>
                                        </p>
                                        <a href="../messages/send.php?receiver_id=<?php echo $donor['id']; ?>" class="btn btn-sm btn-primary">Contact Donor</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No donors found matching your criteria.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-center">Use the search filters to find blood donors.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>