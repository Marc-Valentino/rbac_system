<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin role
requireRole('administrator');

$currentUser = getCurrentUser();
$userCount = count(getAllUsers());
$roleCount = count(getAllRoles());

// Get system statistics
$taskStats = [
    'total' => count(queryData('tasks', '*')),
    'pending' => count(queryData('tasks', '*', ['status' => 'eq.pending'])),
    'in_progress' => count(queryData('tasks', '*', ['status' => 'eq.in_progress'])),
    'completed' => count(queryData('tasks', '*', ['status' => 'eq.completed'])),
];

$projectStats = [
    'total' => count(queryData('projects', '*')),
    'active' => count(queryData('projects', '*', ['status' => 'eq.active'])),
    'completed' => count(queryData('projects', '*', ['status' => 'eq.completed'])),
];

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-dashboard">
    <h2>Administrator Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Users</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <h2 class="display-4 mb-0"><?php echo $userCount; ?></h2>
                    <p class="card-text">Total registered users</p>
                    <div class="mt-auto">
                        <a href="/rbac_system/pages/admin/manage_users.php" class="btn btn-primary btn-sm">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Roles</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <h2 class="display-4 mb-0"><?php echo $roleCount; ?></h2>
                    <p class="card-text">System roles</p>
                    <div class="mt-auto">
                        <a href="/rbac_system/pages/admin/manage_roles.php" class="btn btn-success btn-sm">Manage Roles</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Projects</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <h2 class="display-4 mb-0"><?php echo $projectStats['total']; ?></h2>
                    <p class="card-text"><?php echo $projectStats['active']; ?> active, <?php echo $projectStats['completed']; ?> completed</p>
                    <div class="mt-auto">
                        <a href="/rbac_system/projects/index.php" class="btn btn-info btn-sm">Manage Projects</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">Tasks</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <h2 class="display-4 mb-0"><?php echo $taskStats['total']; ?></h2>
                    <p class="card-text"><?php echo $taskStats['pending']; ?> pending, <?php echo $taskStats['in_progress']; ?> in progress, <?php echo $taskStats['completed']; ?> completed</p>
                    <div class="mt-auto">
                        <a href="/rbac_system/tasks/index.php" class="btn btn-warning btn-sm text-white">View Tasks</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Users</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recentUsers = queryData('users', '*', [], 'created_at.desc', 5);
                            foreach ($recentUsers as $user): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Tasks</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recentTasks = queryData('tasks', '*', [], 'created_at.desc', 5);
                            foreach ($recentTasks as $task): 
                            ?>
                            <tr>
                                <td>
                                    <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo getStatusBadge($task['status']); ?></td>
                                <td><?php echo formatDate($task['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once __DIR__ . '/../../includes/footer.php'; ?>


<script>
$(document).ready(function() {
    // Fix modal glitching issues
    $('.modal').on('shown.bs.modal', function() {
        // Ensure modal has proper z-index
        $(this).css('z-index', 1050);
        // Prevent event propagation issues
        $(this).find('.modal-content').on('mouseenter mouseleave', function(e) {
            e.stopPropagation();
        });
    });
    
    // Improve modal behavior for role assignment
    $('#assignRoleModal').on('show.bs.modal', function (e) {
        // Reset form fields when modal opens
        $(this).find('form')[0].reset();
        // Prevent modal from closing when clicking inside
        $(this).find('.modal-content').on('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Ensure buttons work properly
    $('.btn-assign-role').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Your existing role assignment logic
    });
});
</script>
