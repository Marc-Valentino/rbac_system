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

// Include header for public page
$pageTitle = 'Welcome to Taskify';
include_once __DIR__ . '/includes/header.php';  // Changed from header_public.php to header.php
?>

<div class="container">
    <!-- Updated Header Section -->
    <div class="row justify-content-center mt-5">
        <div class="col-lg-10 text-center">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-body p-5">
                    <h1 class="display-4 fw-bold mb-4">Welcome to <span class="text-gradient">Taskify</span></h1>
                    <p class="lead mb-4">The intelligent task management system that helps teams collaborate efficiently and deliver projects on time.</p>
                    
                    <!-- Icon Row Instead of Image -->
                    <div class="row justify-content-center my-5">
                        <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                            <div class="icon-box text-center">
                                <div class="icon-circle bg-primary text-white mx-auto mb-3">
                                    <i class="fas fa-tasks fa-2x"></i>
                                </div>
                                <h5>Task Management</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                            <div class="icon-box text-center">
                                <div class="icon-circle bg-success text-white mx-auto mb-3">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <h5>Team Collaboration</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                            <div class="icon-box text-center">
                                <div class="icon-circle bg-warning text-white mx-auto mb-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <h5>Progress Tracking</h5>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                            <div class="icon-box text-center">
                                <div class="icon-circle bg-info text-white mx-auto mb-3">
                                    <i class="fas fa-shield-alt fa-2x"></i>
                                </div>
                                <h5>Secure Access</h5>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#features" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                        <a href="/rbac_system/users/login.php" class="btn btn-outline-primary btn-lg rounded-pill px-4">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row my-5" id="features">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">Why Choose Taskify?</h2>
            <p class="text-muted">Powerful features designed for modern teams</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h5 class="card-title">Task Management</h5>
                    <p class="card-text">Create, assign, and track tasks with ease. Set priorities, deadlines, and monitor progress in real-time.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-success text-white rounded-circle mb-3">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="card-title">Role-Based Access</h5>
                    <p class="card-text">Secure your workflow with our advanced role-based access control system. Right permissions for the right people.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="feature-icon bg-info text-white rounded-circle mb-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="card-title">Analytics & Reports</h5>
                    <p class="card-text">Gain insights into team performance, project progress, and resource allocation with detailed reports.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-gradient {
    background: linear-gradient(45deg, #4e73df, #36b9cc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent; /* Fallback for browsers that don't support -webkit-text-fill-color */
}

.feature-icon {
    width: 60px;
    height: 60px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.icon-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.icon-box:hover .icon-circle {
    transform: scale(1.1);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.rounded-4 {
    border-radius: 0.75rem !important;
}
</style>

<?php include_once __DIR__ . '/includes/footer.php'; ?>