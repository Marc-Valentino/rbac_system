<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

// Check if task ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: /rbac_system/tasks/index.php?error=Task ID is required');
    exit;
}

$taskId = intval($_GET['id']);
$task = getTaskById($taskId);

// Check if task exists
if (!$task) {
    header('Location: /rbac_system/tasks/index.php?error=Task not found');
    exit;
}

// Check if user has permission to edit this task
$isAdmin = hasRole('administrator');
$isProjectManager = hasRole('project_manager');
$isTaskCreator = $task['created_by'] == getCurrentUserId();
$isTeamLeader = hasRole('team_leader');

if (!$isAdmin && !$isProjectManager && !$isTaskCreator && !$isTeamLeader) {
    header('Location: /rbac_system/tasks/index.php?error=You do not have permission to edit this task');
    exit;
}

$currentUser = getCurrentUser();
$error = '';
$success = '';

// Get all projects for dropdown
$projects = getAllProjects();

// Get all users for assignment dropdown
$users = getAllUsers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'pending');
    $priority = sanitizeInput($_POST['priority'] ?? 'medium');
    $deadline = sanitizeInput($_POST['deadline'] ?? '');
    $assignedTo = !empty($_POST['assigned_to']) ? sanitizeInput($_POST['assigned_to']) : null;
    
    if (empty($projectId) || empty($title)) {
        $error = 'Project and title are required fields.';
    } else {
        $taskData = [
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'updated_at' => 'now()'
        ];
        
        if (!empty($deadline)) {
            $taskData['deadline'] = $deadline;
        } else {
            $taskData['deadline'] = null;
        }
        
        if (!empty($assignedTo)) {
            $taskData['assigned_to'] = $assignedTo;
        } else {
            $taskData['assigned_to'] = null;
        }
        
        $result = updateData('tasks', $taskData, ['id' => 'eq.' . $taskId]);
        
        if ($result !== null) {
            $success = 'Task updated successfully!';
            // Refresh task data
            $task = getTaskById($taskId);
        } else {
            $error = 'Failed to update task. Please try again.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="task-form-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Task</h2>
        <div>
            <a href="/rbac_system/tasks/view.php?id=<?php echo $taskId; ?>" class="btn btn-info me-2">
                <i class="fas fa-eye"></i> View Task
            </a>
            <a href="/rbac_system/tasks/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Tasks
            </a>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <?php echo displayError($error); ?>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <?php echo displaySuccess($success); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="project_id" class="form-label">Project *</label>
                    <select class="form-select" id="project_id" name="project_id" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo ($task['project_id'] == $project['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="review" <?php echo ($task['status'] == 'review') ? 'selected' : ''; ?>>Review</option>
                            <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($task['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low" <?php echo ($task['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($task['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($task['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="datetime-local" class="form-control" id="task-deadline" name="deadline" 
                               value="<?php echo $task['deadline'] ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="assigned_to" class="form-label">Assign To</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($task['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>