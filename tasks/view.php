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
 
 // Get related data 
 $project = getProjectById($task['project_id']); 
 $creator = $task['created_by'] ? getUserById($task['created_by']) : null; 
 $assignee = $task['assigned_to'] ? getUserById($task['assigned_to']) : null; 
 $comments = getTaskComments($taskId); 
 
 $currentUser = getCurrentUser(); 
 $error = ''; 
 $success = ''; 
 
 // Handle comment submission 
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) { 
     $comment = sanitizeInput($_POST['comment']); 
     
     if (empty($comment)) { 
         $error = 'Comment cannot be empty.'; 
     } else { 
         $commentData = [ 
             'task_id' => $taskId, 
             'user_id' => getCurrentUserId(), 
             'comment' => $comment 
         ]; 
         
         $result = insertData('task_comments', $commentData); 
         
         if ($result !== null) { 
             $success = 'Comment added successfully!'; 
             // Refresh comments 
             $comments = getTaskComments($taskId); 
         } else { 
             $error = 'Failed to add comment. Please try again.'; 
         } 
     } 
 } 
 
 include_once __DIR__ . '/../includes/header.php'; 
 ?> 
 
 <div class="task-view-container"> 
     <div class="d-flex justify-content-between align-items-center mb-4"> 
         <h2>Task Details</h2> 
         <div> 
             <?php if (hasAnyRole(['administrator', 'project_manager']) || $task['created_by'] == getCurrentUserId()): ?> 
             <a href="/rbac_system/tasks/edit.php?id=<?php echo $taskId; ?>" class="btn btn-warning me-2"> 
                 <i class="fas fa-edit"></i> Edit Task 
             </a> 
             <?php endif; ?> 
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
     
     <div class="row"> 
         <div class="col-md-8"> 
             <div class="card mb-4"> 
                 <div class="card-header"> 
                     <h3><?php echo htmlspecialchars($task['title']); ?></h3> 
                 </div> 
                 <div class="card-body"> 
                     <h5>Description</h5> 
                     <p class="task-description"> 
                         <?php if (!empty($task['description'])): ?> 
                             <?php echo nl2br(htmlspecialchars($task['description'])); ?> 
                         <?php else: ?> 
                             <em>No description provided</em> 
                         <?php endif; ?> 
                     </p> 
                     
                     <div class="row mt-4"> 
                         <div class="col-md-6"> 
                             <h5>Project</h5> 
                             <p><?php echo htmlspecialchars($project['name']); ?></p> 
                             
                             <h5>Status</h5> 
                             <p><?php echo getStatusBadge($task['status']); ?></p> 
                             
                             <h5>Priority</h5> 
                             <p><?php echo getPriorityBadge($task['priority']); ?></p> 
                         </div> 
                         <div class="col-md-6"> 
                             <h5>Created By</h5> 
                             <p> 
                                 <?php if ($creator): ?> 
                                     <?php echo htmlspecialchars($creator['first_name'] . ' ' . $creator['last_name']); ?> 
                                 <?php else: ?> 
                                     <em>Unknown</em> 
                                 <?php endif; ?> 
                             </p> 
                             
                             <h5>Assigned To</h5> 
                             <p> 
                                 <?php if ($assignee): ?> 
                                     <?php echo htmlspecialchars($assignee['first_name'] . ' ' . $assignee['last_name']); ?> 
                                 <?php else: ?> 
                                     <em>Unassigned</em> 
                                 <?php endif; ?> 
                             </p> 
                             
                             <h5>Deadline</h5> 
                             <p> 
                                 <?php if ($task['deadline']): ?> 
                                     <?php echo formatDate($task['deadline']); ?> 
                                 <?php else: ?> 
                                     <em>No deadline</em> 
                                 <?php endif; ?> 
                             </p> 
                         </div> 
                     </div> 
                 </div> 
                 <div class="card-footer text-muted"> 
                     <div class="row"> 
                         <div class="col-md-6"> 
                             Created: <?php echo formatDate($task['created_at']); ?> 
                         </div> 
                         <div class="col-md-6 text-end"> 
                             Last Updated: <?php echo formatDate($task['updated_at']); ?> 
                         </div> 
                     </div> 
                 </div>
             </div>
             
             <!-- Task Comments Section -->
             <div class="card">
                 <div class="card-header">
                     <h4>Comments</h4>
                 </div>
                 <div class="card-body">
                     <?php if (empty($comments)): ?>
                         <p class="text-muted">No comments yet.</p>
                     <?php else: ?>
                         <div class="comments-list">
                             <?php foreach ($comments as $comment): ?>
                                 <?php $commentUser = getUserById($comment['user_id']); ?>
                                 <div class="comment-item mb-3">
                                     <div class="comment-header d-flex justify-content-between">
                                         <div>
                                             <strong>
                                                 <?php if ($commentUser): ?>
                                                     <?php echo htmlspecialchars($commentUser['first_name'] . ' ' . $commentUser['last_name']); ?>
                                                 <?php else: ?>
                                                     Unknown User
                                                 <?php endif; ?>
                                             </strong>
                                             <span class="text-muted ms-2">
                                                 <?php echo formatDate($comment['created_at']); ?>
                                             </span>
                                         </div>
                                         <?php if ($comment['user_id'] == getCurrentUserId() || hasRole('administrator')): ?>
                                             <div>
                                                 <a href="#" class="text-danger delete-comment" data-comment-id="<?php echo $comment['id']; ?>">
                                                     <i class="fas fa-trash-alt"></i>
                                                 </a>
                                             </div>
                                         <?php endif; ?>
                                     </div>
                                     <div class="comment-body mt-2">
                                         <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                     </div>
                                 </div>
                                 <hr>
                             <?php endforeach; ?>
                         </div>
                     <?php endif; ?>
                     
                     <!-- Add Comment Form -->
                     <form method="POST" action="" class="mt-4">
                         <div class="mb-3">
                             <label for="comment" class="form-label">Add a Comment</label>
                             <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                         </div>
                         <button type="submit" class="btn btn-primary">Submit Comment</button>
                     </form>
                 </div>
             </div>
         </div>
         
         <div class="col-md-4">
             <!-- Task Actions Card -->
             <div class="card mb-4">
                 <div class="card-header">
                     <h4>Task Actions</h4>
                 </div>
                 <div class="card-body">
                     <?php if ($task['assigned_to'] == getCurrentUserId() || hasAnyRole(['administrator', 'project_manager', 'team_leader'])): ?>
                         <div class="mb-3">
                             <label for="status-update" class="form-label">Update Status</label>
                             <select class="form-select" id="status-update" data-task-id="<?php echo $taskId; ?>">
                                 <option value="pending" <?php echo ($task['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                 <option value="in_progress" <?php echo ($task['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                 <option value="review" <?php echo ($task['status'] == 'review') ? 'selected' : ''; ?>>Review</option>
                                 <option value="completed" <?php echo ($task['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                 <option value="cancelled" <?php echo ($task['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                             </select>
                         </div>
                     <?php endif; ?>
                     
                     <?php if (hasAnyRole(['administrator', 'project_manager'])): ?>
                         <div class="mb-3">
                             <label for="priority-update" class="form-label">Update Priority</label>
                             <select class="form-select" id="priority-update" data-task-id="<?php echo $taskId; ?>">
                                 <option value="low" <?php echo ($task['priority'] == 'low') ? 'selected' : ''; ?>>Low</option>
                                 <option value="medium" <?php echo ($task['priority'] == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                 <option value="high" <?php echo ($task['priority'] == 'high') ? 'selected' : ''; ?>>High</option>
                             </select>
                         </div>
                     <?php endif; ?>
                     
                     <div class="d-grid gap-2">
                         <?php if (hasAnyRole(['administrator', 'project_manager']) || $task['created_by'] == getCurrentUserId()): ?>
                             <a href="/rbac_system/tasks/edit.php?id=<?php echo $taskId; ?>" class="btn btn-warning">
                                 <i class="fas fa-edit"></i> Edit Task
                             </a>
                             
                             <a href="/rbac_system/tasks/delete.php?id=<?php echo $taskId; ?>" class="btn btn-danger delete-task-btn">
                                 <i class="fas fa-trash"></i> Delete Task
                             </a>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
             
             <!-- Related Tasks Card (placeholder for future enhancement) -->
             <div class="card">
                 <div class="card-header">
                     <h4>Project Information</h4>
                 </div>
                 <div class="card-body">
                     <h5><?php echo htmlspecialchars($project['name']); ?></h5>
                     <p>
                         <?php if (!empty($project['description'])): ?>
                             <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                         <?php else: ?>
                             <em>No project description available</em>
                         <?php endif; ?>
                     </p>
                     
                     <a href="/rbac_system/tasks/index.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-primary">
                         <i class="fas fa-tasks"></i> View All Tasks in This Project
                     </a>
                 </div>
             </div>
         </div>
     </div>
 </div>

<script>
// JavaScript for handling status and priority updates
document.addEventListener('DOMContentLoaded', function() {
    // Status update handler
    const statusUpdate = document.getElementById('status-update');
    if (statusUpdate) {
        statusUpdate.addEventListener('change', function() {
            updateTaskField(this.dataset.taskId, 'status', this.value);
        });
    }
    
    // Priority update handler
    const priorityUpdate = document.getElementById('priority-update');
    if (priorityUpdate) {
        priorityUpdate.addEventListener('change', function() {
            updateTaskField(this.dataset.taskId, 'priority', this.value);
        });
    }
    
    // Function to update task field via AJAX
    function updateTaskField(taskId, field, value) {
        // This is a placeholder - you would implement AJAX here
        // For now, we'll just reload the page
        alert('Task ' + field + ' updated to ' + value + '. Page will reload.');
        window.location.reload();
    }
    
    // Delete comment confirmation
    const deleteCommentLinks = document.querySelectorAll('.delete-comment');
    deleteCommentLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this comment?')) {
                // This is a placeholder - you would implement AJAX here
                alert('Comment deletion would be implemented here.');
            }
        });
    });
    
    // Delete task confirmation
    const deleteTaskBtn = document.querySelector('.delete-task-btn');
    if (deleteTaskBtn) {
        deleteTaskBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this task?')) {
                window.location.href = this.href;
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>