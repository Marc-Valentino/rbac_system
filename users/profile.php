<?php 
 require_once __DIR__ . '/../includes/auth.php'; 
 require_once __DIR__ . '/../includes/functions.php'; 
 
 // Require login 
 requireLogin(); 
 
 $currentUser = getCurrentUser(); 
 $userRoles = getUserRoles(); 
 
 $error = ''; 
 $success = ''; 
 
 if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
     $firstName = sanitizeInput($_POST['first_name'] ?? ''); 
     $lastName = sanitizeInput($_POST['last_name'] ?? ''); 
     
     if (empty($firstName) || empty($lastName)) { 
         $error = 'Please fill in all required fields.'; 
     } else { 
         $userData = [ 
             'first_name' => $firstName, 
             'last_name' => $lastName 
         ]; 
         
         // Handle profile picture upload 
         if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) { 
             $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']; 
             $fileType = $_FILES['profile_picture']['type']; 
             
             if (in_array($fileType, $allowedTypes)) { 
                 $fileName = uniqid() . '_' . $_FILES['profile_picture']['name']; 
                 $uploadDir = __DIR__ . '/../assets/uploads/'; 
                 
                 // Create uploads directory if it doesn't exist 
                 if (!file_exists($uploadDir)) { 
                     mkdir($uploadDir, 0777, true); 
                 } 
                 
                 $uploadPath = $uploadDir . $fileName; 
                 
                 if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) { 
                     $userData['profile_picture'] = '/rbac_system/assets/uploads/' . $fileName; 
                 } else { 
                     $error = 'Failed to upload profile picture.'; 
                 } 
             } else { 
                 $error = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.'; 
             } 
         } 
         
         if (empty($error)) { 
             $updated = updateData('users', $userData, ['id' => 'eq.' . getCurrentUserId()]); 
             
             if ($updated !== null) { 
                 $success = 'Profile updated successfully.'; 
                 $currentUser = getCurrentUser(); // Refresh user data 
             } else { 
                 $error = 'Failed to update profile.'; 
             } 
         } 
     } 
 } 
 
 include_once __DIR__ . '/../includes/header.php'; 
 ?> 
 
 <div class="profile-container"> 
     <h2>My Profile</h2> 
     
     <?php if (!empty($error)): ?> 
         <?php echo displayError($error); ?> 
     <?php endif; ?> 
     
     <?php if (!empty($success)): ?> 
         <?php echo displaySuccess($success); ?> 
     <?php endif; ?> 
     
     <div class="row"> 
         <div class="col-md-4 text-center"> 
             <?php if (!empty($currentUser['profile_picture'])): ?> 
                 <img src="<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" alt="Profile Picture" class="profile-picture"> 
             <?php else: ?> 
                 <img src="https://via.placeholder.com/150" alt="Default Profile" class="profile-picture"> 
             <?php endif; ?> 
             
             <div class="mt-3">
                 <h4><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h4>
                 <p class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                 
                 <div class="user-roles">
                     <strong>Roles:</strong>
                     <?php if (!empty($userRoles)): ?>
                         <ul class="list-inline">
                             <?php foreach ($userRoles as $role): ?>
                                 <li class="list-inline-item badge bg-primary"><?php echo htmlspecialchars($role['name']); ?></li>
                             <?php endforeach; ?>
                         </ul>
                     <?php else: ?>
                         <p>No roles assigned</p>
                     <?php endif; ?>
                 </div>
             </div>
         </div>
         
         <div class="col-md-8">
             <div class="card">
                 <div class="card-header">
                     <h3>Edit Profile</h3>
                 </div>
                 <div class="card-body">
                     <form method="POST" enctype="multipart/form-data">
                         <div class="mb-3">
                             <label for="first_name" class="form-label">First Name</label>
                             <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>" required>
                         </div>
                         
                         <div class="mb-3">
                             <label for="last_name" class="form-label">Last Name</label>
                             <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>" required>
                         </div>
                         
                         <div class="mb-3">
                             <label for="email" class="form-label">Email</label>
                             <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                             <div class="form-text">Email cannot be changed.</div>
                         </div>
                         
                         <div class="mb-3">
                             <label for="profile_picture" class="form-label">Profile Picture</label>
                             <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                             <div class="form-text">Upload a JPEG, PNG, or GIF image.</div>
                         </div>
                         
                         <button type="submit" class="btn btn-primary">Update Profile</button>
                     </form>
                 </div>
             </div>
         </div>
     </div>
 </div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>