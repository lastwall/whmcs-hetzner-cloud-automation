<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/functions.php';

/**
 * Module Meta Data
 */
function hetznercloud_MetaData()
{
    return [
        'DisplayName' => HETZNERCLOUD_VERSION_NAME,
        'APIVersion' => '1.0',
        'RequiresServer' => false,
        'ServiceSingleSignOnLabel' => false,
        'AdminSingleSignOnLabel' => false,
        'Version' => HETZNERCLOUD_VERSION,
        'Author' => HETZNERCLOUD_VERSION_AUTHOR,
        'Description' => HETZNERCLOUD_VERSION_DESC,
    ];
}

/**
 * Server Configuration Fields
 */
function hetznercloud_ConfigOptions($params)
{
    $serverTypes = hetznercloud_GetServerTypes($params);

    if (!$serverTypes) {
        return [
            'Server Type' => [
                'Type' => 'text',
                'Default' => 'cx11',
                'Description' => 'Error fetching server types. Check API credentials.',
            ],
        ];
    }

    return [
        'Server Type' => [
            'Type' => 'dropdown',
            'Options' => implode(',', $serverTypes),
            'Description' => 'Choose the Hetzner server type',
        ],
    ];
}



/**
 * Client Area Custom Buttons
 */
function hetznercloud_ClientAreaCustomButtonArray()
{
    return [
        "Power On" => "PowerOn",
        "Power Off" => "PowerOff",
        "Reboot" => "Reboot",
        "Rebuild OS" => "RebuildOS",
        "Reset Password" => "ResetPassword",
        "View Metrics" => "ViewMetrics",
    ];
}

/**
 * Client Area Output - Load Template
 */
function hetznercloud_ClientArea($params)
{
    require_once __DIR__ . '/clientarea.php';
    return hetznercloud_ClientAreaOutput($params);
}
