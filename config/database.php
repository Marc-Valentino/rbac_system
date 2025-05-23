<?php
// Supabase credentials
$supabase_url = 'https://fzpfhxunqtlchajijmbs.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZ6cGZoeHVucXRsY2hhamlqbWJzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDc3NTEwNjUsImV4cCI6MjA2MzMyNzA2NX0.awfbvnM2gdAYYWxoTue9X6WB4b5C6tMlZlFeWSMbphc';

// Include cache functions
require_once __DIR__ . '/../includes/cache.php';

// Function to make Supabase API requests
function supabaseRequest($endpoint, $method = 'GET', $data = null, $auth = true) {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . $endpoint;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $supabase_key,
    ];
    
    if ($auth && isset($_SESSION['access_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['access_token'];
    } else {
        $headers[] = 'Authorization: Bearer ' . $supabase_key;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Add error logging
    if ($status_code >= 400) {
        error_log("Supabase API Error: " . $status_code . " - " . $response);
        error_log("Request: " . $method . " " . $url);
        if ($data) {
            error_log("Data: " . json_encode($data));
        }
    }
    
    curl_close($ch);
    
    return [
        'status' => $status_code,
        'data' => json_decode($response, true)
    ];
}

// Function to query data from Supabase
function queryData($table, $select = '*', $filters = [], $order = null, $limit = null) {
    // Generate a cache key based on the query parameters
    $cacheKey = 'query_' . $table . '_' . $select . '_' . serialize($filters) . '_' . $order . '_' . $limit;
    
    // Try to get data from cache first
    $cachedData = getCache($cacheKey);
    if ($cachedData !== null) {
        return $cachedData;
    }
    
    $endpoint = '/rest/v1/' . $table . '?select=' . urlencode($select);
    
    foreach ($filters as $key => $value) {
        $endpoint .= '&' . $key . '=' . urlencode($value);
    }
    
    if ($order) {
        $endpoint .= '&order=' . urlencode($order);
    }
    
    if ($limit) {
        $endpoint .= '&limit=' . urlencode($limit);
    }
    
    $response = supabaseRequest($endpoint);
    
    if ($response['status'] >= 200 && $response['status'] < 300) {
        // Cache the result for future use
        setCache($cacheKey, $response['data']);
        return $response['data'];
    }
    
    return null;
}

// Function to insert data into Supabase
function insertData($table, $data) {
    $endpoint = '/rest/v1/' . $table;
    $response = supabaseRequest($endpoint, 'POST', $data);
    
    if ($response['status'] >= 200 && $response['status'] < 300) {
        // Clear cache for this table
        clearCache('query_' . $table . '_*');
        return $response['data'];
    } else {
        // Log the error details
        error_log("Insert data failed for table {$table}: " . json_encode($response));
        return null;
    }
}

// Function to update data in Supabase
function updateData($table, $data, $filters = []) {
    $endpoint = '/rest/v1/' . $table;
    
    foreach ($filters as $key => $value) {
        $endpoint .= ($key === array_key_first($filters) ? '?' : '&') . $key . '=' . urlencode($value);
    }
    
    $response = supabaseRequest($endpoint, 'PATCH', $data);
    
    if ($response['status'] >= 200 && $response['status'] < 300) {
        // Clear cache for this table
        clearCache('query_' . $table . '_*');
        return $response['data'];
    }
    
    return null;
}

// Function to delete data from Supabase
function deleteData($table, $filters = []) {
    $endpoint = '/rest/v1/' . $table;
    
    foreach ($filters as $key => $value) {
        $endpoint .= ($key === array_key_first($filters) ? '?' : '&') . $key . '=' . urlencode($value);
    }
    
    $response = supabaseRequest($endpoint, 'DELETE');
    
    if ($response['status'] >= 200 && $response['status'] < 300) {
        // Clear cache for this table
        clearCache('query_' . $table . '_*');
        return true;
    }
    
    return false;
}

// Function to register user with Supabase
function registerUser($email, $password, $userData = []) {
    $endpoint = '/auth/v1/signup';
    $data = [
        'email' => $email,
        'password' => $password
    ];
    
    $response = supabaseRequest($endpoint, 'POST', $data, false);
    
    // Debug the response
    error_log('Supabase registration response: ' . json_encode($response));
    
    if ($response['status'] >= 200 && $response['status'] < 300 && isset($response['data']['id'])) {
        // Insert additional user data
        if (!empty($userData)) {
            $userData['id'] = $response['data']['id'];
            // Ensure email and password are stored in users table
            $userData['email'] = $email;
            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            
            $insertResult = insertData('users', $userData);
            
            // Check if the insert was successful
            if ($insertResult === null) {
                error_log('Failed to insert user data into users table: ' . json_encode($userData));
                return null;
            }
        } else {
            // If no additional data provided, still need to create user record
            $basicUserData = [
                'id' => $response['data']['id'],
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            $insertResult = insertData('users', $basicUserData);
            
            if ($insertResult === null) {
                error_log('Failed to insert basic user data into users table');
                return null;
            }
        }
        return $response['data'];
    } else {
        // Check for specific error messages from Supabase
        if (isset($response['data']['error'])) {
            error_log('Supabase registration error: ' . $response['data']['error']);
            return ['error' => $response['data']['error']];
        } else {
            error_log('Supabase registration failed: ' . json_encode($response));
            return ['error' => 'registration_failed'];
        }
    }
    
    return null;
}
?>