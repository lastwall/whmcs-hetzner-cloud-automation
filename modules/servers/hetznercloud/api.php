<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Send API Request to Hetzner Cloud
 */
function hetznercloud_API_Request($method, $endpoint, $params, $serverID = null, $postData = [])
{
    $apiToken = $params['serveraccesshash'] ?? hetznercloud_GetAPIToken(); // Get API Token from WHMCS server settings Access Hash

    // Determine URL based on endpoint
    if ($endpoint === 'create_server') {
        $url = "https://api.hetzner.cloud/v1/servers";
    } elseif ($endpoint === 'server_types') {
        $url = "https://api.hetzner.cloud/v1/server_types"; // Fetch server types
    } elseif (!$serverID) {
        return ['success' => false, 'message' => 'Server ID is missing'];
    } elseif ($method === 'GET' && $endpoint === 'server_details') {
        $url = "https://api.hetzner.cloud/v1/servers/{$serverID}";
    } elseif ($method === 'DELETE') {
        $url = "https://api.hetzner.cloud/v1/servers/{$serverID}";
    } else {
        $url = "https://api.hetzner.cloud/v1/servers/{$serverID}/actions/{$endpoint}";
    }

    $headers = [
        "Authorization: Bearer {$apiToken}",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Handle POST requests
    if ($method === 'POST') {
        if ($endpoint === 'create_server') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}'); // Empty JSON object for other POST requests
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'message' => ucfirst($endpoint) . ' command sent successfully!',
            'data' => $result // Return full API response
        ];
    } else {
        return [
            'success' => false,
            'message' => $result['error']['message'] ?? 'API request failed'
        ];
    }
}

function hetznercloud_GetServerTypes()
{
    $cacheFolder = __DIR__ . '/cache';
    $cacheFile = $cacheFolder . '/server_types_cache.json';
    $cacheTime = 86400; // 24 hours

    // Ensure cache folder exists
    if (!file_exists($cacheFolder)) {
        mkdir($cacheFolder, 0755, true); // Create folder with proper permissions
    }

    // Check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cachedData)) {
            return $cachedData;
        }
    }

    // Fetch from API
    $response = hetznercloud_API_Request('GET', 'server_types', '', '', '');

    if ($response['success'] && isset($response['data']['server_types'])) {
        $serverTypes = array_map(fn($type) => $type['name'], $response['data']['server_types']);

        // Cache results
        file_put_contents($cacheFile, json_encode($serverTypes));

        return $serverTypes;
    }

    return [];
}


function hetznercloud_GetAPIToken()
{
    $result = select_query('tblservers', 'accesshash', [
        'type' => 'hetznercloud'
    ]);

    $data = mysql_fetch_array($result);
    return $data['accesshash'] ?? null;
}


