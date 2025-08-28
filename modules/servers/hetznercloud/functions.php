<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/api.php';

/**
 * Create Server on Hetzner Cloud
 */
function hetznercloud_CreateAccount($params)
{
    logActivity("Hetzner Cloud - CreateAccount: Starting server creation for service ID: " . $params['serviceid']);
    
    $hostname = $params['domain']; // Get hostname from WHMCS order
    $location = $params['customfields']['location'] ?? 'fsn1'; // Default location
    $plan = $params['configoption1'] ?? 'cx11'; // Default plan
    $osImage = $params['customfields']['os_image'] ?? 'ubuntu-22.04'; // Default OS

    logActivity("Hetzner Cloud - CreateAccount: Hostname: {$hostname}, Location: {$location}, Plan: {$plan}, OS: {$osImage}");

    // Prepare API request payload
    $postData = [
        "name" => $hostname,
        "server_type" => $plan,
        "location" => $location,
        "image" => $osImage,
        "start_after_create" => true
    ];

    // Call API to create the server
    $response = hetznercloud_API_Request('POST', 'create_server', $params, null, $postData);

    if ($response['success'] && isset($response['data']['server']['id'])) {
        $hetzserverID = $response['data']['server']['id'];
        $rootPassword = $response['data']['root_password'] ?? ''; // Get root password
        $dedicatedIP = $response['data']['server']['public_net']['ipv4']['ip'] ?? ''; // Get dedicated IP

        logActivity("Hetzner Cloud - CreateAccount: Server created successfully. Hetzner ID: {$hetzserverID}, IP: {$dedicatedIP}");

        // Save the server ID in WHMCS custom field
        $serverIDFieldID = getCustomFieldID('serverID', $params['pid']);
        if ($serverIDFieldID) {
            update_query('tblcustomfieldsvalues', [
                'value' => $hetzserverID
            ], [
                'fieldid' => $serverIDFieldID,
                'relid' => $params['serviceid']
            ]);
            logActivity("Hetzner Cloud - CreateAccount: Server ID saved to custom field ID: {$serverIDFieldID}");
        } else {
            logActivity("Hetzner Cloud - CreateAccount: Warning - Could not find serverID custom field");
        }

        // Save the dedicated IP in WHMCS
        if ($dedicatedIP) {
            update_query('tblhosting', [
                'dedicatedip' => $dedicatedIP
            ], [
                'id' => $params['serviceid']
            ]);
            logActivity("Hetzner Cloud - CreateAccount: Dedicated IP saved: {$dedicatedIP}");
        }

        // Save the root password in WHMCS service password field
        if ($rootPassword) {
            update_query('tblhosting', [
                'username' => 'root', // Set username to root
                'password' => encrypt($rootPassword) // Encrypt password before saving
            ], [
                'id' => $params['serviceid']
            ]);
            logActivity("Hetzner Cloud - CreateAccount: Root password saved and encrypted");
        }

        logActivity("Hetzner Cloud - CreateAccount: Server creation completed successfully for service ID: " . $params['serviceid']);
        return 'success';
    } else {
        $errorMsg = $response['message'] ?? 'Unknown error';
        logActivity("Hetzner Cloud - CreateAccount: Failed to create server. Error: " . $errorMsg);
        return "Error: " . $errorMsg;
    }
}

/**
 * Get Custom Field ID by Name
 */
function getCustomFieldID($fieldName, $productID)
{
    $query = select_query('tblcustomfields', 'id, fieldname', [
        'type' => 'product',
        'relid' => $productID
    ]);

    while ($data = mysql_fetch_array($query)) {
        $dbFieldName = $data['fieldname'];

        // Check if the field name starts with the expected name (before "|")
        $fieldParts = explode('|', $dbFieldName);
        if (trim($fieldParts[0]) == $fieldName) {
            logActivity("Hetzner Cloud - Found Custom Field: " . $dbFieldName . " (ID: " . $data['id'] . ")");
            return $data['id'];
        }
    }

    logActivity("Hetzner Cloud - Custom Field Not Found: " . $fieldName . " for Product ID: " . $productID);
    return null;
}


