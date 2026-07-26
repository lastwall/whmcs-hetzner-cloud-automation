<?php
/**
 * WHMCS provisioning module: Hetzner Cloud
 *
 * https://github.com/lastwall/whmcs-hetzner-cloud-automation
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/setup.php';

/* =========================================================================
 * Metadata & configuration
 * ====================================================================== */

function hetznercloud_MetaData()
{
    return [
        'DisplayName' => HETZNERCLOUD_VERSION_NAME,
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
        'ServiceSingleSignOnLabel' => 'Open control panel',
        'AdminSingleSignOnLabel' => false,
        'Version' => HETZNERCLOUD_VERSION,
        'Author' => HETZNERCLOUD_VERSION_AUTHOR,
        'Description' => HETZNERCLOUD_VERSION_DESC,
    ];
}

function hetznercloud_ConfigOptions($params = [])
{
    hetznercloud_InstallTables();

    // "Run setup now" was saved on the previous submit: do the work, then reset.
    if (!empty($params['pid']) && stripos((string) ($params['configoption17'] ?? ''), 'run') === 0) {
        hetznercloud_SetupProduct($params['pid'], $params);
        try {
            Capsule::table('tblproducts')->where('id', (int) $params['pid'])->update(['configoption17' => 'No']);
        } catch (\Exception $e) {
            // non fatal
        }
    }

    $typePairs = hetznercloud_TypePairs($params);
    $locationPairs = hetznercloud_LocationPairs($params);
    $imagePairs = hetznercloud_ImagePairs($params);

    if (!$typePairs) {
        $typePairs = ['cx22' => 'CX22 (API unreachable - check the Access Hash)'];
    }
    if (!$locationPairs) {
        $locationPairs = ['fsn1' => 'Falkenstein DE (fsn1)'];
    }
    if (!$imagePairs) {
        $imagePairs = ['ubuntu-24.04' => 'Ubuntu 24.04'];
    }

    return [
        'Server Name Prefix' => [
            'Type' => 'text',
            'Size' => '20',
            'Default' => 'vps',
            'Description' => 'Used when the order has no hostname. Result: prefix-serviceid',
        ],
        'Server Type' => [
            'Type' => 'dropdown',
            'Options' => hetznercloud_DropdownOptions($typePairs),
            'Description' => 'Default plan. A "Server Type" configurable option overrides this.',
        ],
        'Location' => [
            'Type' => 'dropdown',
            'Options' => hetznercloud_DropdownOptions($locationPairs),
            'Description' => 'Default datacenter location.',
        ],
        'OS Image' => [
            'Type' => 'dropdown',
            'Options' => hetznercloud_DropdownOptions($imagePairs),
            'Description' => 'Default operating system.',
        ],
        'Enable Backups' => [
            'Type' => 'yesno',
            'Description' => 'Turn on Hetzner automatic backups at provisioning (+20% cost).',
        ],
        'Auto Mount Volume' => [
            'Type' => 'yesno',
            'Description' => 'Mount the extra volume automatically inside the server.',
        ],
        'Volume Size (GB)' => [
            'Type' => 'text',
            'Size' => '6',
            'Default' => '0',
            'Description' => '0 disables the extra volume. Minimum 10 GB.',
        ],
        'Volume Format' => [
            'Type' => 'dropdown',
            'Options' => 'ext4|Ext4,xfs|XFS',
            'Description' => 'Filesystem created on the extra volume.',
        ],
        'Number Of Floating IPs' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '0',
            'Description' => 'Floating IPs created with the server, and the client-area limit.',
        ],
        'Floating IP Protocol' => [
            'Type' => 'dropdown',
            'Options' => 'ipv4|IPv4,ipv6|IPv6,both|IPv4 + IPv6',
            'Description' => 'Which kind of floating IP the customer gets.',
        ],
        'Price Per Additional Floating IP' => [
            'Type' => 'text',
            'Size' => '8',
            'Default' => '0.00',
            'Description' => 'Shown in the client area. Bill it with a configurable option.',
        ],
        'Snapshot Limit' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '0',
            'Description' => '0 hides the snapshot section from the client area.',
        ],
        'Firewall Limit' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '0',
            'Description' => '0 hides the firewall section from the client area.',
        ],
        'Inbound Firewall Rules Limit' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '10',
            'Description' => 'Maximum inbound rules per firewall.',
        ],
        'Outbound Firewall Rules Limit' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '10',
            'Description' => 'Maximum outbound rules per firewall.',
        ],
        'Private Network Limit' => [
            'Type' => 'text',
            'Size' => '4',
            'Default' => '0',
            'Description' => '0 hides private networks from the client area.',
        ],
        'Auto Setup Product' => [
            'Type' => 'dropdown',
            'Options' => 'No,Run setup now',
            'Description' => 'Select "Run setup now" and save. The module creates the serverID / ssh_key '
                . 'custom fields and a full configurable option group for this product, then resets to No. '
                . 'Client-area features are switched on and off in Addons > Hetzner Cloud.',
        ],
    ];
}

