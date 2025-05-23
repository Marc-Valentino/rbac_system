<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /rbac_system/index.php');
    exit;
}

$error = '';
$success = '';

// Get available roles for registration (exclude administrator)
$availableRoles = queryData('roles', '*', ['name' => 'neq.administrator'], 'name.asc');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if (empty($email) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName) || $roleId === 0) {
        $error = 'Please fill in all fields and select a role.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
        
        $authData = registerUser($email, $password, $userData);
        
        if ($authData && isset($authData['id'])) {
            // Assign selected role to new user
            assignRoleToUser($authData['id'], $roleId);
            
            $success = 'Registration successful! You can now login.';
        } else {
            if (isset($authData['error']) && $authData['error'] === 'email_exists') {
                $error = 'This email is already registered. Please use a different email.';
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Register for Taskify';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Create Account</h2>
                    <p class="text-muted">Join Taskify to manage your tasks efficiently</p>
                </div>
                
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
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="first_name" name="first_name" placeholder="First name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="last_name" name="last_name" placeholder="Last name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-envelope text-muted"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="name@example.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Create a password" required>
                        </div>
                        <div class="form-text small">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="role_id" class="form-label">Select Your Role</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-user-tag text-muted"></i>
                            </span>
                            <select class="form-select border-start-0" id="role_id" name="role_id" required>
                                <option value="">Select a role</option>
                                <?php foreach ($availableRoles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-text small">Choose the role that best describes your position.</div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
                
                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="/rbac_system/users/login.php" class="text-decoration-none fw-medium">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rounded-4 {
    border-radius: 0.75rem !important;
}

.text-gradient {
    background: linear-gradient(45deg, #4e73df, #36b9cc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent; /* Fallback for browsers that don't support -webkit-text-fill-color */
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>