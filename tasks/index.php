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
        <h2 class="text-primary"><i class="fas fa-tasks me-2"></i>Tasks</h2>
        
        <?php if (hasAnyRole(['administrator', 'project_manager', 'team_leader'])): ?>
        <a href="/rbac_system/tasks/create.php" class="btn btn-primary rounded-pill shadow-sm">
            <i class="fas fa-plus me-2"></i> Create Task
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Filter Form -->
    <div class="card shadow-sm mb-4 border-0 rounded-3">
        <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 text-primary"><i class="fas fa-filter me-2"></i>Filter Tasks</h5>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body bg-white">
                <form id="task-filter-form" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="project-filter" class="form-label fw-medium">Project</label>
                        <select class="form-select border-0 bg-light" id="project-filter" name="project_id">
                            <option value="">All Projects</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo ($projectId == $project['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status-filter" class="form-label fw-medium">Status</label>
                        <select class="form-select border-0 bg-light" id="status-filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo ($status == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="review" <?php echo ($status == 'review') ? 'selected' : ''; ?>>Review</option>
                            <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="priority-filter" class="form-label fw-medium">Priority</label>
                        <select class="form-select border-0 bg-light" id="priority-filter" name="priority">
                            <option value="">All Priorities</option>
                            <option value="low" <?php echo ($priority == 'low') ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo ($priority == 'medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo ($priority == 'high') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-grid w-100">
                            <button type="submit" class="btn btn-primary rounded-pill shadow-sm">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (empty($tasks)): ?>
    <div class="alert alert-info bg-light border-0 shadow-sm rounded-3">
        <i class="fas fa-info-circle me-2"></i>No tasks found matching your criteria.
    </div>
    <?php else: ?>
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Title</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Priority</th>
                            <th class="border-0">Deadline</th>
                            <th class="border-0">Assigned To</th>
                            <th class="border-0">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <?php $assignedUser = $task['assigned_to'] ? getUserById($task['assigned_to']) : null; ?>
                        <tr class="align-middle">
                            <td>
                                <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="fw-medium text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <div class="small text-muted">
                                    <i class="fas fa-project-diagram me-1"></i> <?php echo getProjectName($task['project_id']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if (hasAnyRole(['administrator', 'project_manager', 'team_leader']) || $task['assigned_to'] == getCurrentUserId()): ?>
                                <select class="form-select form-select-sm task-status-select border-0 bg-light rounded-pill" data-task-id="<?php echo $task['id']; ?>">
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
                            <td>
                                <span class="badge rounded-pill <?php echo getPriorityClass($task['priority']); ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task['deadline']): ?>
                                    <?php 
                                        $deadline = new DateTime($task['deadline']);
                                        $today = new DateTime();
                                        $diff = $today->diff($deadline);
                                        $deadlineClass = '';
                                        
                                        if ($deadline < $today && $task['status'] != 'completed' && $task['status'] != 'cancelled') {
                                            $deadlineClass = 'text-danger fw-bold';
                                        } elseif ($diff->days <= 2 && $task['status'] != 'completed' && $task['status'] != 'cancelled') {
                                            $deadlineClass = 'text-warning fw-bold';
                                        }
                                    ?>
                                    <span class="<?php echo $deadlineClass; ?>">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo formatDate($task['deadline']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-infinity me-1"></i> No deadline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($assignedUser): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2 bg-primary text-white">
                                            <?php echo strtoupper(substr($assignedUser['first_name'], 0, 1) . substr($assignedUser['last_name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($assignedUser['first_name'] . ' ' . $assignedUser['last_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-user-slash me-1"></i> Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasAnyRole(['administrator', 'project_manager']) || ($task['created_by'] == getCurrentUserId())): ?>
                                    <a href="/rbac_system/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-1" data-bs-toggle="tooltip" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill delete-task-btn" data-task-id="<?php echo $task['id']; ?>" data-bs-toggle="tooltip" title="Delete Task">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add this CSS to your style.css file -->
<style>
    .navbar {
        background-color: #4e73df;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2e59d9;
    }
    
    .text-primary {
        color: #4e73df !important;
    }
    
    .avatar-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    .form-select, .form-control {
        border-radius: 10px;
        padding: 0.6rem 1rem;
        transition: all 0.2s;
    }
    
    .form-select:focus, .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        border-color: #bac8f3;
    }
    
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        font-weight: 600;
        color: #5a5c69;
        border-bottom: 2px solid #e3e6f0;
    }
    
    .table tbody tr {
        border-bottom: 1px solid #e3e6f0;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    /* Custom status badge colors */
    .badge-pending {
        background-color: #f6c23e;
        color: #fff;
    }
    
    .badge-in-progress {
        background-color: #36b9cc;
        color: #fff;
    }
    
    .badge-review {
        background-color: #4e73df;
        color: #fff;
    }
    
    .badge-completed {
        background-color: #1cc88a;
        color: #fff;
    }
    
    .badge-cancelled {
        background-color: #e74a3b;
        color: #fff;
    }
    
    /* Custom priority badge colors */
    .priority-low {
        background-color: #1cc88a;
    }
    
    .priority-medium {
        background-color: #f6c23e;
    }
    
    .priority-high {
        background-color: #e74a3b;
    }
</style>

<!-- Add this JavaScript to handle status updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle task status changes
    const statusSelects = document.querySelectorAll('.task-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const newStatus = this.value;
            
            // Show loading indicator
            this.classList.add('bg-light');
            this.disabled = true;
            
            // Send AJAX request to update status
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
                    // Show success notification
                    const toast = document.createElement('div');
                    toast.className = 'position-fixed bottom-0 end-0 p-3';
                    toast.style.zIndex = '11';
                    toast.innerHTML = `
                        <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-check-circle me-2"></i> Task status updated successfully!
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    const toastEl = new bootstrap.Toast(toast.querySelector('.toast'));
                    toastEl.show();
                    
                    // Remove toast after it's hidden
                    toast.addEventListener('hidden.bs.toast', function() {
                        document.body.removeChild(toast);
                    });
                } else {
                    alert('Failed to update task status. Please try again.');
                }
                
                // Remove loading indicator
                this.classList.remove('bg-light');
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                this.classList.remove('bg-light');
                this.disabled = false;
            });
        });
    });
    
    // Handle task deletion
    const deleteButtons = document.querySelectorAll('.delete-task-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                // Send AJAX request to delete task
                fetch('/rbac_system/tasks/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        this.closest('tr').remove();
                        
                        // Show success notification
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '11';
                        toast.innerHTML = `
                            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-check-circle me-2"></i> Task deleted successfully!
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        const toastEl = new bootstrap.Toast(toast.querySelector('.toast'));
                        toastEl.show();
                        
                        // Remove toast after it's hidden
                        toast.addEventListener('hidden.bs.toast', function() {
                            document.body.removeChild(toast);
                        });
                        
                        // If no tasks left, show the "no tasks" message
                        const tbody = document.querySelector('tbody');
                        if (tbody.children.length === 0) {
                            const tasksContainer = document.querySelector('.tasks-container');
                            const cardElement = document.querySelector('.card');
                            
                            const noTasksAlert = document.createElement('div');
                            noTasksAlert.className = 'alert alert-info bg-light border-0 shadow-sm rounded-3';
                            noTasksAlert.innerHTML = '<i class="fas fa-info-circle me-2"></i>No tasks found matching your criteria.';
                            
                            tasksContainer.replaceChild(noTasksAlert, cardElement);
                        }
                    } else {
                        alert('Failed to delete task. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>