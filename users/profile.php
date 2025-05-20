<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();
$userId = getCurrentUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? 'user-circle'); // Default icon
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'Please fill in all required fields.';
    } else {
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'profile_picture' => $icon // Store the icon name instead of image path
        ];
        
        $updated = updateData('users', $userData, ['id' => 'eq.' . $userId]);
        
        if ($updated) {
            $success = 'Profile updated successfully.';
            // Refresh user data
            $currentUser = getCurrentUser();
        } else {
            $error = 'Failed to update profile.';
        }
    }
}

$pageTitle = 'My Profile';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <div class="icon-preview rounded-circle bg-light d-inline-flex justify-content-center align-items-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-<?php echo !empty($currentUser['profile_picture']) ? htmlspecialchars($currentUser['profile_picture']) : 'user-circle'; ?> fa-3x text-primary"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                        <p class="text-muted">
                            <?php 
                            $roles = getUserRoles();
                            $roleNames = array_map(function($role) {
                                return $role['name'];
                            }, $roles);
                            echo htmlspecialchars(implode(', ', $roleNames));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 pb-0 px-4">
                    <h3 class="mb-0">Edit Profile</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <?php echo displayError($error); ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <?php echo displaySuccess($success); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Icon</label>
                            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mb-3">
                                <?php
                                $icons = ['user-circle', 'user-tie', 'user-ninja', 'user-astronaut', 'user-graduate', 
                                          'user-secret', 'user-shield', 'user-cog', 'user-edit', 'user-check'];
                                $currentIcon = !empty($currentUser['profile_picture']) ? $currentUser['profile_picture'] : 'user-circle';
                                
                                foreach ($icons as $icon):
                                ?>
                                <div class="col">
                                    <div class="form-check icon-check">
                                        <input class="form-check-input visually-hidden" type="radio" name="icon" id="icon-<?php echo $icon; ?>" value="<?php echo $icon; ?>" <?php echo ($currentIcon === $icon) ? 'checked' : ''; ?>>
                                        <label class="form-check-label d-flex flex-column align-items-center" for="icon-<?php echo $icon; ?>">
                                            <div class="icon-container rounded-circle bg-light d-flex justify-content-center align-items-center mb-2" style="width: 50px; height: 50px;">
                                                <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
                                            </div>
                                            <span class="small text-center"><?php echo ucwords(str_replace('-', ' ', $icon)); ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-check .form-check-input:checked + .form-check-label .icon-container {
    background-color: #e8f0fe !important;
    border: 2px solid #4e73df;
}

.icon-check .form-check-label {
    cursor: pointer;
    transition: all 0.2s;
}

.icon-check .form-check-label:hover .icon-container {
    background-color: #f8f9fa !important;
    transform: translateY(-2px);
}

.icon-preview {
    border: 2px solid #e9ecef;
}

.rounded-4 {
    border-radius: 0.75rem !important;
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>