/**
 * Get Web Console Link for Hetzner Cloud
 */
function hetznercloud_GetConsoleLink($params)
{
    logActivity("Hetzner Cloud - GetConsoleLink: Requesting console for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {
        logActivity("Hetzner Cloud - GetConsoleLink: Server ID missing for service ID: " . $params['serviceid']);
        return [
            'fa' => 'fa fa-terminal fa-fw',
            'link' => '',
            'text' => 'Web Console (Unavailable)',
            'error' => 'Error: Server ID is missing!'
        ];
    }

    logActivity("Hetzner Cloud - GetConsoleLink: Requesting console session for server ID: " . $serverID);

    // Request console session from Hetzner API
    $response = hetznercloud_API_Request('POST', 'request_console', $params, $serverID);

    if (!$response['success']) {
        logActivity("Hetzner Cloud - GetConsoleLink: Failed to get console session. Error: " . $response['message']);
        return [
            'fa' => 'fa fa-terminal fa-fw',
            'link' => '',
            'text' => 'Web Console (Unavailable)',
            'error' => 'Error: ' . $response['message']
        ];
    }

    $consoleData = $response['data'] ?? null;
    if (!$consoleData || !isset($consoleData["wss_url"], $consoleData["password"])) {
        logActivity("Hetzner Cloud - GetConsoleLink: Console data incomplete or missing required fields");
        return [
            'fa' => 'fa fa-terminal fa-fw',
            'link' => '',
            'text' => 'Web Console (Unavailable)',
            'error' => 'Error: Failed to retrieve console session.'
        ];
    }

    // Encode console data
    $b64Data = base64_encode('host=' . $consoleData["wss_url"] . '_&_password=' . $consoleData["password"]);

    // Generate URL to console.php
    $consoleURL = '../modules/servers/hetznercloud/console.php?tokens=' . $b64Data;

    logActivity("Hetzner Cloud - GetConsoleLink: Console session created successfully. URL: " . $consoleURL);

    return [
        'fa' => 'fa fa-terminal fa-fw',
        'link' => $consoleURL,
        'text' => 'Web Console',
        'error' => ''
    ];
}


/**
 * Get Full Server Details from Hetzner Cloud
 */
function hetznercloud_GetServerDetails($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        return ['error' => 'Server ID is missing.'];
    }

    // Call Hetzner API for server details using the correct endpoint
    $response = hetznercloud_API_Request('GET', 'server_details', $params, $serverID);

    if (!$response['success'] || empty($response['data']['server'])) {
        return ['error' => "Failed to fetch server details: " . ($response['message'] ?? 'Unknown error')];
    }

    // Return the actual server details
    return $response['data']['server'];
}


/**
 * Suspend Server
 */
function hetznercloud_SuspendAccount($params)
{


    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {

        return "Error: Server ID is missing!";
    }

    // Power off the server
    $powerOffResponse = hetznercloud_API_Request('POST', 'poweroff', $params, $serverID);
    if (!$powerOffResponse['success']) {
        
        return "Error: " . $powerOffResponse['message'];
    }

    // Update WHMCS service status to "Suspended"
    update_query('tblhosting', ['domainstatus' => 'Suspended'], ['id' => $params['serviceid']]);

    return 'success';
} 


/**
 * Unsuspend Server
 */
function hetznercloud_UnsuspendAccount($params)
{


    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    // Power on the server
    $powerOnResponse = hetznercloud_API_Request('POST', 'poweron', $params, $serverID);
    if (!$powerOnResponse['success']) {
        return "Error: " . $powerOnResponse['message'];
    }

    // Update WHMCS service status to "Active"
    update_query('tblhosting', ['domainstatus' => 'Active'], ['id' => $params['serviceid']]);

    return 'success';
}


/**
 * Terminate Server (Delete from Hetzner Cloud)
 */
