<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';

/**
 * Client Area Output
 */
function hetznercloud_ClientAreaOutput($params)
{
    // Log the client area access
    logActivity("Hetzner Cloud - Client Area accessed for service ID: " . $params['serviceid']);
    
    // Handle AJAX request for status update
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'status') {
        logActivity("Hetzner Cloud - Status AJAX request for service ID: " . $params['serviceid']);
        $statusInfo = hetznercloud_GetServerStatus($params);
        echo json_encode([
            'success'       => true,
            'serverStatus'  => $statusInfo['status'] ?? 'Unknown',
            'statusColor'   => $statusInfo['color'] ?? 'grey',
            'statusMessage' => $statusInfo['message'] ?? 'No status available',
        ]);
        exit; // Stop further execution
    }

    // Handle AJAX request for metrics
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'metrics') {
        logActivity("Hetzner Cloud - Metrics AJAX request for service ID: " . $params['serviceid']);
        
        // Get start and end parameters for metrics
        $start = isset($_GET['start']) ? (int)$_GET['start'] : null;
        $end = isset($_GET['end']) ? (int)$_GET['end'] : null;
        
        // If no dates provided, use default (last hour)
        if (!$start || !$end) {
            $end = time();
            $start = $end - (60 * 60); // 1 hour ago
        }
        
        logActivity("Hetzner Cloud - Metrics time range: " . date('Y-m-d H:i:s', $start) . " to " . date('Y-m-d H:i:s', $end));
        
        $metrics = hetznercloud_GetServerMetrics($params, $start, $end);
        echo json_encode([
            'success' => true,
            'metrics' => $metrics,
            'timeRange' => [
                'start' => $start,
                'end' => $end,
                'startFormatted' => date('Y-m-d H:i:s', $start),
                'endFormatted' => date('Y-m-d H:i:s', $end)
            ]
        ]);
        exit;
    }

    // Handle AJAX request for ISOs
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'isos') {
        logActivity("Hetzner Cloud - ISOs AJAX request for service ID: " . $params['serviceid']);
        
        // Log the parameters being passed
        logActivity("Hetzner Cloud - ISOs request params: " . json_encode($params));
        
        $isos = hetznercloud_GetAvailableISOs($params);
        
        logActivity("Hetzner Cloud - ISOs response: " . json_encode($isos));
        
        echo json_encode([
            'success' => true,
            'isos' => $isos
        ]);
        exit;
    }

    // Handle ISO cache refresh
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'refresh_isos') {
        logActivity("Hetzner Cloud - Refresh ISOs cache request for service ID: " . $params['serviceid']);
        
        $isos = hetznercloud_RefreshISOCache($params);
        echo json_encode([
            'success' => true,
            'isos' => $isos,
            'message' => 'ISO cache refreshed successfully'
        ]);
        exit;
    }

    // Handle ISO attachment
    if (isset($_POST['modop']) && $_POST['modop'] === 'custom' && isset($_POST['a']) && $_POST['a'] === 'AttachISO') {
        $isoName = $_POST['iso_name'] ?? '';
        
        // Log all POST data for debugging
        logActivity("Hetzner Cloud - AttachISO: POST data received: " . json_encode($_POST));
        logActivity("Hetzner Cloud - AttachISO: ISO name extracted: '{$isoName}'");
        logActivity("Hetzner Cloud - AttachISO: Service ID: " . $params['serviceid']);
        logActivity("Hetzner Cloud - AttachISO: Server ID: " . ($params['customfields']['serverID'] ?? 'NOT_FOUND'));
        
        if (empty($isoName)) {
            logActivity("Hetzner Cloud - AttachISO: No ISO name provided for service ID: " . $params['serviceid']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&error=no_iso');
            exit;
        }
        
        $result = hetznercloud_AttachISO($params, $isoName);
        
        if ($result['success']) {
            logActivity("Hetzner Cloud - AttachISO: Success for service ID: " . $params['serviceid']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&success=iso_attached');
        } else {
            logActivity("Hetzner Cloud - AttachISO: Failed for service ID: " . $params['serviceid'] . ". Error: " . $result['message']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&error=iso_failed&message=' . urlencode($result['message']));
        }
        exit;
    }

    // Handle server rebuild
    if (isset($_POST['modop']) && $_POST['modop'] === 'custom' && isset($_POST['a']) && $_POST['a'] === 'RebuildOS') {
        $newImage = $_POST['new_image'] ?? '';
        
        // Log all POST data for debugging
        logActivity("Hetzner Cloud - RebuildOS: POST data received: " . json_encode($_POST));
        logActivity("Hetzner Cloud - RebuildOS: New image selected: '{$newImage}'");
        logActivity("Hetzner Cloud - RebuildOS: Service ID: " . $params['serviceid']);
        logActivity("Hetzner Cloud - RebuildOS: Server ID: " . ($params['customfields']['serverID'] ?? 'NOT_FOUND'));
        
        if (empty($newImage)) {
            logActivity("Hetzner Cloud - RebuildOS: No new image selected for service ID: " . $params['serviceid']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&error=no_image');
            exit;
        }
        
        $result = hetznercloud_RebuildServer($params, $newImage);
        
        if ($result['success']) {
            logActivity("Hetzner Cloud - RebuildOS: Success for service ID: " . $params['serviceid']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&success=rebuild_initiated');
        } else {
            logActivity("Hetzner Cloud - RebuildOS: Failed for service ID: " . $params['serviceid'] . ". Error: " . $result['message']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&error=rebuild_failed&message=' . urlencode($result['message']));
        }
        exit;
    }

    // Handle ISO unmount
    if (isset($_POST['modop']) && $_POST['modop'] === 'custom' && isset($_POST['a']) && $_POST['a'] === 'UnmountISO') {
        // Log all POST data for debugging
        logActivity("Hetzner Cloud - UnmountISO: POST data received: " . json_encode($_POST));
        logActivity("Hetzner Cloud - UnmountISO: Service ID: " . $params['serviceid']);
        logActivity("Hetzner Cloud - UnmountISO: Server ID: " . ($params['customfields']['serverID'] ?? 'NOT_FOUND'));
        
        $result = hetznercloud_UnmountISO($params);
        
        if ($result['success']) {
            logActivity("Hetzner Cloud - UnmountISO: Success for service ID: " . $params['serviceid']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&success=iso_unmounted');
        } else {
            logActivity("Hetzner Cloud - UnmountISO: Failed for service ID: " . $params['serviceid'] . ". Error: " . $result['message']);
            header('Location: clientarea.php?action=productdetails&id=' . $params['serviceid'] . '&error=unmount_failed&message=' . urlencode($result['message']));
        }
        exit;
    }

    // Fetch required data
    try {
        logActivity("Hetzner Cloud - Fetching server data for service ID: " . $params['serviceid']);
        
        $statusInfo = hetznercloud_GetServerStatus($params);
        $serverInfo = hetznercloud_GetServerDetails($params);
        $consoleLinkData = hetznercloud_GetConsoleLink($params);
        $availableImages = hetznercloud_GetAvailableImages($params);
        $attachedISO = hetznercloud_GetAttachedISO($params);

        if (isset($serverInfo['error'])) {
            logActivity("Hetzner Cloud - Error fetching server details: " . $serverInfo['error']);
            $name = 'Unknown';
            $ip = 'Unknown';
            $image = 'Unknown';
        } else {
            $name = $serverInfo['name'] ?? 'Unknown';
            $ip = $serverInfo['public_net']['ipv4']['ip'] ?? 'Unknown';
            $image = $serverInfo['image']['description'] ?? 'Unknown';
        }

        logActivity("Hetzner Cloud - Server data retrieved successfully. Name: {$name}, IP: {$ip}, Image: {$image}");
        
    } catch (Exception $e) {
        logActivity("Hetzner Cloud - Exception in client area: " . $e->getMessage());
        $statusInfo = ['status' => 'unknown', 'color' => 'gray', 'message' => 'Error loading data'];
        $name = 'Error';
        $ip = 'Error';
        $image = 'Error';
        $consoleLinkData = ['link' => '', 'text' => 'Console Unavailable', 'error' => 'Error loading data'];
        $availableImages = [];
    }

    return [
        'templatefile' => 'clientarea',
        'vars' => [
            'serviceid'     => $params['serviceid'],
            'serverID'      => $params['customfields']['serverID'] ?? null,
            'serverName'    => $name,
            'ip'            => $ip,
            'image'         => $image,
            'serverStatus'  => $statusInfo['status'] ?? 'Unknown',
            'statusColor'   => $statusInfo['color'] ?? 'grey',
            'statusMessage' => $statusInfo['message'] ?? 'No status available',
            'consoleLink'   => $consoleLinkData['link'] ?? '',
            'consoleText'   => $consoleLinkData['text'] ?? 'Web Console (Unavailable)',
            'consoleError'  => $consoleLinkData['error'] ?? '',
            'availableImages' => $availableImages,
            'attachedISO'   => $attachedISO['success'] ? $attachedISO['iso'] : null,
            'username'      => $params['username'] ?? 'root',
            'password'      => $params['password'] ?? 'Click to set password',
            'error'         => $_GET['error'] ?? null,
            'success'       => $_GET['success'] ?? null,
            'message'       => $_GET['message'] ?? null,
            'version'       => HETZNERCLOUD_VERSION,
        ],
    ];
}
