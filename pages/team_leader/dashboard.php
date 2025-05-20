<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require team leader role
requireRole('team_leader');

$currentUser = getCurrentUser();
$userRoles = getUserRoles();

// Get team members (users with team_member role)
$teamMembers = [];
$allUsers = getAllUsers();
foreach ($allUsers as $user) {
    $userRoleData = getUserRoles($user['id']);
    $userRoleNames = array_map(function($role) {
        return $role['name'];
    }, $userRoleData);
    
    if (in_array('team_member', $userRoleNames)) {
        $user['roles'] = $userRoleData;
        $teamMembers[] = $user;
    }
}

// Get tasks assigned to team members
$teamTasks = [];
foreach ($teamMembers as $member) {
    $memberTasks = queryData('tasks', '*', ['assigned_to' => 'eq.' . $member['id']], 'created_at.desc');
    if ($memberTasks) {
        foreach ($memberTasks as $task) {
            $task['assignee'] = $member;
            $teamTasks[] = $task;
        }
    }
}

// Get tasks created by the team leader
$createdTasks = queryData('tasks', '*', ['created_by' => 'eq.' . getCurrentUserId()], 'created_at.desc');

// Get task statistics
$taskStats = [
    'total' => count($teamTasks),
    'pending' => count(array_filter($teamTasks, function($task) { return $task['status'] == 'pending'; })),
    'in_progress' => count(array_filter($teamTasks, function($task) { return $task['status'] == 'in_progress'; })),
    'review' => count(array_filter($teamTasks, function($task) { return $task['status'] == 'review'; })),
    'completed' => count(array_filter($teamTasks, function($task) { return $task['status'] == 'completed'; })),
];

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="team-leader-dashboard">
    <h2>Team Leader Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Team Members</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo count($teamMembers); ?></h5>
                    <p class="card-text">Total team members</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Tasks</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['total']; ?></h5>
                    <p class="card-text">Team tasks</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">In Progress</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['in_progress']; ?></h5>
                    <p class="card-text">Tasks in progress</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Pending Review</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['review']; ?></h5>
                    <p class="card-text">Tasks awaiting review</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Team Members</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($teamMembers)): ?>
                        <p class="text-muted">No team members found.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Tasks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamMembers as $member): ?>
                                    <?php 
                                    $memberTaskCount = count(array_filter($teamTasks, function($task) use ($member) {
                                        return $task['assigned_to'] == $member['id'];
                                    }));
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo $memberTaskCount; ?></td>
                                        <td>
                                            <a href="/rbac_system/tasks/create.php?assign_to=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus"></i> Assign Task
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Recent Team Tasks</h5>
                    <a href="/rbac_system/tasks/create.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Create Task
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($teamTasks)): ?>
                        <p class="text-muted">No tasks found for team members.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Sort tasks by created_at date (newest first)
                                usort($teamTasks, function($a, $b) {
                                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                                });
                                
                                // Display only the 5 most recent tasks
                                $recentTasks = array_slice($teamTasks, 0, 5);
                                
                                foreach ($recentTasks as $task): 
                                ?>
                                    <tr>
                                        <td>
                                            <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (isset($task['assignee'])): ?>
                                                <?php echo htmlspecialchars($task['assignee']['first_name'] . ' ' . $task['assignee']['last_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo getStatusBadge($task['status']); ?></td>
                                        <td>
                                            <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/rbac_system/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="mt-2 text-end">
                            <a href="/rbac_system/tasks/index.php" class="btn btn-link">View all tasks</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Tasks Created by You</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($createdTasks)): ?>
                        <p class="text-muted">You haven't created any tasks yet.</p>
                        <a href="/rbac_system/tasks/create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Task
                        </a>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($createdTasks as $task): ?>
                                    <?php $assignedUser = $task['assigned_to'] ? getUserById($task['assigned_to']) : null; ?>
                                    <tr>
                                        <td>
                                            <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($assignedUser): ?>
                                                <?php echo htmlspecialchars($assignedUser['first_name'] . ' ' . $assignedUser['last_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo getStatusBadge($task['status']); ?></td>
                                        <td><?php echo getPriorityBadge($task['priority']); ?></td>
                                        <td><?php echo $task['deadline'] ? formatDate($task['deadline']) : 'No deadline'; ?></td>
                                        <td>
                                            <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/rbac_system/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/rbac_system/tasks/delete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger delete-task-btn">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirm task deletion
    const deleteBtns = document.querySelectorAll('.delete-task-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>