function hetznercloud_TerminateAccount($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    // Call Hetzner API to delete the server
    $response = hetznercloud_API_Request('DELETE', 'servers', $params, $serverID);
    
    // Update WHMCS service status to "Active"
    update_query('tblhosting', ['domainstatus' => 'Terminiated'], ['id' => $params['serviceid']]);

    return $response['success'] ? 'success' : "Error: " . $response['message'];
}


/**
 * Power On Server
 */
function hetznercloud_PowerOn($params)
{

    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    $response = hetznercloud_API_Request('POST', 'poweron', $params, $serverID);


    return $response['success'] ? 'success' : $response['message'];
}

/**
 * Power Off Server
 */
function hetznercloud_PowerOff($params)
{

    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    $response = hetznercloud_API_Request('POST', 'poweroff', $params, $serverID);


    return $response['success'] ? 'success' : $response['message'];
}

/**
 * Reboot Server
 */
function hetznercloud_Reboot($params)
{

    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    $response = hetznercloud_API_Request('POST', 'reboot', $params, $serverID);


    return $response['success'] ? 'success' : $response['message'];
}

/**
 * Get Server Status from Hetzner Cloud
 */
function hetznercloud_GetServerStatus($params)
{
    $apiToken = $params['serveraccesshash'];
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        logActivity("Hetzner Cloud - GetServerStatus: Server ID missing for service ID: " . $params['serviceid']);
        return ['status' => 'unknown', 'color' => 'gray', 'message' => 'Server ID missing'];
    }

    $url = "https://api.hetzner.cloud/v1/servers/{$serverID}";

    $headers = [
        "Authorization: Bearer {$apiToken}",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        logActivity("Hetzner Cloud - GetServerStatus: cURL error: " . $curlError . " for server ID: " . $serverID);
        return ['status' => 'unknown', 'color' => 'gray', 'message' => 'Connection error'];
    }

    $data = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($data['server']['status'])) {
        $status = $data['server']['status'];

        // Define color and message based on status
        $statusMap = [
            'running' => ['color' => 'green', 'message' => 'Online'],
            'off' => ['color' => 'red', 'message' => 'Offline'],
            'starting' => ['color' => 'orange', 'message' => 'Starting...'],
            'stopping' => ['color' => 'orange', 'message' => 'Stopping...'],
            'rebuilding' => ['color' => 'blue', 'message' => 'Rebuilding...'],
            'unknown' => ['color' => 'gray', 'message' => 'Unknown']
        ];

        $result = [
            'status' => $status,
            'color' => $statusMap[$status]['color'] ?? 'gray',
            'message' => $statusMap[$status]['message'] ?? 'Unknown'
        ];

        logActivity("Hetzner Cloud - GetServerStatus: Successfully retrieved status '" . $status . "' for server ID: " . $serverID);
        return $result;
    }

    $errorMessage = isset($data['error']['message']) ? $data['error']['message'] : 'HTTP ' . $httpCode;
    logActivity("Hetzner Cloud - GetServerStatus: Failed to fetch status. Error: " . $errorMessage . " for server ID: " . $serverID);
    
    return ['status' => 'unknown', 'color' => 'gray', 'message' => 'Failed to fetch status'];
}

/**
 * Rebuild Server with New OS
 */
