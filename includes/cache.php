<?php
/**
 * Simple caching system for RBAC
 */

// Cache directory
define('CACHE_DIR', __DIR__ . '/../cache');

// Create cache directory if it doesn't exist
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

/**
 * Get cached data
 * 
 * @param string $key Cache key
 * @param int $ttl Time to live in seconds (default: 5 minutes)
 * @return mixed|null Cached data or null if not found/expired
 */
function getCache($key, $ttl = 300) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile)) {
        $fileTime = filemtime($cacheFile);
        
        // Check if cache is still valid
        if (time() - $fileTime < $ttl) {
            $data = file_get_contents($cacheFile);
            return unserialize($data);
        }
    }
    
    return null;
}

/**
 * Set cached data
 * 
 * @param string $key Cache key
 * @param mixed $data Data to cache
 * @return bool True on success, false on failure
 */
function setCache($key, $data) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    return file_put_contents($cacheFile, serialize($data)) !== false;
}

/**
 * Clear cache for a specific key
 * 
 * @param string $key Cache key
 * @return bool True on success, false on failure
 */
function clearCache($key) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return true;
}

/**
 * Clear all cache
 * 
 * @return bool True on success, false on failure
 */
function clearAllCache() {
    $files = glob(CACHE_DIR . '/*.cache');
    
    foreach ($files as $file) {
        if (!unlink($file)) {
            return false;
        }
    }
    
    return true;
}