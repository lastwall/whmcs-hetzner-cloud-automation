<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Central Version Configuration
 * Update this file to change version across the entire module
 */
define('HETZNERCLOUD_VERSION', '2.0.0');
define('HETZNERCLOUD_VERSION_NAME', 'Hetzner Cloud Server Automation');
define('HETZNERCLOUD_VERSION_DESC', 'Advanced Free Hetzner Cloud automation module with real-time monitoring, OS management, web console, and comprehensive server management.');
define('HETZNERCLOUD_VERSION_AUTHOR', 'LastWall');
define('HETZNERCLOUD_VERSION_GITHUB', 'https://github.com/lastwall/whmcs-hetzner-cloud-automation');

/**
 * Get Module Version Information
 */
function hetznercloud_GetVersion()
{
    return [
        'version' => HETZNERCLOUD_VERSION,
        'name' => HETZNERCLOUD_VERSION_NAME,
        'author' => HETZNERCLOUD_VERSION_AUTHOR,
        'github' => HETZNERCLOUD_VERSION_GITHUB
    ];
}
