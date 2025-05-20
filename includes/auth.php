<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $users = queryData('users', '*', ['id' => 'eq.' . $_SESSION['user_id']]);
    
    if ($users && count($users) > 0) {
        return $users[0];
    }
    
    return null;
}

// Get user roles
function getUserRoles($userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return [];
    }
    
    $query = 'user_roles(id, role_id, roles(id, name, description))';
    $userRoles = queryData('users', $query, ['id' => 'eq.' . $userId]);
    
    if ($userRoles && count($userRoles) > 0 && isset($userRoles[0]['user_roles'])) {
        return array_map(function($role) {
            return $role['roles'];
        }, $userRoles[0]['user_roles']);
    }
    
    return [];
}

// Check if user has specific role
function hasRole($roleName, $userId = null) {
    $roles = getUserRoles($userId);
    
    foreach ($roles as $role) {
        if ($role['name'] === $roleName) {
            return true;
        }
    }
    
    return false;
}

// Check if user has any of the specified roles
function hasAnyRole($roleNames, $userId = null) {
    if (!is_array($roleNames)) {
        $roleNames = [$roleNames];
    }
    
    $roles = getUserRoles($userId);
    
    foreach ($roles as $role) {
        if (in_array($role['name'], $roleNames)) {
            return true;
        }
    }
    
    return false;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /rbac_system/users/login.php');
        exit;
    }
}

// Redirect if not authorized (doesn't have required role)
function requireRole($roleName) {
    requireLogin();
    
    if (!hasRole($roleName)) {
        header('Location: /rbac_system/index.php?error=unauthorized');
        exit;
    }
}

// Redirect if not authorized (doesn't have any of the required roles)
function requireAnyRole($roleNames) {
    requireLogin();
    
    if (!hasAnyRole($roleNames)) {
        header('Location: /rbac_system/index.php?error=unauthorized');
        exit;
    }
}
?>