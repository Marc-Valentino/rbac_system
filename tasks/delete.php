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

// Check if user has permission to delete this task
// Only administrators, project managers, or the task creator can delete tasks
if (!hasAnyRole(['administrator', 'project_manager']) && $task['created_by'] != getCurrentUserId()) {
    header('Location: /rbac_system/tasks/index.php?error=You do not have permission to delete this task');
    exit;
}

// Delete task comments first (to maintain referential integrity)
$commentFilters = ['task_id' => 'eq.' . $taskId];
deleteData('task_comments', $commentFilters);

// Delete the task
$taskFilters = ['id' => 'eq.' . $taskId];
$result = deleteData('tasks', $taskFilters);

if ($result !== null) {
    // Success
    header('Location: /rbac_system/tasks/index.php?success=Task deleted successfully');
    exit;
} else {
    // Error
    header('Location: /rbac_system/tasks/index.php?error=Failed to delete task');
    exit;
}
?>