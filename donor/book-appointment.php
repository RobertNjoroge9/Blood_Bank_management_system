<?php
// donor/book-appointment.php
require_once '../includes/auth.php';
requireDonor();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get donor's blood group
$stmt = $pdo->prepare("SELECT blood_group FROM donor_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$donor = $stmt->fetch();
$donor_blood_group = $donor['blood_group'] ?? '';

// Get available blood requests matching donor's blood group
$stmt = $pdo->prepare("
    SELECT br.*, u.full_name as receiver_name, u.phone as receiver_phone,
           rp.emergency_contact, rp.hospital_registered,
           DATEDIFF(br.request_date, CURDATE()) as days_until_needed
    FROM blood_requests br
    JOIN users u ON br.receiver_id = u.id
    LEFT JOIN receiver_profiles rp ON u.id = rp.user_id
    WHERE br.status = 'pending' 
    AND br.blood_group = ?
    ORDER BY 
        CASE br.urgency 
            WHEN 'emergency' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END,
        br.request_date ASC
");
$stmt->execute([$donor_blood_group]);
$requests = $stmt->fetchAll();

// Get donor's upcoming appointments
$stmt = $pdo->prepare("
    SELECT d.*, br.hospital_name, br.blood_group
    FROM donations d
    JOIN blood_requests br ON d.request_id = br.id
    WHERE d.donor_id = ? AND d.status = 'scheduled'
");
$stmt->execute([$user_id]);
$existing_appointments = $stmt->fetchAll();

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $request_id = $_POST['request_id'];
    $donation_date = $_POST['donation_date'];
    $notes = $_POST['notes'] ?? '';
    
    // Validate date
    if (strtotime($donation_date) < strtotime(date('Y-m-d'))) {
        $error = "Please select a future date";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert donation
            $stmt = $pdo->prepare("
                INSERT INTO donations (donor_id, request_id, donation_date, notes, status) 
                VALUES (?, ?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$user_id, $request_id, $donation_date, $notes]);
            
            // Update request status to matched
            $stmt = $pdo->prepare("UPDATE blood_requests SET status = 'matched' WHERE id = ?");
            $stmt->execute([$request_id]);
            
            // Create notification for receiver
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                SELECT receiver_id, 'Donor Found', ?, 'success'
                FROM blood_requests WHERE id = ?
            ");
            $notification_msg = "A donor has scheduled an appointment for your blood request.";
            $stmt->execute([$notification_msg, $request_id]);
            
            $pdo->commit();
            $success = "Appointment booked successfully!";
            
            // Refresh data
            header("Refresh:2");
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Failed to book appointment: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="bi bi-calendar-plus text-danger"></i> Book Donation Appointment</h2>
        <p class="lead">Choose a blood request to fulfill and schedule your donation</p>
    </div>
</div>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Available Requests -->
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> Available Blood Requests (<?php echo count($requests); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if(count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Blood Group</th>
                                    <th>Units</th>
                                    <th>Hospital</th>
                                    <th>Urgency</th>
                                    <th>Required By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requests as $request): ?>
                                <tr class="<?php echo $request['urgency'] == 'emergency' ? 'table-danger' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($request['receiver_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: #<?php echo $request['id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo $request['blood_group']; ?></span>
                                    </td>
                                    <td><?php echo $request['units_needed']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($request['hospital_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($request['hospital_registered'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php if($request['urgency'] == 'emergency'): ?>
                                            <span class="badge bg-danger">Emergency</span>
                                        <?php elseif($request['urgency'] == 'urgent'): ?>
                                            <span class="badge bg-warning text-dark">Urgent</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($request['request_date']);
                                        echo $date->format('d M Y');
                                        ?>
                                        <?php if($request['days_until_needed'] < 3): ?>
                                            <br><span class="badge bg-warning text-dark">Soon</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="showBookingModal(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                            <i class="bi bi-calendar-plus"></i> Book
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h5 class="mt-3">No matching blood requests available</h5>
                        <p class="text-muted">Check back later or search for other opportunities</p>
                        <a href="dashboard.php" class="btn btn-outline-danger">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Your Blood Group -->
        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-droplet"></i> Your Blood Group</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-3 text-danger"><?php echo $donor_blood_group; ?></h1>
                <p>You can donate to patients with compatible blood types</p>
                <small class="text-muted">
                    Compatible groups: 
                    <?php
                    $compatible = [
                        'O-' => ['O-'],
                        'O+' => ['O+', 'O-'],
                        'A-' => ['A-', 'O-'],
                        'A+' => ['A+', 'A-', 'O+', 'O-'],
                        'B-' => ['B-', 'O-'],
                        'B+' => ['B+', 'B-', 'O+', 'O-'],
                        'AB-' => ['AB-', 'A-', 'B-', 'O-'],
                        'AB+' => ['All types']
                    ];
                    echo implode(', ', $compatible[$donor_blood_group] ?? ['All types']);
                    ?>
                </small>
            </div>
        </div>
        
        <!-- Your Upcoming Appointments -->
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-calendar"></i> Your Appointments</h5>
            </div>
            <div class="card-body">
                <?php if(count($existing_appointments) > 0): ?>
                    <div class="list-group">
                        <?php foreach($existing_appointments as $apt): ?>
                            <div class="list-group-item">
                                <i class="bi bi-hospital"></i> <?php echo htmlspecialchars($apt['hospital_name']); ?><br>
                                <small>
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($apt['donation_date'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0">No upcoming appointments</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="my-appointments.php" class="btn btn-sm btn-outline-info w-100">
                    View All Appointments
                </a>
            </div>
        </div>
        
        <!-- Donation Guidelines -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Donation Guidelines</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> Be at least 18 years old</li>
                    <li><i class="bi bi-check-circle text-success"></i> Weigh at least 45kg</li>
                    <li><i class="bi bi-check-circle text-success"></i> Be in good health</li>
                    <li><i class="bi bi-check-circle text-success"></i> No alcohol 24hrs before</li>
                    <li><i class="bi bi-check-circle text-success"></i> Eat well before donation</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Book Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <input type="text" class="form-control" id="modal_patient" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hospital</label>
                        <input type="text" class="form-control" id="modal_hospital" readonly disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="donation_date" class="form-label">Donation Date</label>
                        <input type="date" class="form-control" id="donation_date" name="donation_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any special instructions or comments"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="book_appointment" class="btn btn-danger">
                        <i class="bi bi-check-circle"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showBookingModal(request) {
    document.getElementById('modal_request_id').value = request.id;
    document.getElementById('modal_patient').value = request.receiver_name;
    document.getElementById('modal_hospital').value = request.hospital_name;
    
    var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>