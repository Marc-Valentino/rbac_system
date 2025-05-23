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
// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    $userId = $_POST['user_id'] ?? '';
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if (empty($userId) || $roleId <= 0) {
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
    $userId = $_POST['user_id'] ?? '';
    $roleId = intval($_POST['role_id'] ?? 0);
    
    if (empty($userId) || $roleId <= 0) {
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
    
    <!-- Toast container for notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1070;">
        <!-- Toasts will be inserted here dynamically -->
    </div>
    
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
                                    <?php echo htmlspecialchars(ucfirst($role['name'])); ?>
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
                            <button type="button" class="btn btn-sm btn-primary assign-role-btn" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">
                                <i class="fas fa-user-tag"></i> Assign Role
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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
                                <?php echo htmlspecialchars(ucfirst($role['name'])); ?> - <?php echo htmlspecialchars($role['description'] ? ucfirst($role['description']) : ''); ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast notification function
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toastId = 'toast-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();
        
        // Remove toast from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
    
    // Show toast for PHP messages on page load
    <?php if (!empty($success)): ?>
        showToast('<?php echo addslashes($success); ?>', 'success');
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        showToast('<?php echo addslashes($error); ?>', 'danger');
    <?php endif; ?>
    
    // Replace the existing role removal code with this improved implementation
    function attachRemoveRoleListeners(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            if (!confirm('Are you sure you want to remove this role from the user?')) {
                return;
            }
            
            // Get form data
            const formData = new FormData(this);
            const userId = this.querySelector('input[name="user_id"]').value;
            const roleId = this.querySelector('input[name="role_id"]').value;
            const roleElement = this.closest('.badge');
            const rolesCell = roleElement.parentElement;
            
            // Add loading state
            roleElement.classList.add('opacity-50');
            const removeBtn = this.querySelector('button');
            removeBtn.disabled = true;
            
            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Always remove the role from UI immediately
                roleElement.remove();
                
                // Check if this was the last role and update UI accordingly
                if (!rolesCell.querySelector('.badge')) {
                    rolesCell.innerHTML = '<em>No roles assigned</em>';
                }
                
                // Show success message
                showToast('Role removed successfully!', 'success');
                
                // Force reload the page after a short delay to ensure UI is in sync with database
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'danger');
                
                // Reset loading state
                roleElement.classList.remove('opacity-50');
                removeBtn.disabled = false;
            });
        });
    }
    
    // Attach event listeners to all existing remove role forms
    const removeForms = document.querySelectorAll('.remove-role-form');
    removeForms.forEach(form => {
        attachRemoveRoleListeners(form);
    });
    
    // Initialize the modal properly
    const globalRoleModal = document.getElementById('globalRoleModal');
    let modal;
    
    if (globalRoleModal) {
        try {
            modal = new bootstrap.Modal(globalRoleModal);
        } catch (error) {
            console.error('Error initializing modal:', error);
        }
    }
    
    // Assign role button click handler
    document.querySelectorAll('.assign-role-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            
            // Update modal title and user ID
            document.getElementById('globalRoleModalLabel').textContent = 'Assign Role to ' + userName;
            document.getElementById('globalModalUserId').value = userId;
            
            // Reset form
            document.getElementById('globalRoleForm').reset();
            
            // Show modal
            if (modal) {
                modal.show();
            } else {
                // Fallback if modal initialization failed
                $('#globalRoleModal').modal('show');
            }
        });
    });
    
    // Form submission with AJAX and improved feedback
    document.getElementById('globalRoleForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const roleSelect = document.getElementById('global_role_id');
        
        if (!roleSelect.value) {
            showToast('Please select a role to assign', 'warning');
            return;
        }
        
        // Add loading state to button
        const submitButton = document.querySelector('button[form="globalRoleForm"]');
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Assigning...';
        submitButton.disabled = true;
        
        // Get form data
        const formData = new FormData(this);
        
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Parse the response to check for success or error messages
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Check if there's a success message in the response
            const successAlert = doc.querySelector('.alert-success');
            
            // Also check if the role was actually removed by looking at the updated HTML
            const userRowInResponse = doc.querySelector(`button[data-user-id="${userId}"]`)?.closest('tr');
            const roleStillExists = userRowInResponse?.querySelector(`input[name="role_id"][value="${roleId}"]`);
            
            if (successAlert || !roleStillExists) {
                showToast(successAlert ? successAlert.textContent.trim() : 'Role removed successfully!', 'success');
                
                // Remove the role badge from the UI
                roleElement.remove();
                
                // Check if this was the last role and update UI accordingly
                if (!rolesCell.querySelector('.badge')) {
                    rolesCell.innerHTML = '<em>No roles assigned</em>';
                }
            } else {
                // Check for error message
                const errorAlert = doc.querySelector('.alert-danger');
                if (errorAlert) {
                    showToast(errorAlert.textContent.trim(), 'danger');
                } else {
                    showToast('Failed to remove role. Please try again.', 'danger');
                }
                
                // Reset loading state
                roleElement.classList.remove('opacity-50');
                removeBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'danger');
        })
        .finally(() => {
            // Reset button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });
});
</script>

<style>
/* Fix modal styles */
.modal-backdrop {
    opacity: 0.5 !important;
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

.modal-dialog {
    pointer-events: auto !important;
}

.modal-content {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    pointer-events: auto !important;
}

/* Toast styles */
.toast-container {
    z-index: 1070 !important;
}

.toast {
    opacity: 1 !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Improved select styling */
.form-select {
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-300);
    transition: all 0.3s ease;
    font-weight: 500;
}

.form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .toast-container {
        max-width: 90%;
        padding: 0.5rem !important;
    }
    
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}
</style>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>