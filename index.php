<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
?>
<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12 text-center mb-5">
        <h1>Welcome to Blood Bank Management System</h1>
        <p class="lead">Saving lives by connecting blood donors with those in need</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Donate Blood</h5>
                <p class="card-text">Be a hero - donate blood and save lives. Your single donation can save up to three lives.</p>
                <a href="register.php?type=donor" class="btn btn-danger">Register as Donor</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Request Blood</h5>
                <p class="card-text">Need blood? Register as a receiver and submit your blood request.</p>
                <a href="register.php?type=receiver" class="btn btn-danger">Register as Receiver</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Find Donors</h5>
                <p class="card-text">Search for blood donors by blood group and location.</p>
                <a href="search-donors.php" class="btn btn-danger">Search Donors</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-12">
        <h3>Why Donate Blood?</h3>
        <ul>
            <li>Safe blood saves lives and improves health</li>
            <li>Blood is needed by women with complications during pregnancy and childbirth</li>
            <li>Children with severe anemia often require blood transfusion</li>
            <li>Patients with cancer, trauma victims, and surgical patients need blood</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>