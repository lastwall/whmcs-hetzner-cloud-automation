<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Optimized API Request to Hetzner Cloud
 * Consolidated endpoints and improved error handling
 */
function hetznercloud_API_Request($method, $endpoint, $params, $serverID = null, $postData = [])
{
    $apiToken = $params['serveraccesshash'] ?? hetznercloud_GetAPIToken();
    
    if (!$apiToken) {
        logActivity("Hetzner Cloud - API Request: No API token available");
        return ['success' => false, 'message' => 'API token not configured'];
    }

    // Build optimized URL based on endpoint type
    $url = hetznercloud_BuildAPIUrl($endpoint, $serverID, $method, $postData);
    
    if (!$url) {
        return ['success' => false, 'message' => 'Invalid endpoint or missing server ID'];
    }

    // Execute API request
    return hetznercloud_ExecuteRequest($url, $method, $apiToken, $postData, $endpoint, $serverID);
}

/**
 * Build API URL based on endpoint type
 */
function hetznercloud_BuildAPIUrl($endpoint, $serverID, $method, $postData)
{
    $baseUrl = "https://api.hetzner.cloud/v1";
    
    // Direct resource endpoints
    $directEndpoints = [
        'create_server' => '/servers',
        'server_types' => '/server_types',
        'images' => '/images',
        'isos' => '/isos'
    ];
    
    if (isset($directEndpoints[$endpoint])) {
        return $baseUrl . $directEndpoints[$endpoint];
    }
    
    // Server-specific endpoints
    if ($serverID) {
        // Metrics endpoint
        if ($endpoint === 'metrics') {
            $url = "{$baseUrl}/servers/{$serverID}/metrics";
            return !empty($postData) ? $url . '?' . http_build_query($postData) : $url;
        }
        
        // Server details
        if ($endpoint === 'server_details') {
            return "{$baseUrl}/servers/{$serverID}";
        }
        
        // Server actions
        if (in_array($endpoint, ['poweron', 'poweroff', 'reboot', 'reset', 'shutdown'])) {
            return "{$baseUrl}/servers/{$serverID}/actions/{$endpoint}";
        }
        
        // ISO actions
        if (in_array($endpoint, ['attach_iso', 'detach_iso'])) {
            return "{$baseUrl}/servers/{$serverID}/actions/{$endpoint}";
        }
        
        // Rebuild action
        if ($endpoint === 'rebuild') {
            return "{$baseUrl}/servers/{$serverID}/actions/rebuild";
        }
        
        // Delete server
        if ($method === 'DELETE') {
            return "{$baseUrl}/servers/{$serverID}";
        }
        
        // Generic server actions
        return "{$baseUrl}/servers/{$serverID}/actions/{$endpoint}";
    }
    
    // Metrics endpoint without server ID (for bulk operations)
    if (strpos($endpoint, 'servers/') === 0) {
        $url = "{$baseUrl}/{$endpoint}";
        return ($method === 'GET' && !empty($postData)) ? $url . '?' . http_build_query($postData) : $url;
    }
    
    return false;
}

/**
 * Execute the actual API request
 */
function hetznercloud_ExecuteRequest($url, $method, $apiToken, $postData, $endpoint, $serverID)
{
    $headers = [
        "Authorization: Bearer {$apiToken}",
        "Content-Type: application/json",
        "User-Agent: WHMCS-HetznerCloud-Module/2.0"
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3
    ]);

    // Handle POST data
    if ($method === 'POST' && !empty($postData)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log request for debugging
    hetznercloud_LogAPIRequest($method, $endpoint, $serverID, $httpCode, $url, $postData);

    // Handle cURL errors
    if ($curlError) {
        logActivity("Hetzner Cloud - API Error: cURL Error: {$curlError}");
        return ['success' => false, 'message' => 'Connection error: ' . $curlError];
    }

    // Parse response
    $result = json_decode($response, true);
    
    // Handle successful responses
    if ($httpCode >= 200 && $httpCode < 300) {
        logActivity("Hetzner Cloud - API Success: {$method} {$endpoint} - HTTP {$httpCode}");
        return [
            'success' => true,
            'message' => ucfirst($endpoint) . ' command sent successfully!',
            'data' => $result
        ];
    }
    
    // Handle error responses
    $errorMessage = $result['error']['message'] ?? "HTTP {$httpCode}";
    logActivity("Hetzner Cloud - API Error: {$method} {$endpoint} - {$errorMessage}");
    
    return [
        'success' => false,
        'message' => $errorMessage
    ];
}

/**
 * Log API request details
 */
function hetznercloud_LogAPIRequest($method, $endpoint, $serverID, $httpCode, $url, $postData)
{
    $logMessage = "Hetzner Cloud - API Request: {$method} {$endpoint}";
    if ($serverID) $logMessage .= " (Server ID: {$serverID})";
    $logMessage .= " - HTTP {$httpCode}";
    
    // Only log detailed debug info in development
    if (defined('WHMCS_DEBUG') && WHMCS_DEBUG) {
        logActivity("Hetzner Cloud - API Debug: URL: {$url}, Method: {$method}, PostData: " . json_encode($postData));
    }
    
    logActivity($logMessage);
}

/**
 * Get server types with optimized caching
 */
function hetznercloud_GetServerTypes()
{
    $cacheFile = __DIR__ . '/cache/server_types_cache.json';
    $cacheTime = 86400; // 24 hours

    // Ensure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }

    // Check cache validity
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cachedData)) {
            return $cachedData;
        }
    }

    // Fetch from API
    $response = hetznercloud_API_Request('GET', 'server_types', '', '', '');

    if ($response['success'] && isset($response['data']['server_types'])) {
        $serverTypes = array_map(function($type) { 
            return $type['name']; 
        }, $response['data']['server_types']);

        // Cache results
        file_put_contents($cacheFile, json_encode($serverTypes));
        return $serverTypes;
    }

    return [];
}

/**
 * Get API token from WHMCS server settings
 */
function hetznercloud_GetAPIToken()
{
    $result = select_query('tblservers', 'accesshash', ['type' => 'hetznercloud']);
    $data = mysql_fetch_array($result);
    return $data['accesshash'] ?? null;
}

/**
 * Validate server ID format
 */
function hetznercloud_ValidateServerID($serverID)
{
    return is_numeric($serverID) && $serverID > 0;
}

/**
 * Get cached data with automatic refresh
 */
function hetznercloud_GetCachedData($cacheKey, $callback, $ttl = 3600)
{
    $cacheFile = __DIR__ . '/cache/' . md5($cacheKey) . '.json';
    
    // Ensure cache directory exists
    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    
    // Check cache validity
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cachedData)) {
            return $cachedData;
        }
    }
    
    // Fetch fresh data
    $data = $callback();
    
    // Cache results
    if (!empty($data)) {
        file_put_contents($cacheFile, json_encode($data));
    }
    
    return $data;
}