function hetznercloud_RebuildServer($params, $newImage = null)
{
    logActivity("Hetzner Cloud - RebuildServer: Starting rebuild for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;
    
    // Use passed parameter or fallback to customfield
    if (!$newImage) {
        $newImage = $params['customfields']['new_image'] ?? 'ubuntu-22.04';
    }

    if (!$serverID) {
        logActivity("Hetzner Cloud - RebuildServer: Server ID missing for service ID: " . $params['serviceid']);
        return ['success' => false, 'message' => 'Server ID is missing!'];
    }

    if (!$newImage) {
        logActivity("Hetzner Cloud - RebuildServer: No new image specified for service ID: " . $params['serviceid']);
        return ['success' => false, 'message' => 'No new image specified!'];
    }

    logActivity("Hetzner Cloud - RebuildServer: Rebuilding server ID: {$serverID} with image: {$newImage}");

    // First, power off the server if it's running
    logActivity("Hetzner Cloud - RebuildServer: Powering off server before rebuild");
    $powerOffResponse = hetznercloud_API_Request('POST', 'poweroff', $params, $serverID);
    if (!$powerOffResponse['success']) {
        logActivity("Hetzner Cloud - RebuildServer: Failed to power off server. Error: " . $powerOffResponse['message']);
        return ['success' => false, 'message' => 'Failed to power off server - ' . $powerOffResponse['message']];
    }

    logActivity("Hetzner Cloud - RebuildServer: Server powered off successfully, waiting 5 seconds");

    // Wait a moment for the server to power off
    sleep(5);

    // Rebuild the server with new image
    logActivity("Hetzner Cloud - RebuildServer: Starting rebuild with new image");
    $postData = [
        "image" => $newImage
    ];

    $response = hetznercloud_API_Request('POST', 'rebuild', $params, $serverID, $postData);

    if ($response['success']) {
        logActivity("Hetzner Cloud - RebuildServer: Rebuild initiated successfully");
        
        // Check if we got a new root password from the API response
        if (isset($response['data']['root_password'])) {
            $newPassword = $response['data']['root_password'];
            logActivity("Hetzner Cloud - RebuildServer: New root password received, updating WHMCS");
            
            // Update the password in WHMCS
            update_query('tblhosting', [
                'password' => encrypt($newPassword)
            ], [
                'id' => $params['serviceid']
            ]);
            
            logActivity("Hetzner Cloud - RebuildServer: Root password updated in WHMCS for service ID: " . $params['serviceid']);
        } else {
            logActivity("Hetzner Cloud - RebuildServer: No new root password in response");
        }
        
        // Update the image description in WHMCS
        $serverInfo = hetznercloud_GetServerDetails($params);
        if (isset($serverInfo['image']['description'])) {
            logActivity("Hetzner Cloud - RebuildServer: Server rebuilt with new image: " . $serverInfo['image']['description']);
        }
        
        logActivity("Hetzner Cloud - RebuildServer: Rebuild completed successfully for service ID: " . $params['serviceid']);
        return ['success' => true, 'message' => 'Server rebuild initiated successfully'];
    } else {
        logActivity("Hetzner Cloud - RebuildServer: Rebuild failed. Error: " . $response['message']);
        return ['success' => false, 'message' => $response['message']];
    }
}

/**
 * Reset Root Password
 */
function hetznercloud_ResetPassword($params)
{
    logActivity("Hetzner Cloud - ResetPassword: Starting password reset for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        logActivity("Hetzner Cloud - ResetPassword: Server ID missing for service ID: " . $params['serviceid']);
        return "Error: Server ID is missing!";
    }

    logActivity("Hetzner Cloud - ResetPassword: Resetting password for server ID: " . $serverID);

    // Reset root password via Hetzner API
    $response = hetznercloud_API_Request('POST', 'reset_password', $params, $serverID);

    if ($response['success'] && isset($response['data']['root_password'])) {
        $newPassword = $response['data']['root_password'];
        
        logActivity("Hetzner Cloud - ResetPassword: Password reset successful, updating WHMCS");
        
        // Update the password in WHMCS
        update_query('tblhosting', [
            'password' => encrypt($newPassword)
        ], [
            'id' => $params['serviceid']
        ]);

        logActivity("Hetzner Cloud - ResetPassword: Root password reset and updated in WHMCS for server ID: " . $serverID);
        return 'success';
    } else {
        $errorMsg = $response['message'] ?? 'Failed to reset password';
        logActivity("Hetzner Cloud - ResetPassword: Password reset failed. Error: " . $errorMsg);
        return "Error: " . $errorMsg;
    }
}

/**
 * Get Server Metrics for Graphs
 */
function hetznercloud_GetServerMetrics($params, $startTime = null, $endTime = null)
{
    logActivity("Hetzner Cloud - GetServerMetrics: Requesting metrics for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
        logActivity("Hetzner Cloud - GetServerMetrics: Server ID missing for service ID: " . $params['serviceid']);
        return ['error' => 'Server ID is missing.'];
    }

    logActivity("Hetzner Cloud - GetServerMetrics: Fetching metrics for server ID: " . $serverID);

    // Use provided time range or default to last 24 hours
    if ($startTime === null || $endTime === null) {
        $endTime = time();
        $startTime = $endTime - (24 * 60 * 60); // 24 hours ago
    }

    logActivity("Hetzner Cloud - GetServerMetrics: Time range: " . date('Y-m-d H:i:s', $startTime) . " to " . date('Y-m-d H:i:s', $endTime));

    // Get CPU metrics
    logActivity("Hetzner Cloud - GetServerMetrics: Fetching CPU metrics");
    $cpuResponse = hetznercloud_API_Request('GET', 'servers/' . $serverID . '/metrics', $params, null, [
        'type' => 'cpu',
        'start' => date('c', $startTime), // Convert to ISO 8601 format
        'end' => date('c', $endTime)      // Convert to ISO 8601 format
    ]);

    // Get disk metrics (instead of memory)
    logActivity("Hetzner Cloud - GetServerMetrics: Fetching disk metrics");
    $diskResponse = hetznercloud_API_Request('GET', 'servers/' . $serverID . '/metrics', $params, null, [
        'type' => 'disk',
        'start' => date('c', $startTime), // Convert to ISO 8601 format
        'end' => date('c', $endTime)      // Convert to ISO 8601 format
    ]);

    // Get network metrics
    logActivity("Hetzner Cloud - GetServerMetrics: Fetching network metrics");
    $networkResponse = hetznercloud_API_Request('GET', 'servers/' . $serverID . '/metrics', $params, null, [
        'type' => 'network',
        'start' => date('c', $startTime), // Convert to ISO 8601 format
        'end' => date('c', $endTime)      // Convert to ISO 8601 format
    ]);

    $metrics = [
        'cpu' => $cpuResponse['success'] ? $cpuResponse['data'] : null,
        'disk' => $diskResponse['success'] ? $diskResponse['data'] : null,
        'network' => $networkResponse['success'] ? $networkResponse['data'] : null,
        'timestamp' => time()
    ];

    // Log the results
    $cpuStatus = $cpuResponse['success'] ? 'success' : 'failed';
    $diskStatus = $diskResponse['success'] ? 'success' : 'failed';
    $networkStatus = $networkResponse['success'] ? 'success' : 'failed';
    
    logActivity("Hetzner Cloud - GetServerMetrics: CPU: {$cpuStatus}, Disk: {$diskStatus}, Network: {$networkStatus}");

    if (!$cpuResponse['success'] || !$diskResponse['success'] || !$networkResponse['success']) {
        logActivity("Hetzner Cloud - GetServerMetrics: Some metrics failed to load. CPU: " . ($cpuResponse['message'] ?? 'N/A') . ", Disk: " . ($diskResponse['message'] ?? 'N/A') . ", Network: " . ($networkResponse['message'] ?? 'N/A'));
    }

    return $metrics;
}

/**
 * Get Available OS Images
 */
function hetznercloud_GetAvailableImages($params)
{
    $cacheFolder = __DIR__ . '/cache';
    $cacheFile = $cacheFolder . '/images_cache.json';
    $cacheTime = 86400; // 24 hours

    // Ensure cache folder exists
    if (!file_exists($cacheFolder)) {
        mkdir($cacheFolder, 0755, true);
    }

    // Check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cachedData)) {
            return $cachedData;
        }
    }

    // Fetch from API
    $response = hetznercloud_API_Request('GET', 'images', $params, '', '');

    if ($response['success'] && isset($response['data']['images'])) {
        $images = [];
        foreach ($response['data']['images'] as $image) {
            if ($image['type'] === 'system') {
                $images[] = [
                    'id' => $image['id'],
                    'name' => $image['name'],
                    'description' => $image['description'],
                    'os_flavor' => $image['os_flavor'],
                    'os_version' => $image['os_version']
                ];
            }
        }

        // Cache results
        file_put_contents($cacheFile, json_encode($images));
        return $images;
    }

    return [];
}