/**
 * Server "Test Connection" button.
 */
function hetznercloud_TestConnection($params)
{
    $response = hetznercloud_Request($params, 'GET', 'locations', null, ['per_page' => 1]);

    if ($response['success']) {
        hetznercloud_InstallTables();
        return ['success' => true, 'error' => ''];
    }

    return ['success' => false, 'error' => $response['message']];
}

/* =========================================================================
 * Provisioning entry points
 * ====================================================================== */

function hetznercloud_CreateAccount($params)
{
    try {
        if (hetznercloud_ServerId($params)) {
            return 'Error: this service already has a Hetzner server attached.';
        }

        $result = hetznercloud_Provision($params);

        return $result['success'] ? 'success' : 'Error: ' . $result['message'];
    } catch (\Exception $e) {
        hetznercloud_LogCall('CreateAccount', $params['serviceid'] ?? 0, $e->getMessage());
        return 'Error: ' . $e->getMessage();
    }
}

function hetznercloud_SuspendAccount($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return 'Error: Server ID is missing.';
    }

    $response = hetznercloud_Action($params, $serverId, 'poweroff');

    return $response['success'] ? 'success' : 'Error: ' . $response['message'];
}

function hetznercloud_UnsuspendAccount($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return 'Error: Server ID is missing.';
    }

    $response = hetznercloud_Action($params, $serverId, 'poweron');

    return $response['success'] ? 'success' : 'Error: ' . $response['message'];
}

function hetznercloud_TerminateAccount($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return 'Error: Server ID is missing.';
    }

    $errors = [];

    if (hetznercloud_Setting('terminate_cleanup', 'on') === 'on') {
        $errors = hetznercloud_DestroyResources($params);
    }

    $response = hetznercloud_Request($params, 'DELETE', "servers/{$serverId}");

    if (!$response['success'] && $response['code'] !== 404) {
        return 'Error: ' . $response['message'];
    }

    hetznercloud_UntrackService($params['serviceid'] ?? 0);

    $fieldId = hetznercloud_CustomFieldId('serverID', $params['pid'] ?? 0);
    if ($fieldId) {
        try {
            Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $fieldId)
                ->where('relid', (int) $params['serviceid'])
                ->update(['value' => '']);
        } catch (\Exception $e) {
            // non fatal
        }
    }

    if ($errors) {
        hetznercloud_LogCall('TerminateAccount/Cleanup', $params['serviceid'] ?? 0, $errors);
    }

    return 'success';
}

/**
 * Upgrade / downgrade the plan.
 */
function hetznercloud_ChangePackage($params)
{
    $options = hetznercloud_Options($params);
    $server = hetznercloud_Server($params, true);

    if (!$server) {
        return 'Error: Server ID is missing.';
    }

    $current = $server['server_type']['name'] ?? '';

    if ($current === $options['type']) {
        // Plan unchanged - reconcile the add-ons instead.
        hetznercloud_ReconcileAddons($params, $options);
        return 'success';
    }

    $currentDisk = (int) ($server['server_type']['disk'] ?? 0);
    $targetDisk = 0;

    foreach (hetznercloud_ServerTypes($params) as $type) {
        if ($type['name'] === $options['type']) {
            $targetDisk = (int) $type['disk'];
        }
    }

    $result = hetznercloud_ChangeType($params, $options['type'], $targetDisk > $currentDisk);

    if (!$result['success']) {
        return 'Error: ' . $result['message'];
    }

    hetznercloud_ReconcileAddons($params, $options);

    return 'success';
}

/**
 * Bring backups and floating IP count in line with the (new) product options.
 */
function hetznercloud_ReconcileAddons($params, $options)
{
    $server = hetznercloud_Server($params, true);

    if (!$server) {
        return;
    }

    $backupsOn = !empty($server['backup_window']);

    if ($options['backups'] && !$backupsOn) {
        hetznercloud_Backups($params, true);
    } elseif (!$options['backups'] && $backupsOn) {
        hetznercloud_Backups($params, false);
    }

    $current = hetznercloud_ListFloatingIps($params);
    $have = $current['success'] ? count($current['data']) : 0;
    $want = max(0, (int) $options['floating_ips']);

    if ($want > $have) {
        $protocol = $options['floating_protocol'] === 'ipv6' ? 'ipv6' : 'ipv4';
        for ($i = $have; $i < $want; $i++) {
            hetznercloud_CreateFloatingIp($params, $protocol);
        }
    }
}

/**
 * Admin "Change Password" - Hetzner only supports generating a new one.
 */
function hetznercloud_ChangePassword($params)
{
    $result = hetznercloud_ResetRootPassword($params);

    return $result['success'] ? 'success' : 'Error: ' . $result['message'];
}

/* =========================================================================
 * Admin area
 * ====================================================================== */

