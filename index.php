<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard based on user role
    $currentUser = getCurrentUser();
    
    if (hasRole('administrator')) {
        header('Location: /rbac_system/pages/admin/dashboard.php');
        exit;
    } elseif (hasRole('project_manager')) {
        header('Location: /rbac_system/pages/manager/dashboard.php');
        exit;
    } elseif (hasRole('team_leader')) {
        header('Location: /rbac_system/pages/team_leader/dashboard.php');
        exit;
    } elseif (hasRole('team_member')) {
        header('Location: /rbac_system/pages/team_member/dashboard.php');
        exit;
    } else {
        // Default dashboard for users with no specific role
        header('Location: /rbac_system/dashboard.php');
        exit;
    }
}

// If not logged in, show login page
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Authenticate user
        $authData = authenticateUser($email, $password);
        
        if ($authData && isset($authData['access_token'])) {
            // Set session variables
            $_SESSION['user_id'] = $authData['user']['id'];
            $_SESSION['access_token'] = $authData['access_token'];
            $_SESSION['refresh_token'] = $authData['refresh_token'];
            
            // Redirect based on role
            header('Location: /rbac_system/index.php');
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// Include header without authentication requirement
$pageTitle = 'Login';
include_once __DIR__ . '/includes/header_public.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">RBAC System Login</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Don't have an account? <a href="/rbac_system/register.php">Register here</a></p>
                    </div>
                </div>
                <div class="card-footer text-muted text-center">
                    <small>Role-Based Access Control System</small>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Demo Accounts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-danger">Administrator</span></td>
                                    <td>admin@example.com</td>
                                    <td>password123</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">Project Manager</span></td>
                                    <td>manager@example.com</td>
                                    <td>password123</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">Team Leader</span></td>
                                    <td>leader@example.com</td>
                                    <td>password123</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning text-dark">Team Member</span></td>
                                    <td>member@example.com</td>
                                    <td>password123</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>