/**
 * Ensure cache folder exists with proper permissions
 */
function hetznercloud_EnsureCacheFolder()
{
    $cacheFolder = __DIR__ . '/cache';
    
    if (!file_exists($cacheFolder)) {
        if (!mkdir($cacheFolder, 0755, true)) {
            logActivity("Hetzner Cloud - Cache: Failed to create cache folder: {$cacheFolder}");
            return false;
        }
        logActivity("Hetzner Cloud - Cache: Created cache folder: {$cacheFolder}");
    }
    
    if (!is_writable($cacheFolder)) {
        logActivity("Hetzner Cloud - Cache: Cache folder not writable: {$cacheFolder}");
        return false;
    }
    
    return true;
}

/**
 * Get Available ISOs
 */
function hetznercloud_GetAvailableISOs($params)
{
    // Ensure cache folder exists
    if (!hetznercloud_EnsureCacheFolder()) {
        logActivity("Hetzner Cloud - GetAvailableISOs: Cache folder issue, proceeding without cache");
    }
    
    $cacheFolder = __DIR__ . '/cache';
    $cacheFile = $cacheFolder . '/isos_cache.json';
    $cacheTime = 86400; // 24 hours - ISOs don't change often
    
    // Check if cache exists and is valid
    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        
        // If cache is still valid, return cached data
        if ($cacheAge < $cacheTime) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            if (!empty($cachedData) && isset($cachedData['isos'])) {
                logActivity("Hetzner Cloud - GetAvailableISOs: Returning cached ISOs (cache age: {$cacheAge}s)");
                return $cachedData['isos'];
            }
        } else {
            logActivity("Hetzner Cloud - GetAvailableISOs: Cache expired (age: {$cacheAge}s), refreshing...");
        }
    }
    
    logActivity("Hetzner Cloud - GetAvailableISOs: Fetching fresh ISOs from API");
    
    // Fetch from API
    $response = hetznercloud_API_Request('GET', 'isos', $params, '', '');
    
    logActivity("Hetzner Cloud - GetAvailableISOs: API Response - " . json_encode($response));
    
    if ($response['success'] && isset($response['data']['isos'])) {
        $isos = [];
        foreach ($response['data']['isos'] as $iso) {
            $isos[] = [
                'id' => $iso['id'],
                'name' => $iso['name'],
                'description' => $iso['description'],
                'type' => $iso['type'],
                'architecture' => $iso['architecture'] ?? null
            ];
        }
        
        // Cache the results
        $cacheData = [
            'timestamp' => time(),
            'count' => count($isos),
            'isos' => $isos
        ];
        
        if (hetznercloud_EnsureCacheFolder() && file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT))) {
            logActivity("Hetzner Cloud - GetAvailableISOs: Cached " . count($isos) . " ISOs successfully");
        } else {
            logActivity("Hetzner Cloud - GetAvailableISOs: Failed to cache ISOs");
        }
        
        return $isos;
    }
    
    logActivity("Hetzner Cloud - GetAvailableISOs: Failed to fetch ISOs from API. Response: " . json_encode($response));
    return [];
}

