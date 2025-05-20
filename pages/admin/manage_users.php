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
                            <!-- Replace the existing modal button with this -->
                            <button type="button" class="btn btn-sm btn-primary assign-role-btn" data-user-id="<?php echo $user['id']; ?>">
                                <i class="fas fa-user-tag"></i> Assign Role
                            </button>
                            
                            <!-- Add this at the bottom of your file, before the closing </div> -->
                            <!-- Single Global Modal -->
                            <div class="modal fade" id="globalRoleModal" tabindex="-1" aria-labelledby="globalRoleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="globalRoleModalLabel">Assign Role</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" id="globalRoleForm">
                                                <input type="hidden" name="action" value="assign_role">
                                                <input type="hidden" name="user_id" id="globalModalUserId" value="">
                                                
                                                <div class="mb-3">
                                                    <label for="global_role_id" class="form-label">Select Role</label>
                                                    <select class="form-select" id="global_role_id" name="role_id" required>
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
                                            <button type="submit" form="globalRoleForm" class="btn btn-primary">Assign Role</button>
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
    
    // Fix modal glitching issues
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        // Ensure proper backdrop and keyboard behavior
        modal.setAttribute('data-bs-backdrop', 'static');
        
        // Prevent event propagation issues
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });
        
        // Fix content click propagation
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    
    // Ensure only one modal is open at a time
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            // Close any open modals first
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(openModal => {
                const modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });
    });
});
</script>

<style>
/* Fix modal z-index and stacking issues */
.modal {
    z-index: 1050 !important;
}
.modal-backdrop {
    z-index: 1040 !important;
}
/* Ensure modal content is clickable */
.modal-content {
    position: relative;
    z-index: 1051 !important;
}
/* Prevent unwanted hover effects */
.modal-dialog {
    pointer-events: all !important;
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>

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
    
    // Complete modal reset approach
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the target modal ID
            const targetModalId = this.getAttribute('data-bs-target');
            const targetModal = document.querySelector(targetModalId);
            
            if (targetModal) {
                // Destroy any existing modal instances first
                const existingModal = bootstrap.Modal.getInstance(targetModal);
                if (existingModal) {
                    existingModal.dispose();
                }
                
                // Create a fresh modal instance
                const newModal = new bootstrap.Modal(targetModal, {
                    backdrop: 'static',
                    keyboard: false,
                    focus: true
                });
                
                // Show the modal
                newModal.show();
            }
        });
    });
});
</script>

<style>
/* Reset modal styles to defaults */
.modal-backdrop {
    opacity: 0.5 !important;
}

/* Ensure modals appear above everything */
.modal {
    z-index: 1055 !important;
}

/* Fix pointer events */
.modal-dialog {
    margin: 1.75rem auto;
    max-width: 500px;
    pointer-events: auto !important;
}

/* Ensure content is clickable */
.modal-content {
    position: relative;
    pointer-events: auto !important;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Fix button hover states */
.modal .btn:hover {
    opacity: 0.85;
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>

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
    
    // Fix modal glitching issues
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        // Ensure proper backdrop and keyboard behavior
        modal.setAttribute('data-bs-backdrop', 'static');
        
        // Prevent event propagation issues
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });
        
        // Fix content click propagation
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
    
    // Ensure only one modal is open at a time
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            // Close any open modals first
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(openModal => {
                const modalInstance = bootstrap.Modal.getInstance(openModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        });
    });
    
    // Use a single global modal instead of multiple modals
    const globalModal = new bootstrap.Modal(document.getElementById('globalRoleModal'), {
        backdrop: 'static',
        keyboard: false
    });
    
    // Assign role button click handler
    document.querySelectorAll('.assign-role-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
            
            // Update modal title and user ID
            document.getElementById('globalRoleModalLabel').textContent = 'Assign Role to ' + userName;
            document.getElementById('globalModalUserId').value = userId;
            
            // Reset form
            document.getElementById('globalRoleForm').reset();
            
            // Show modal
            globalModal.show();
        });
    });
});
</script>