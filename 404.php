<?php
// 404.php
require_once 'config/database.php';
?>
<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12 text-center py-5">
        <div class="error-template">
            <h1 class="display-1 text-danger">404</h1>
            <h2 class="display-4">Page Not Found</h2>
            <div class="error-details my-4">
                <p class="lead">Sorry, the page you're looking for doesn't exist or has been moved.</p>
                <p><i class="bi bi-emoji-frown display-6"></i></p>
            </div>
            <div class="error-actions">
                <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn-danger btn-lg">
                    <i class="bi bi-house"></i> Go to Homepage
                </a>
                <a href="<?php echo BASE_PATH; ?>receiver/search-donors.php" class="btn btn-outline-danger btn-lg">
                    <i class="bi bi-search"></i> Find Donors
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                    <i class="bi bi-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>