<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Central Version Configuration
 * Update this file to change version across the entire module
 */
define('HETZNERCLOUD_VERSION', '3.0.0');
define('HETZNERCLOUD_VERSION_NAME', 'Hetzner Cloud Server Automation');
define('HETZNERCLOUD_VERSION_DESC', 'Full Hetzner Cloud automation for WHMCS: provisioning, power management, graphs, snapshots &amp; backups, floating IPs, firewalls, private networks, rDNS, rescue mode, ISO mounting, SSH keys and noVNC console.');
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
        'github' => HETZNERCLOUD_VERSION_GITHUB,
    ];
}
