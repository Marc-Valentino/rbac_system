<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require login
requireLogin();

// Require team_member role
requireRole('team_member');

$currentUser = getCurrentUser();
$userId = getCurrentUserId();

// Get assigned tasks
$assignedTasks = queryData(
    'tasks', 
    '*', 
    ['assigned_to' => 'eq.' . $userId], 
    'deadline.asc,priority.desc'
);

// Get task statistics
$taskStats = [
    'total' => count($assignedTasks),
    'pending' => 0,
    'in_progress' => 0,
    'review' => 0,
    'completed' => 0,
    'cancelled' => 0
];

// Calculate task statistics
foreach ($assignedTasks as $task) {
    $taskStats[$task['status']]++;
}

// Get projects the user is involved in
$projectIds = [];
foreach ($assignedTasks as $task) {
    if (!in_array($task['project_id'], $projectIds)) {
        $projectIds[] = $task['project_id'];
    }
}

$projects = [];
foreach ($projectIds as $projectId) {
    $project = getProjectById($projectId);
    if ($project) {
        $projects[] = $project;
    }
}

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="team-member-dashboard">
    <h2>Team Member Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</p>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Assigned Tasks</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['total']; ?></h5>
                    <p class="card-text">Total tasks assigned to you</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">In Progress</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['in_progress']; ?></h5>
                    <p class="card-text">Tasks you're currently working on</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">In Review</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['review']; ?></h5>
                    <p class="card-text">Tasks awaiting review</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Completed</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $taskStats['completed']; ?></h5>
                    <p class="card-text">Tasks you've completed</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Your Tasks</h4>
                    <a href="/rbac_system/tasks/index.php" class="btn btn-outline-primary">View All Tasks</a>
                </div>
                <div class="card-body">
                    <?php if (empty($assignedTasks)): ?>
                        <div class="alert alert-info">
                            You don't have any tasks assigned to you yet.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Deadline</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedTasks as $task): ?>
                                        <?php 
                                            $project = getProjectById($task['project_id']);
                                            $deadlineClass = '';
                                            
                                            if ($task['deadline']) {
                                                $deadline = new DateTime($task['deadline']);
                                                $today = new DateTime();
                                                $diff = $today->diff($deadline);
                                                
                                                if ($deadline < $today && $task['status'] != 'completed' && $task['status'] != 'cancelled') {
                                                    $deadlineClass = 'text-danger fw-bold';
                                                } elseif ($diff->days <= 2 && $task['status'] != 'completed' && $task['status'] != 'cancelled') {
                                                    $deadlineClass = 'text-warning fw-bold';
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['title']); ?></td>
                                            <td><?php echo htmlspecialchars($project['name']); ?></td>
                                            <td><?php echo getStatusBadge($task['status']); ?></td>
                                            <td><?php echo getPriorityBadge($task['priority']); ?></td>
                                            <td class="<?php echo $deadlineClass; ?>">
                                                <?php if ($task['deadline']): ?>
                                                    <?php echo formatDate($task['deadline']); ?>
                                                <?php else: ?>
                                                    <em>No deadline</em>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($task['status'] != 'completed' && $task['status'] != 'cancelled'): ?>
                                                        <button type="button" class="btn btn-sm btn-success update-status-btn" 
                                                                data-task-id="<?php echo $task['id']; ?>" 
                                                                data-current-status="<?php echo $task['status']; ?>">
                                                            <i class="fas fa-check"></i> Update
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Task Progress</h4>
                </div>
                <div class="card-body">
                    <?php if ($taskStats['total'] > 0): ?>
                        <canvas id="taskStatusChart" width="400" height="300"></canvas>
                        
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const ctx = document.getElementById('taskStatusChart').getContext('2d');
                                const taskStatusChart = new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['Pending', 'In Progress', 'Review', 'Completed', 'Cancelled'],
                                        datasets: [{
                                            label: 'Task Status',
                                            data: [
                                                <?php echo $taskStats['pending']; ?>,
                                                <?php echo $taskStats['in_progress']; ?>,
                                                <?php echo $taskStats['review']; ?>,
                                                <?php echo $taskStats['completed']; ?>,
                                                <?php echo $taskStats['cancelled']; ?>
                                            ],
                                            backgroundColor: [
                                                '#6c757d',  // Pending - gray
                                                '#ffc107',  // In Progress - yellow
                                                '#17a2b8',  // Review - cyan
                                                '#28a745',  // Completed - green
                                                '#dc3545'   // Cancelled - red
                                            ],
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                            }
                                        }
                                    }
                                });
                            });
                        </script>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No task data available to display chart.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4>Your Projects</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($projects)): ?>
                        <div class="alert alert-info">
                            You're not assigned to any projects yet.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($projects as $project): ?>
                                <a href="/rbac_system/tasks/index.php?project_id=<?php echo $project['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($project['name']); ?></h5>
                                        <?php if ($project['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1">
                                        <?php 
                                            $projectTasks = queryData('tasks', '*', [
                                                'project_id' => 'eq.' . $project['id'],
                                                'assigned_to' => 'eq.' . $userId
                                            ]);
                                            $completedTasks = 0;
                                            foreach ($projectTasks as $task) {
                                                if ($task['status'] == 'completed') {
                                                    $completedTasks++;
                                                }
                                            }
                                            $totalTasks = count($projectTasks);
                                            $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                                        ?>
                                        <div class="progress mt-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%;" 
                                                 aria-valuenow="<?php echo $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $progressPercentage; ?>%
                                            </div>
                                        </div>
                                    </p>
                                    <small>
                                        <?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> tasks completed
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Task Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="update-status-form">
                    <input type="hidden" id="task-id" name="task_id">
                    <div class="mb-3">
                        <label for="task-status" class="form-label">Status</label>
                        <select class="form-select" id="task-status" name="status">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Ready for Review</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-status">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status update button handler
    const updateStatusBtns = document.querySelectorAll('.update-status-btn');
    updateStatusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const currentStatus = this.dataset.currentStatus;
            
            // Set values in modal
            document.getElementById('task-id').value = taskId;
            document.getElementById('task-status').value = currentStatus;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        });
    });
    
    // Save status button handler
    document.getElementById('save-status').addEventListener('click', function() {
        const taskId = document.getElementById('task-id').value;
        const newStatus = document.getElementById('task-status').value;
        
        // Send AJAX request to update task status
        fetch('/rbac_system/tasks/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `task_id=${taskId}&status=${newStatus}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated status
                window.location.reload();
            } else {
                alert('Failed to update task status. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>