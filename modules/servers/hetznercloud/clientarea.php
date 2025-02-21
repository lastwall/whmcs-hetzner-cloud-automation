<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/api.php';

/**
 * Client Area Output
 */
function hetznercloud_ClientAreaOutput($params)
{
    // Handle AJAX request for status update
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'status') {
        $statusInfo = hetznercloud_GetServerStatus($params);
        echo json_encode([
            'success'       => true,
            'serverStatus'  => $statusInfo['status'] ?? 'Unknown',
            'statusColor'   => $statusInfo['color'] ?? 'grey',
            'statusMessage' => $statusInfo['message'] ?? 'No status available',
        ]);
        exit; // Stop further execution
    }

    // Fetch required data
    $statusInfo = hetznercloud_GetServerStatus($params);
    $serverInfo = hetznercloud_GetServerDetails($params);
    $consoleLinkData = hetznercloud_GetConsoleLink($params);

    $name   = $serverInfo['data']['server']['name'];
    $ip     = $serverInfo['public_net']['ipv4']['ip'];
    $image  = $serverInfo['image']['description'];

    return [
        'templatefile' => 'clientarea',
        'vars' => [
            'serviceid'     => $params['serviceid'],
            'serverID'      => $params['customfields']['serverID'] ?? null,
            'ip'            => $ip ?? null,
            'image'         => $image ?? null,
            'serverStatus'  => $statusInfo['status'] ?? 'Unknown',
            'statusColor'   => $statusInfo['color'] ?? 'grey',
            'statusMessage' => $statusInfo['message'] ?? 'No status available',
            'consoleLink'   => $consoleLinkData['link'] ?? '',
            'consoleText'   => $consoleLinkData['text'] ?? 'Web Console (Unavailable)',
            'consoleError'  => $consoleLinkData['error'] ?? '',
        ],
    ];
}
