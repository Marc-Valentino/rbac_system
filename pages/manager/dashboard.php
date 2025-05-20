<?php 
 require_once __DIR__ . '/../../includes/auth.php'; 
 require_once __DIR__ . '/../../includes/functions.php'; 
 
 // Require login 
 requireLogin(); 
 
 // Require project manager role 
 requireRole('project_manager'); 
 
 $currentUser = getCurrentUser(); 
 
 // Get projects managed by this user 
 $managedProjects = queryData('projects', '*', ['manager_id' => 'eq.' . getCurrentUserId()], 'created_at.desc'); 
 
 // Get task statistics 
 $taskStats = [ 
     'total' => 0, 
     'pending' => 0, 
     'in_progress' => 0, 
     'review' => 0, 
     'completed' => 0, 
     'cancelled' => 0 
 ]; 
 
 $projectIds = array_map(function($project) { 
     return $project['id']; 
 }, $managedProjects); 
 
 if (!empty($projectIds)) { 
     $projectIdList = implode(',', $projectIds); 
     
     $taskStats['total'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')'])); 
     $taskStats['pending'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')', 'status' => 'eq.pending'])); 
     $taskStats['in_progress'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')', 'status' => 'eq.in_progress'])); 
     $taskStats['review'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')', 'status' => 'eq.review'])); 
     $taskStats['completed'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')', 'status' => 'eq.completed'])); 
     $taskStats['cancelled'] = count(queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')', 'status' => 'eq.cancelled'])); 
 } 
 
 include_once __DIR__ . '/../../includes/header.php'; 
 ?> 
 
 <div class="manager-dashboard"> 
     <h2>Project Manager Dashboard</h2> 
     <p>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>!</p> 
     
     <div class="row mt-4"> 
         <div class="col-md-3"> 
             <div class="card text-white bg-primary mb-3"> 
                 <div class="card-header">Projects</div> 
                 <div class="card-body"> 
                     <h5 class="card-title"><?php echo count($managedProjects); ?></h5> 
                     <p class="card-text">Projects you manage</p> 
                 </div> 
             </div> 
         </div> 
         
         <div class="col-md-3"> 
             <div class="card text-white bg-success mb-3"> 
                 <div class="card-header">Tasks</div> 
                 <div class="card-body"> 
                     <h5 class="card-title"><?php echo $taskStats['total']; ?></h5> 
                     <p class="card-text">Total tasks across all projects</p> 
                 </div> 
             </div> 
         </div> 
         
         <div class="col-md-3"> 
             <div class="card text-white bg-warning mb-3"> 
                 <div class="card-header">In Progress</div> 
                 <div class="card-body"> 
                     <h5 class="card-title"><?php echo $taskStats['in_progress']; ?></h5> 
                     <p class="card-text">Tasks currently in progress</p> 
                 </div> 
             </div> 
         </div> 
         
         <div class="col-md-3"> 
             <div class="card text-white bg-info mb-3"> 
                 <div class="card-header">Completed</div> 
                 <div class="card-body"> 
                     <h5 class="card-title"><?php echo $taskStats['completed']; ?></h5> 
                     <p class="card-text">Tasks completed successfully</p> 
                 </div> 
             </div> 
         </div> 
     </div> 
     
     <div class="row mt-4"> 
         <div class="col-md-12"> 
             <div class="card"> 
                 <div class="card-header d-flex justify-content-between align-items-center">
                     <h4>Your Projects</h4>
                     <a href="/rbac_system/projects/create.php" class="btn btn-primary">
                         <i class="fas fa-plus"></i> Create New Project
                     </a>
                 </div>
                 <div class="card-body">
                     <?php if (empty($managedProjects)): ?>
                         <div class="alert alert-info">
                             You don't have any projects yet. Click the "Create New Project" button to get started.
                         </div>
                     <?php else: ?>
                         <div class="table-responsive">
                             <table class="table table-striped">
                                 <thead>
                                     <tr>
                                         <th>Project Name</th>
                                         <th>Description</th>
                                         <th>Status</th>
                                         <th>Tasks</th>
                                         <th>Created</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php foreach ($managedProjects as $project): ?>
                                         <?php 
                                             $projectTasks = queryData('tasks', '*', ['project_id' => 'eq.' . $project['id']]);
                                             $taskCount = count($projectTasks);
                                         ?>
                                         <tr>
                                             <td><?php echo htmlspecialchars($project['name']); ?></td>
                                             <td>
                                                 <?php if (strlen($project['description']) > 50): ?>
                                                     <?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?>
                                                 <?php else: ?>
                                                     <?php echo htmlspecialchars($project['description']); ?>
                                                 <?php endif; ?>
                                             </td>
                                             <td>
                                                 <?php if ($project['status'] == 'active'): ?>
                                                     <span class="badge bg-success">Active</span>
                                                 <?php elseif ($project['status'] == 'completed'): ?>
                                                     <span class="badge bg-info">Completed</span>
                                                 <?php else: ?>
                                                     <span class="badge bg-secondary"><?php echo ucfirst(htmlspecialchars($project['status'])); ?></span>
                                                 <?php endif; ?>
                                             </td>
                                             <td><?php echo $taskCount; ?></td>
                                             <td><?php echo formatDate($project['created_at']); ?></td>
                                             <td>
                                                 <div class="btn-group">
                                                     <a href="/rbac_system/projects/view.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                                         <i class="fas fa-eye"></i>
                                                     </a>
                                                     <a href="/rbac_system/projects/edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-warning">
                                                         <i class="fas fa-edit"></i>
                                                     </a>
                                                     <a href="/rbac_system/tasks/create.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-success">
                                                         <i class="fas fa-plus"></i> Task
                                                     </a>
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
     </div>
     
     <div class="row mt-4">
         <div class="col-md-6">
             <div class="card">
                 <div class="card-header">
                     <h4>Recent Tasks</h4>
                 </div>
                 <div class="card-body">
                     <?php if (empty($projectIds)): ?>
                         <div class="alert alert-info">
                             No tasks available. Create a project first.
                         </div>
                     <?php else: ?>
                         <?php 
                             $recentTasks = queryData('tasks', '*', ['project_id' => 'in.(' . $projectIdList . ')'], 'created_at.desc', '5');
                         ?>
                         
                         <?php if (empty($recentTasks)): ?>
                             <div class="alert alert-info">
                                 No tasks created yet. Add tasks to your projects.
                             </div>
                         <?php else: ?>
                             <div class="list-group">
                                 <?php foreach ($recentTasks as $task): ?>
                                     <a href="/rbac_system/tasks/view.php?id=<?php echo $task['id']; ?>" class="list-group-item list-group-item-action">
                                         <div class="d-flex w-100 justify-content-between">
                                             <h5 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h5>
                                             <small><?php echo getStatusBadge($task['status']); ?></small>
                                         </div>
                                         <p class="mb-1">
                                             <?php if (strlen($task['description']) > 100): ?>
                                                 <?php echo htmlspecialchars(substr($task['description'], 0, 100)) . '...'; ?>
                                             <?php else: ?>
                                                 <?php echo htmlspecialchars($task['description']); ?>
                                             <?php endif; ?>
                                         </p>
                                         <small>
                                             <?php 
                                                 $project = getProjectById($task['project_id']);
                                                 echo 'Project: ' . htmlspecialchars($project['name']);
                                             ?>
                                         </small>
                                     </a>
                                 <?php endforeach; ?>
                             </div>
                             
                             <div class="mt-3">
                                 <a href="/rbac_system/tasks/index.php" class="btn btn-outline-primary">View All Tasks</a>
                             </div>
                         <?php endif; ?>
                     <?php endif; ?>
                 </div>
             </div>
         </div>
         
         <div class="col-md-6">
             <div class="card">
                 <div class="card-header">
                     <h4>Task Status Distribution</h4>
                 </div>
                 <div class="card-body">
                     <?php if ($taskStats['total'] > 0): ?>
                         <canvas id="taskStatusChart" width="400" height="300"></canvas>
                         
                         <script>
                             document.addEventListener('DOMContentLoaded', function() {
                                 const ctx = document.getElementById('taskStatusChart').getContext('2d');
                                 const taskStatusChart = new Chart(ctx, {
                                     type: 'pie',
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
                                             },
                                             title: {
                                                 display: true,
                                                 text: 'Task Status Distribution'
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
         </div>
     </div>
 </div>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>