<?php
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$userRoles = getUserRoles();
$roleNames = array_map(function($role) {
    return $role['name'];
}, $userRoles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Taskify'; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/rbac_system/assets/css/style.css">
    
    <style>
    .text-gradient {
        background: linear-gradient(45deg, #4e73df, #36b9cc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .nav-link.btn {
        padding: 0.5rem 1.25rem;
        transition: all 0.3s ease;
    }
    
    .nav-link.btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/rbac_system/index.php">
                <i class="fas fa-shield-alt me-2 text-primary"></i><span class="text-gradient">Taskify</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-3" href="/rbac_system/index.php">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if (hasRole('administrator')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle rounded-pill px-3" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-shield me-1"></i> Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2 text-primary"></i> Admin Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/manage_users.php">
                                    <i class="fas fa-users me-2 text-primary"></i> Manage Users
                                </a></li>
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/manage_roles.php">
                                    <i class="fas fa-user-tag me-2 text-primary"></i> Manage Roles
                                </a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasAnyRole(['project_manager', 'team_leader', 'team_member'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle rounded-pill px-3" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-tasks me-1"></i> Tasks
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="/rbac_system/tasks/index.php">
                                    <i class="fas fa-list me-2 text-primary"></i> All Tasks
                                </a></li>
                                <?php if (hasAnyRole(['project_manager', 'team_leader'])): ?>
                                <li><a class="dropdown-item" href="/rbac_system/tasks/create.php">
                                    <i class="fas fa-plus me-2 text-primary"></i> Create Task
                                </a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('project_manager')): ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-3" href="/rbac_system/pages/manager/dashboard.php">
                                <i class="fas fa-project-diagram me-1"></i> Manager Dashboard
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('team_leader')): ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill px-3" href="/rbac_system/pages/team_leader/dashboard.php">
                                <i class="fas fa-users-cog me-1"></i> Team Dashboard
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle rounded-pill px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?php 
                                $currentUser = getCurrentUser();
                                $profilePic = !empty($currentUser['profile_picture']) ? $currentUser['profile_picture'] : '/rbac_system/assets/img/default-avatar.png';
                                ?>
                                <img src="<?php echo $profilePic; ?>" alt="Profile" class="rounded-circle me-1 border" width="28" height="28">
                                <?php echo htmlspecialchars($currentUser['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="/rbac_system/users/profile.php">
                                    <i class="fas fa-user me-2 text-primary"></i> My Profile
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/rbac_system/users/logout.php">
                                    <i class="fas fa-sign-out-alt me-2 text-primary"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-primary rounded-pill px-3 me-2" href="/rbac_system/users/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary rounded-pill px-3 text-white" href="/rbac_system/users/register.php">
                                <i class="fas fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container py-4 fade-in">
        <!-- Content will be here -->