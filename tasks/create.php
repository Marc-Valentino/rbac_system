<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require login
requireLogin();

// Only administrators, project managers, and team leaders can create tasks
requireAnyRole(['administrator', 'project_manager', 'team_leader']);

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
            'created_by' => getCurrentUserId()
        ];
        
        if (!empty($deadline)) {
            $taskData['deadline'] = $deadline;
        }
        
        if (!empty($assignedTo)) {
            $taskData['assigned_to'] = $assignedTo;
        }
        
        $result = insertData('tasks', $taskData);
        
        if ($result !== null) {
            $success = 'Task created successfully!';
            // Redirect to task list after short delay
            header('refresh:2;url=/rbac_system/tasks/index.php');
        } else {
            $error = 'Failed to create task. Please try again.';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="task-form-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New Task</h2>
        <a href="/rbac_system/tasks/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Tasks
        </a>
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
                        <option value="<?php echo $project['id']; ?>">
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Title *</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" selected>Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="deadline" class="form-label">Deadline</label>
                        <input type="datetime-local" class="form-control" id="task-deadline" name="deadline">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="assigned_to" class="form-label">Assign To</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>