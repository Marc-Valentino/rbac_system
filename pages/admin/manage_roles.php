<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin role
requireRole('administrator');

$currentUser = getCurrentUser();
$roles = getAllRoles();

$error = '';
$success = '';

// Handle role creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Role name is required.';
    } else {
        // Check if role already exists
        $existingRoles = queryData('roles', '*', ['name' => 'eq.' . $name]);
        
        if (!empty($existingRoles)) {
            $error = 'A role with this name already exists.';
        } else {
            $roleData = [
                'name' => $name,
                'description' => $description
            ];
            
            $result = insertData('roles', $roleData);
            
            if ($result !== null) {
                $success = 'Role created successfully!';
                // Refresh roles list
                $roles = getAllRoles();
            } else {
                $error = 'Failed to create role. Please try again.';
            }
        }
    }
}

// Handle role deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if ($roleId <= 0) {
        $error = 'Invalid role ID.';
    } else {
        // Check if role is in use
        $userRoles = queryData('user_roles', '*', ['role_id' => 'eq.' . $roleId]);
        
        if (!empty($userRoles)) {
            $error = 'This role is assigned to users and cannot be deleted.';
        } else {
            $result = deleteData('roles', ['id' => 'eq.' . $roleId]);
            
            if ($result !== null) {
                $success = 'Role deleted successfully!';
                // Refresh roles list
                $roles = getAllRoles();
            } else {
                $error = 'Failed to delete role. Please try again.';
            }
        }
    }
}

include_once __DIR__ . '/../../includes/header.php';
?>

<div class="manage-roles-container">
    <h2>Manage Roles</h2>
    
    <?php if (!empty($error)): ?>
        <?php echo displayError($error); ?>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <?php echo displaySuccess($success); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Existing Roles</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo $role['id']; ?></td>
                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                <td><?php echo htmlspecialchars($role['description'] ?? ''); ?></td>
                                <td>
                                    <form method="POST" class="d-inline delete-role-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Create New Role</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Role Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Use lowercase with underscores (e.g., team_leader)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirm role deletion
    const deleteForms = document.querySelectorAll('.delete-role-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>