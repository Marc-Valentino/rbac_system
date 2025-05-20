<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /rbac_system/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $authData = authenticateUser($email, $password);
        
        if ($authData && isset($authData['access_token']) && isset($authData['user']['id'])) {
            $_SESSION['user_id'] = $authData['user']['id'];
            $_SESSION['access_token'] = $authData['access_token'];
            $_SESSION['refresh_token'] = $authData['refresh_token'];
            
            header('Location: /rbac_system/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <h2>Login</h2>
    
    <?php if (!empty($error)): ?>
        <?php echo displayError($error); ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Login</button>
        </div>
    </form>
    
    <div class="mt-3 text-center">
        <p>Don't have an account? <a href="/rbac_system/users/register.php">Register</a></p>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>