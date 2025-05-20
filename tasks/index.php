<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

// Check if user has permission to view tasks
requireAnyRole(['administrator', 'project_manager', 'team_leader', 'team_member']);

$currentUser = getCurrentUser();
$userRoles = getUserRoles();

// Get filter parameters
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
$priority = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : null;

// Get all projects for filter dropdown
$projects = getAllProjects();

// Get tasks based on user role and filters
$tasks = [];
$filters = [];

if ($projectId) {
    $filters['project_id'] = 'eq.' . $projectId;
}

if ($status) {
    $filters['status'] = 'eq.' . $status;
}

if ($priority) {
    $filters['priority'] = 'eq.' . $priority;
}

// Different users see different tasks based on their role
if (hasRole('administrator') || hasRole('project_manager')) {
    // Admins and project managers can see all tasks
    $tasks = queryData('tasks', '*', $filters, 'created_at.desc');
} elseif (hasRole('team_leader')) {
    // Team leaders see tasks they created or tasks assigned to their team members
    // This is simplified - in a real app, you'd have team assignments
    $tasksCreated = getTasksByCreatedUser(getCurrentUserId());
    $tasksAssigned = getTasksByAssignedUser(getCurrentUserId());
    $tasks = array_merge($tasksCreated, $tasksAssigned);
    
    // Apply filters manually since we're merging
    if (!empty($filters)) {
        $tasks = array_filter($tasks, function($task) use ($filters) {
            foreach ($filters as $key => $value) {
                $value = str_replace('eq.', '', $value);
                if ($task[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }
} else {
    // Team members only see tasks assigned to them
    $tasks = getTasksByAssignedUser(getCurrentUserId());
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="tasks-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Tasks</h2>
        <?php if (hasAnyRole(['administrator', 'project_manager', 'team_leader'])): ?>
        <a href="/rbac_system/tasks/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Task
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter Tasks</h5>
        </div>
        <div class="card-body">
            <form id="task-filter-form" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="project-filter" class="form-label">Project</label>
                    <select class="form-select" id="project-filter" name="project_id">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo ($projectId == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status-filter" class="form-label">Status</label>
                    <select class="form-select" id="status-filter" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo ($status == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="review" <?php echo ($status == 'review') ? 'selected' : ''; ?>>Review</option>
                        <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="priority-filter" class="form-label">Priority</label>
                    <select class="form-select" id="priority-filter" name="priority">
                        <option value="">All Priorities</option>
                        <option value="low" <?php echo ($priority == 'low') ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo ($priority == 'medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo ($priority == 'high') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="/rbac_system/tasks/index.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (empty($tasks)): ?>
    <div class="alert alert-info">
        No tasks found. <?php if (hasAnyRole(['administrator', 'project_manager', 'team_leader'])): ?>
        <a href="/rbac_system/tasks/create.php">Create a new task</a>.
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Deadline</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <?php 
                    $project = getProjectById($task['project_id']);
                    $assignedUser = $task['assigned_to'] ? getUserById($task['assigned_to']) : null;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                    <td>
                        <?php if (hasAnyRole(['administrator', 'project_manager', 'team_leader']) || $task['assigned_to'] == getCurrentUserId()): ?>
                        <select class="form-select form-select-sm task-status-select" data-task-id="<?php echo $task['id']; ?>">
                            <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="review" <?php echo ($task['status'] == 'review') ? 'selected' : ''; ?>>Review</option>
                            <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($task['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <?php else: ?>
                        <?php echo getStatusBadge($task['status']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo getPriorityBadge($task['priority']); ?></td>
                    <td><?php echo $task['deadline'] ? formatDate($task['deadline']) : 'No deadline'; ?></td>
                    <td>
                        <?php if ($assignedUser): ?>
                        <?php echo htmlspecialchars($assignedUser['first_name'] . ' ' . $assignedUser['last_name']); ?>
                        <?php else: ?>
                        <span class="text-muted">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if (hasAnyRole(['administrator', 'project_manager']) || $task['created_by'] == getCurrentUserId()): ?>
                        <a href="/rbac_system/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <a href="/rbac_system/tasks/delete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger delete-task-btn">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>