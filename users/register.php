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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    
    if (empty($email) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName)) {
        $error = 'Please fill in all fields.';
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
            // Assign default role (team_member) to new user
            $roles = queryData('roles', '*', ['name' => 'eq.team_member']);
            
            if ($roles && count($roles) > 0) {
                assignRoleToUser($authData['id'], $roles[0]['id']);
            }
            
            $success = 'Registration successful! You can now login.';
        } else {
            $error = 'Registration failed. Email may already be in use.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <h2>Register</h2>
    
    <?php if (!empty($error)): ?>
        <?php echo displayError($error); ?>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <?php echo displaySuccess($success); ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" required>
        </div>
        
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" required>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="form-text">Password must be at least 8 characters long.</div>
        </div>
        
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </form>
    
    <div class="mt-3 text-center">
        <p>Already have an account? <a href="/rbac_system/users/login.php">Login</a></p>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>