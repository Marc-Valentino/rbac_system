<?php
require_once __DIR__ . '/../includes/auth.php';

// Clear session data
session_unset();
session_destroy();

// Redirect to login page
header('Location: /rbac_system/users/login.php');
exit;
?>