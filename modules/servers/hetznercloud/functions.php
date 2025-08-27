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
    $hostname = $params['domain']; // Get hostname from WHMCS order
    $location = $params['customfields']['location'] ?? 'fsn1'; // Default location
    $plan = $params['configoption1'] ?? 'cx11'; // Default plan
    $osImage = $params['customfields']['os_image'] ?? 'ubuntu-22.04'; // Default OS

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

        // Save the server ID in WHMCS custom field
        $serverIDFieldID = getCustomFieldID('serverID', $params['pid']);
        if ($serverIDFieldID) {
            update_query('tblcustomfieldsvalues', [
                'value' => $hetzserverID
            ], [
                'fieldid' => $serverIDFieldID,
                'relid' => $params['serviceid']
            ]);
        }

        // Save the dedicated IP in WHMCS
        if ($dedicatedIP) {
            update_query('tblhosting', [
                'dedicatedip' => $dedicatedIP
            ], [
                'id' => $params['serviceid']
            ]);
        }

        // Save the root password in WHMCS service password field
        if ($rootPassword) {
            update_query('tblhosting', [
                'username' => 'root', // Set username to root
                'password' => encrypt($rootPassword) // Encrypt password before saving
            ], [
                'id' => $params['serviceid']
            ]);
        }

        return 'success';
    } else {
        return "Error: " . ($response['message'] ?? 'Unknown error');
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
    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {
        return [
            'fa' => 'fa fa-terminal fa-fw',
            'link' => '',
            'text' => 'Web Console (Unavailable)',
            'error' => 'Error: Server ID is missing!'
        ];
    }

    // Request console session from Hetzner API
    $response = hetznercloud_API_Request('POST', 'request_console', $params, $serverID);

    if (!$response['success']) {
        return [
            'fa' => 'fa fa-terminal fa-fw',
            'link' => '',
            'text' => 'Web Console (Unavailable)',
            'error' => 'Error: ' . $response['message']
        ];
    }

    $consoleData = $response['data'] ?? null;
    if (!$consoleData || !isset($consoleData["wss_url"], $consoleData["password"])) {
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
 * Rebuild Server with current image
 */
function hetznercloud_Rebuild($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;
    $image = $params['customfields']['os_image'] ?? null;

    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    $postData = [];
    if ($image) {
        $postData['image'] = $image;
    }

    $response = hetznercloud_API_Request('POST', 'rebuild', $params, $serverID, $postData);

    return $response['success'] ? 'success' : $response['message'];
}

/**
 * Reset root password for the server
 */
function hetznercloud_ResetPassword($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {
        return "Error: Server ID is missing!";
    }

    $response = hetznercloud_API_Request('POST', 'reset_password', $params, $serverID);

    if ($response['success'] && isset($response['data']['root_password'])) {
        // Save the new password in WHMCS
        update_query('tblhosting', [
            'username' => 'root',
            'password' => encrypt($response['data']['root_password'])
        ], [
            'id' => $params['serviceid']
        ]);
        return 'success';
    }

    return $response['success'] ? 'success' : $response['message'];
}

/**
 * Fetch usage metrics for graphs
 */
function hetznercloud_GetUsageMetrics($params)
{
    $serverID = $params['customfields']['serverID'] ?? null;
    if (!$serverID) {
        return ['success' => false, 'message' => 'Server ID is missing'];
    }

    $end = time();
    $start = $end - 3600; // last hour
    $query = [
        'type' => 'cpu,disk,cpu_rate,network',
        'start' => gmdate('c', $start),
        'end' => gmdate('c', $end),
        'step' => 60
    ];

    $response = hetznercloud_API_Request('GET', 'metrics', $params, $serverID, $query);

    return $response;
}

/**
 * Get Server Status from Hetzner Cloud
 */
function hetznercloud_GetServerStatus($params)
{
    $apiToken = $params['serveraccesshash'];
    $serverID = $params['customfields']['serverID'] ?? null;

    if (!$serverID) {
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

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

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

        return [
            'status' => $status,
            'color' => $statusMap[$status]['color'] ?? 'gray',
            'message' => $statusMap[$status]['message'] ?? 'Unknown'
        ];
    }

    return ['status' => 'unknown', 'color' => 'gray', 'message' => 'Failed to fetch status'];
}