function hetznercloud_AdminCustomButtonArray()
{
    return [
        'Power On' => 'AdminPowerOn',
        'Power Off' => 'AdminPowerOff',
        'Shut Down' => 'AdminShutdown',
        'Reboot' => 'AdminReboot',
        'Reset (hard)' => 'AdminReset',
        'Reset Password' => 'AdminResetPassword',
        'Enable Rescue' => 'AdminEnableRescue',
        'Disable Rescue' => 'AdminDisableRescue',
        'Prepare Console' => 'AdminConsole',
        'Sync From Hetzner' => 'AdminSync',
        'Refresh API Cache' => 'AdminRefreshCache',
        'Run Product Setup' => 'AdminRunSetup',
    ];
}

function hetznercloud_AdminPowerOn($params)
{
    $r = hetznercloud_PowerAction($params, 'poweron');
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminPowerOff($params)
{
    $r = hetznercloud_PowerAction($params, 'poweroff');
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminShutdown($params)
{
    $r = hetznercloud_PowerAction($params, 'shutdown');
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminReboot($params)
{
    $r = hetznercloud_PowerAction($params, 'reboot');
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminReset($params)
{
    $r = hetznercloud_PowerAction($params, 'reset');
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminResetPassword($params)
{
    $r = hetznercloud_ResetRootPassword($params);
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminEnableRescue($params)
{
    $r = hetznercloud_Rescue($params, true);
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

function hetznercloud_AdminDisableRescue($params)
{
    $r = hetznercloud_Rescue($params, false);
    return $r['success'] ? 'success' : 'Error: ' . $r['message'];
}

/**
 * Open a console session for the admin, then use the link on the service tab.
 */
function hetznercloud_AdminConsole($params)
{
    $result = hetznercloud_Console($params);

    return $result['success'] ? 'success' : 'Error: ' . $result['message'];
}

/**
 * Pull the current IPs / hostname back into WHMCS.
 */
function hetznercloud_AdminSync($params)
{
    $server = hetznercloud_Server($params, true);

    if (!$server) {
        return 'Error: could not read the server from Hetzner.';
    }

    $ipv4 = $server['public_net']['ipv4']['ip'] ?? '';
    $ipv6 = $server['public_net']['ipv6']['ip'] ?? '';

    try {
        Capsule::table('tblhosting')->where('id', (int) $params['serviceid'])->update([
            'dedicatedip' => $ipv4,
            'assignedips' => trim($ipv4 . "\n" . $ipv6),
        ]);
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }

    hetznercloud_TrackResource($params['serviceid'], 'server', $server['id'], $server['name'] ?? '');

    foreach (['hetznercloud_ListFloatingIps', 'hetznercloud_ListFirewalls', 'hetznercloud_ListNetworks',
        'hetznercloud_ListVolumes', 'hetznercloud_ListSshKeys'] as $callback) {
        $callback($params);
    }

    return 'success';
}

function hetznercloud_AdminRefreshCache($params)
{
    hetznercloud_ClearCache();
    hetznercloud_ServerTypes($params);
    hetznercloud_Locations($params);
    hetznercloud_Images($params);
    hetznercloud_Isos($params);

    return 'success';
}

function hetznercloud_AdminRunSetup($params)
{
    $result = hetznercloud_SetupProduct($params['pid'] ?? 0, $params);

    return $result['success'] ? 'success' : 'Error: ' . $result['message'];
}

/**
 * The full control panel on the admin service page - the same UI the customer
 * sees, with every feature switch overridden on for staff.
 */
function hetznercloud_AdminServicesTabFields($params)
{
    require_once __DIR__ . '/admin.php';

    try {
        return ['Hetzner Cloud' => hetznercloud_AdminPanel($params)];
    } catch (\Exception $e) {
        hetznercloud_LogCall('AdminServicesTabFields', $params['serviceid'] ?? 0, $e->getMessage());

        return [
            'Hetzner Cloud' => '<div class="alert alert-danger" style="margin:0">The control panel could not be '
                . 'rendered: ' . htmlspecialchars($e->getMessage()) . '</div>',
        ];
    }
}

/* =========================================================================
 * Client area
 * ====================================================================== */

/**
 * The panel replaces the stock buttons, so keep this list empty.
 */
function hetznercloud_ClientAreaCustomButtonArray()
{
    return [];
}

function hetznercloud_ClientArea($params)
{
    require_once __DIR__ . '/clientarea.php';

    return hetznercloud_ClientAreaOutput($params);
}

/**
 * "Login to panel" button next to the service - opens the console.
 */
function hetznercloud_ServiceSingleSignOn($params)
{
    $options = hetznercloud_Options($params);

    if (!$options['allow_console']) {
        return ['success' => false, 'errorMsg' => hetznercloud_L('feature_disabled')];
    }

    $console = hetznercloud_Console($params);

    if (!$console['success']) {
        return ['success' => false, 'errorMsg' => $console['message']];
    }

    return ['success' => true, 'redirectTo' => 'modules/servers/hetznercloud/' . $console['data']['path']];
}
