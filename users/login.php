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

$pageTitle = 'Login to Taskify';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold">Welcome Back</h2>
                    <p class="text-muted">Login to your Taskify account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <?php echo displayError($error); ?>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-envelope text-muted"></i>
                            </span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="name@example.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-lock text-muted"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center">
                    <p class="mb-0">Don't have an account? <a href="/rbac_system/users/register.php" class="text-decoration-none fw-medium">Register</a></p>
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
    -webkit-text-fill-color: transparent;
}
</style>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>