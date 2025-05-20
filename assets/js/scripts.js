// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Task status change handler
    const statusSelects = document.querySelectorAll('.task-status-select');
    if (statusSelects) {
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const taskId = this.getAttribute('data-task-id');
                const newStatus = this.value;
                
                // Send AJAX request to update task status
                fetch('tasks/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            Task status updated successfully!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        
                        // Find the container to insert the alert
                        const container = document.querySelector('.container');
                        container.insertBefore(alertDiv, container.firstChild);
                        
                        // Auto dismiss after 3 seconds
                        setTimeout(() => {
                            alertDiv.classList.remove('show');
                            setTimeout(() => alertDiv.remove(), 150);
                        }, 3000);
                    } else {
                        console.error('Failed to update task status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }
    
    // Task deletion confirmation
    const deleteButtons = document.querySelectorAll('.delete-task-btn');
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // User role management
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');
    if (roleCheckboxes) {
        roleCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const userId = this.getAttribute('data-user-id');
                const roleId = this.getAttribute('data-role-id');
                const isChecked = this.checked;
                
                // Send AJAX request to update user role
                fetch('pages/admin/update_role.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&role_id=${roleId}&action=${isChecked ? 'add' : 'remove'}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            User role updated successfully!
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        `;
                        
                        // Find the container to insert the alert
                        const container = document.querySelector('.container');
                        container.insertBefore(alertDiv, container.firstChild);
                        
                        // Auto dismiss after 3 seconds
                        setTimeout(() => {
                            alertDiv.classList.remove('show');
                            setTimeout(() => alertDiv.remove(), 150);
                        }, 3000);
                    } else {
                        console.error('Failed to update user role');
                        // Revert checkbox state
                        this.checked = !isChecked;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert checkbox state
                    this.checked = !isChecked;
                });
            });
        });
    }
    
    // Deadline date picker initialization
    const deadlinePicker = document.getElementById('task-deadline');
    if (deadlinePicker) {
        // If you're using a library like flatpickr, you would initialize it here
        // For now, we'll just set min date to today
        const today = new Date().toISOString().split('T')[0];
        deadlinePicker.setAttribute('min', today);
    }
    
    // Task filtering
    const filterForm = document.getElementById('task-filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const queryString = new URLSearchParams(formData).toString();
            
            // Redirect to the same page with filter parameters
            window.location.href = `${window.location.pathname}?${queryString}`;
        });
    }
});