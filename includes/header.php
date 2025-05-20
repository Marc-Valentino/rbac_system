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
    <title>Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/rbac_system/assets/css/style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/rbac_system/index.php">Task Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/index.php">Dashboard</a>
                        </li>
                        
                        <?php if (hasRole('administrator')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/dashboard.php">Admin Dashboard</a></li>
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/manage_users.php">Manage Users</a></li>
                                <li><a class="dropdown-item" href="/rbac_system/pages/admin/manage_roles.php">Manage Roles</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasAnyRole(['project_manager', 'team_leader', 'team_member'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown">
                                Tasks
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/rbac_system/tasks/index.php">All Tasks</a></li>
                                <?php if (hasAnyRole(['project_manager', 'team_leader'])): ?>
                                <li><a class="dropdown-item" href="/rbac_system/tasks/create.php">Create Task</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('project_manager')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/pages/manager/dashboard.php">Manager Dashboard</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('team_leader')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/pages/team_leader/dashboard.php">Team Leader Dashboard</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('team_member')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/pages/team_member/dashboard.php">Team Member Dashboard</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('client')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/pages/client/dashboard.php">Client Dashboard</a>
                        </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/rbac_system/users/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/rbac_system/users/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/users/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/rbac_system/users/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                    $error = $_GET['error'];
                    if ($error === 'unauthorized') {
                        echo 'You do not have permission to access this page.';
                    } else {
                        echo htmlspecialchars($error);
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>