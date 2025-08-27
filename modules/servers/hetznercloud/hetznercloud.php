<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/functions.php';

/**
 * Module Meta Data
 */
function hetznercloud_MetaData()
{
    return [
        'DisplayName' => 'Hetzner Cloud Server Automation',
        'APIVersion' => '1.0',
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
        "Rebuild" => "Rebuild",
        "Reset Password" => "ResetPassword",
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