/**
 * Force refresh ISO cache
 */
function hetznercloud_RefreshISOCache($params)
{
    $cacheFolder = __DIR__ . '/cache';
    $cacheFile = $cacheFolder . '/isos_cache.json';
    
    // Delete existing cache
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        logActivity("Hetzner Cloud - RefreshISOCache: Deleted existing ISO cache");
    }
    
    // Fetch fresh data
    return hetznercloud_GetAvailableISOs($params);
}

/**
 * Attach ISO to Server
 */
function hetznercloud_AttachISO($params, $isoName)
{
    logActivity("Hetzner Cloud - AttachISO: Starting ISO attachment for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;
    
    if (!$serverID) {
        logActivity("Hetzner Cloud - AttachISO: Server ID missing for service ID: " . $params['serviceid']);
        return ['success' => false, 'message' => 'Server ID is missing!'];
    }
    
    if (empty($isoName)) {
        logActivity("Hetzner Cloud - AttachISO: ISO name is empty for service ID: " . $params['serviceid']);
        return ['success' => false, 'message' => 'ISO name is required!'];
    }
    
    logActivity("Hetzner Cloud - AttachISO: Attaching ISO '{$isoName}' to server ID: {$serverID}");
    
    $postData = [
        "iso" => $isoName
    ];
    
    $response = hetznercloud_API_Request('POST', 'attach_iso', $params, $serverID, $postData);
    
    if ($response['success']) {
        logActivity("Hetzner Cloud - AttachISO: Successfully attached ISO '{$isoName}' to server ID: {$serverID}");
        return ['success' => true, 'message' => 'ISO attached successfully'];
    } else {
        logActivity("Hetzner Cloud - AttachISO: Failed to attach ISO '{$isoName}' to server ID: {$serverID}. Error: " . $response['message']);
        return ['success' => false, 'message' => $response['message']];
    }
}

/**
 * Unmount ISO from Server
 */
function hetznercloud_UnmountISO($params)
{
    logActivity("Hetzner Cloud - UnmountISO: Starting ISO unmount for service ID: " . $params['serviceid']);
    
    $serverID = $params['customfields']['serverID'] ?? null;
    
    if (!$serverID) {
        logActivity("Hetzner Cloud - UnmountISO: Server ID missing for service ID: " . $params['serviceid']);
        return ['success' => false, 'message' => 'Server ID is missing!'];
    }
    
    logActivity("Hetzner Cloud - UnmountISO: Unmounting ISO from server ID: {$serverID}");
    
    // Detach ISO using the detach_iso endpoint
    $response = hetznercloud_API_Request('POST', 'detach_iso', $params, $serverID);
    
    if ($response['success']) {
        logActivity("Hetzner Cloud - UnmountISO: Successfully unmounted ISO from server ID: {$serverID}");
        return ['success' => true, 'message' => 'ISO unmounted successfully'];
    } else {
        logActivity("Hetzner Cloud - UnmountISO: Failed to unmount ISO from server ID: {$serverID}. Error: " . $response['message']);
        return ['success' => false, 'message' => $response['message']];
    }
}

/**
 * Get Currently Attached ISO
 */
function hetznercloud_GetAttachedISO($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;
    
    if (!$serverID) {
        return ['success' => false, 'message' => 'Server ID is missing'];
    }

    logActivity("Hetzner Cloud - GetAttachedISO: Checking attached ISO for server ID: {$serverID}");

    // Get server details to check for attached ISO
    $serverResponse = hetznercloud_API_Request('GET', 'servers/' . $serverID, $params, $serverID);
    
    if ($serverResponse['success'] && isset($serverResponse['data']['server'])) {
        $server = $serverResponse['data']['server'];
        
        if (isset($server['iso']) && $server['iso']) {
            logActivity("Hetzner Cloud - GetAttachedISO: Server {$serverID} has ISO attached: " . $server['iso']['name']);
            return [
                'success' => true,
                'iso' => [
                    'id' => $server['iso']['id'],
                    'name' => $server['iso']['name'],
                    'description' => $server['iso']['description'] ?? ''
                ]
            ];
        } else {
            logActivity("Hetzner Cloud - GetAttachedISO: Server {$serverID} has no ISO attached");
            return ['success' => true, 'iso' => null];
        }
    }

    return ['success' => false, 'message' => 'Failed to get server details'];
}