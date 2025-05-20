<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin role
requireRole('administrator');

$currentUser = getCurrentUser();
$users = getAllUsers();
$roles = getAllRoles();

$error = '';
$success = '';

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    $userId = intval($_POST['user_id'] ?? 0);
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if ($userId <= 0 || $roleId <= 0) {
        $error = 'Invalid user or role ID.';
    } else {
        // Check if user already has this role
        $userRoles = queryData('user_roles', '*', [
            'user_id' => 'eq.' . $userId,
            'role_id' => 'eq.' . $roleId
        ]);
        
        if (!empty($userRoles)) {
            $error = 'User already has this role.';
        } else {
            $result = assignRoleToUser($userId, $roleId);
            
            if ($result !== null) {
                $success = 'Role assigned successfully!';
                // Refresh users list
                $users = getAllUsers();
            } else {
                $error = 'Failed to assign role. Please try again.';
            }
        }
    }
}

// Handle role removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_role') {
    $userId = intval($_POST['user_id'] ?? 0);
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if ($userId <= 0 || $roleId <= 0) {
        $error = 'Invalid user or role ID.';
    } else {
        $result = removeRoleFromUser($userId, $roleId);
        
        if ($result !== null) {
            $success = 'Role removed successfully!';
            // Refresh users list
            $users = getAllUsers();
        } else {
            $error = 'Failed to remove role. Please try again.';
        }
    }
}

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="manage-users-container">
    <h2>Manage Users</h2>
    
    <?php if (!empty($error)): ?>
        <?php echo displayError($error); ?>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <?php echo displaySuccess($success); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5>User Management</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php 
                            $userRoles = getUserRoles($user['id']);
                            if (!empty($userRoles)): 
                                foreach ($userRoles as $role): 
                            ?>
                                <span class="badge bg-primary me-1">
                                    <?php echo htmlspecialchars($role['name']); ?>
                                    <form method="POST" class="d-inline remove-role-form">
                                        <input type="hidden" name="action" value="remove_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                        <button type="submit" class="btn-close btn-close-white ms-1" aria-label="Remove role"></button>
                                    </form>
                                </span>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <em>No roles assigned</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignRoleModal<?php echo $user['id']; ?>">
                                <i class="fas fa-user-tag"></i> Assign Role
                            </button>
                            
                            <!-- Assign Role Modal -->
                            <div class="modal fade" id="assignRoleModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="assignRoleModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="assignRoleModalLabel<?php echo $user['id']; ?>">
                                                Assign Role to <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" id="assignRoleForm<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="assign_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="role_id<?php echo $user['id']; ?>" class="form-label">Select Role</label>
                                                    <select class="form-select" id="role_id<?php echo $user['id']; ?>" name="role_id" required>
                                                        <option value="">-- Select Role --</option>
                                                        <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['id']; ?>">
                                                            <?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description'] ?? ''); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" form="assignRoleForm<?php echo $user['id']; ?>" class="btn btn-primary">Assign Role</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirm role removal
    const removeForms = document.querySelectorAll('.remove-role-form');
    removeForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to remove this role from the user?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>