<?php
require_once __DIR__ . '/../config/database.php';

// Display error message
function displayError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

// Display success message
function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

// Get all roles
function getAllRoles() {
    return queryData('roles', '*', [], 'name.asc');
}

// Get all users
function getAllUsers() {
    return queryData('users', '*', [], 'created_at.desc');
}

// Get user by ID
function getUserById($userId) {
    $users = queryData('users', '*', ['id' => 'eq.' . $userId]);
    
    if ($users && count($users) > 0) {
        return $users[0];
    }
    
    return null;
}

// Get role by ID
function getRoleById($roleId) {
    $roles = queryData('roles', '*', ['id' => 'eq.' . $roleId]);
    
    if ($roles && count($roles) > 0) {
        return $roles[0];
    }
    
    return null;
}

// Assign role to user
function assignRoleToUser($userId, $roleId) {
    $data = [
        'user_id' => $userId,
        'role_id' => $roleId
    ];
    
    return insertData('user_roles', $data);
}

// Remove role from user
function removeRoleFromUser($userId, $roleId) {
    $filters = [
        'user_id' => 'eq.' . $userId,
        'role_id' => 'eq.' . $roleId
    ];
    
    return deleteData('user_roles', $filters);
}

// Get all projects
function getAllProjects() {
    return queryData('projects', '*', [], 'created_at.desc');
}

// Get project by ID
function getProjectById($projectId) {
    $projects = queryData('projects', '*', ['id' => 'eq.' . $projectId]);
    
    if ($projects && count($projects) > 0) {
        return $projects[0];
    }
    
    return null;
}

// Get tasks by project ID
function getTasksByProject($projectId) {
    return queryData('tasks', '*', ['project_id' => 'eq.' . $projectId], 'created_at.desc');
}

// Get tasks by assigned user
function getTasksByAssignedUser($userId) {
    return queryData('tasks', '*', ['assigned_to' => 'eq.' . $userId], 'created_at.desc');
}

// Get tasks by created user
function getTasksByCreatedUser($userId) {
    return queryData('tasks', '*', ['created_by' => 'eq.' . $userId], 'created_at.desc');
}

// Get task by ID
function getTaskById($taskId) {
    $tasks = queryData('tasks', '*', ['id' => 'eq.' . $taskId]);
    
    if ($tasks && count($tasks) > 0) {
        return $tasks[0];
    }
    
    return null;
}

// Get task comments
function getTaskComments($taskId) {
    return queryData('task_comments', '*', ['task_id' => 'eq.' . $taskId], 'created_at.asc');
}

// Format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M j, Y g:i A');
}

// Get status badge HTML
function getStatusBadge($status) {
    $statusClass = 'status-' . str_replace('_', '-', $status);
    $statusLabel = ucwords(str_replace('_', ' ', $status));
    
    return '<span class="' . $statusClass . '">' . $statusLabel . '</span>';
}

// Get priority badge HTML
function getPriorityBadge($priority) {
    $priorityClass = 'priority-' . $priority;
    $priorityLabel = ucfirst($priority);
    
    return '<span class="' . $priorityClass . '">' . $priorityLabel . '</span>';
}

// Get CSS class for priority badge
function getPriorityClass($priority) {
    switch ($priority) {
        case 'high':
            return 'bg-danger';
        case 'medium':
            return 'bg-warning';
        case 'low':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim(htmlspecialchars($input));
    }
    
    return $input;
}

// Get project name by ID
function getProjectName($projectId) {
    $project = getProjectById($projectId);
    return $project ? $project['name'] : 'Unknown Project';
}
?>