<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require admin role
requireRole('administrator');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (clearAllCache()) {
        $success = 'Cache cleared successfully!';
    } else {
        $error = 'Failed to clear cache.';
    }
}

$pageTitle = 'Clear System Cache';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                    <h3 class="mb-0">Clear System Cache</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <?php echo displayError($error); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <?php echo displaySuccess($success); ?>
                    <?php endif; ?>
                    
                    <p class="mb-4">
                        Clearing the system cache will refresh all data from the database. 
                        This can help resolve issues with stale data or performance problems.
                    </p>
                    
                    <form method="POST" action="">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill">
                                <i class="fas fa-sync-alt me-2"></i>Clear Cache
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>