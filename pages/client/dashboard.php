<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require login
requireLogin();

// Require client role
requireRole('client');

$currentUser = getCurrentUser();

// Get client projects
$clientProjects = queryData('projects', '*', ['client_id' => 'eq.' . getCurrentUserId()], 'created_at.desc');

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="client-dashboard">
    <h2>Client Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Your Projects</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($clientProjects)): ?>
                        <div class="alert alert-info">
                            You don't have any projects yet.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Tasks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientProjects as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                    <td>
                                        <?php if ($project['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($project['status'] === 'completed'): ?>
                                            <span class="badge bg-secondary">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($project['created_at']); ?></td>
                                    <td>
                                        <?php 
                                        $projectTasks = queryData('tasks', '*', ['project_id' => 'eq.' . $project['id']]);
                                        echo count($projectTasks); 
                                        ?>
                                    </td>
                                    <td>
                                        <a href="/rbac_system/tasks/index.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-tasks"></i> View Tasks
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
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Task Updates</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get tasks from client's projects
                    $projectIds = array_map(function($project) {
                        return $project['id'];
                    }, $clientProjects);
                    
                    $recentTasks = [];
                    if (!empty($projectIds)) {
                        $projectIdList = implode(',', $projectIds);
                        $recentTasks = queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')'], 'updated_at.desc', 10);
                    }
                    
                    if (empty($recentTasks)):
                    ?>
                        <div class="alert alert-info">
                            No recent task updates.
                        </div>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTasks as $task): 
                                    $project = getProjectById($task['project_id']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                    <td><?php echo getStatusBadge($task['status']); ?></td>
                                    <td><?php echo formatDate($task['updated_at']); ?></td>
                                    <td>
                                        <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
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

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>