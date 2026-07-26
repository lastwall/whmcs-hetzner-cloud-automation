<?php
/**
 * Admin side of the module.
 *
 * Two jobs:
 *  - included from hetznercloud.php, it renders the same control panel the
 *    client gets on the admin service page;
 *  - requested directly, it is the JSON endpoint that panel talks to.
 *
 * Module params are rebuilt from the database rather than borrowed from an
 * undocumented WHMCS internal, so the endpoint keeps working across releases.
 */

$hetznercloudAdminEntryPoint = !defined('WHMCS');

if ($hetznercloudAdminEntryPoint) {
    require_once __DIR__ . '/../../../init.php';
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/clientarea.php';
require_once __DIR__ . '/panel.php';

/* -------------------------------------------------------------------------
 * Module params, rebuilt from the database
 * ---------------------------------------------------------------------- */

/**
 * Assemble the $params array a provisioning module would normally receive.
 *
 * @return array|null null when the service does not exist
 */
function hetznercloud_ServiceParams($serviceId)
{
    $serviceId = (int) $serviceId;

    try {
        $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
    } catch (\Exception $e) {
        return null;
    }

    if (!$service) {
        return null;
    }

    $params = [
        'serviceid' => $serviceId,
        'pid' => (int) $service->packageid,
        'userid' => (int) $service->userid,
        'domain' => (string) $service->domain,
        'username' => (string) $service->username,
        'password' => hetznercloud_Decrypt($service->password),
        'dedicatedip' => (string) $service->dedicatedip,
        'assignedips' => (string) $service->assignedips,
        'status' => (string) $service->domainstatus,
        'producttype' => 'server',
        'moduletype' => 'hetznercloud',
        'serveraccesshash' => '',
        'configoptions' => [],
        'customfields' => [],
    ];

    // API token: the server entry this service sits on, else any enabled one.
    if (!empty($service->server)) {
        $server = Capsule::table('tblservers')->where('id', (int) $service->server)->first();

        if ($server) {
            $params['serveraccesshash'] = hetznercloud_UnlockToken($server->accesshash);
            $params['serverhostname'] = (string) $server->hostname;
            $params['servername'] = (string) $server->name;
        }
    }

    if ($params['serveraccesshash'] === '') {
        $params['serveraccesshash'] = hetznercloud_Token();
    }

    // Product settings.
    $product = Capsule::table('tblproducts')->where('id', $params['pid'])->first();

    for ($i = 1; $i <= 24; $i++) {
        $key = 'configoption' . $i;
        $params[$key] = $product && isset($product->$key) ? (string) $product->$key : '';
    }

    if ($product) {
        $params['productname'] = (string) $product->name;
    }

    // Custom fields, keyed by the name before any "|".
    $fields = Capsule::table('tblcustomfields')
        ->where('type', 'product')
        ->where('relid', $params['pid'])
        ->get(['id', 'fieldname']);

    foreach ($fields as $field) {
        $value = Capsule::table('tblcustomfieldsvalues')
            ->where('fieldid', $field->id)
            ->where('relid', $serviceId)
            ->value('value');

        $params['customfields'][trim(explode('|', $field->fieldname)[0])] = (string) $value;
    }

    // Configurable options, mirroring what WHMCS hands a module.
    $chosen = Capsule::table('tblhostingconfigoptions as h')
        ->join('tblproductconfigoptions as o', 'o.id', '=', 'h.configid')
        ->leftJoin('tblproductconfigoptionssub as s', 's.id', '=', 'h.optionid')
        ->where('h.relid', $serviceId)
        ->get(['o.optionname', 'o.optiontype', 's.optionname as suboption', 'h.qty']);

    foreach ($chosen as $row) {
        $type = (int) $row->optiontype;

        if ($type === 4) {
            $value = (string) $row->qty;
        } elseif ($type === 3) {
            $value = $row->qty ? 'on' : '';
        } else {
            $value = trim(explode('|', (string) $row->suboption)[0]);
        }

        $params['configoptions'][$row->optionname] = $value;
    }

    // Client details, for currency and language.
    $client = Capsule::table('tblclients')->where('id', $params['userid'])->first();

    if ($client) {
        $currencyCode = Capsule::table('tblcurrencies')->where('id', (int) $client->currency)->value('code');

        $params['clientsdetails'] = [
            'userid' => (int) $client->id,
            'id' => (int) $client->id,
            'firstname' => (string) $client->firstname,
            'lastname' => (string) $client->lastname,
            'email' => (string) $client->email,
            'currency' => (int) $client->currency,
            'currency_code' => (string) $currencyCode,
            'language' => (string) $client->language,
        ];
    }

    return $params;
}

/* -------------------------------------------------------------------------
 * Admin panel
 * ---------------------------------------------------------------------- */

/**
 * The control panel plus a strip of WHMCS-side facts staff care about.
 */
function hetznercloud_AdminPanel($params)
{
    $config = hetznercloud_PanelConfig($params, 'admin');
    $summary = $config['summary'];

    $facts = [
        'Service' => '#' . (int) $params['serviceid'],
        'Hetzner server' => $config['serverid'] ? '#' . (int) $config['serverid'] : '<span class="hc-chip">none</span>',
        'Product' => hetznercloud_E($params['productname'] ?? ''),
        'Server entry' => hetznercloud_E($params['servername'] ?? 'auto'),
        'WHMCS status' => hetznercloud_E($params['status'] ?? ''),
        'Created' => $summary['available'] ? hetznercloud_E($summary['created']) : '-',
    ];

    $strip = '<div class="hc-adminbar">';
    foreach ($facts as $label => $value) {
        $strip .= '<div><b>' . hetznercloud_E($label) . '</b><span>' . $value . '</span></div>';
    }
    $strip .= '</div>';

    if (!$config['serverid']) {
        $strip .= '<div class="hc-note hc-note--warn">No Hetzner server is attached to this service yet. '
            . 'Use <strong>Create</strong> above to provision one, or paste an existing server ID into the '
            . '<code>serverID</code> custom field and press <strong>Sync From Hetzner</strong>.</div>';
    }

    return '<div class="hc-adminwrap">' . $strip . hetznercloud_PanelHtml($config) . '</div>';
}

/* -------------------------------------------------------------------------
 * JSON endpoint
 * ---------------------------------------------------------------------- */

/**
 * Answer a panel request from the admin service page.
 */
function hetznercloud_AdminEndpoint()
{
    if (!hetznercloud_CurrentAdminId()) {
        hetznercloud_Json(['success' => false, 'message' => 'Administrator login required.'], 403);
    }

    $serviceId = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    $action = (string) ($_POST['hcaction'] ?? $_GET['hcaction'] ?? '');

    if (!$serviceId || $action === '') {
        hetznercloud_Json(['success' => false, 'message' => 'Missing service id or action.'], 400);
    }

    if (!in_array($action, hetznercloud_ReadOnlyActions(), true)) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            hetznercloud_Json(['success' => false, 'message' => 'This action requires POST.'], 405);
        }

        if (!hetznercloud_CheckCsrf($_POST['token'] ?? '')) {
            hetznercloud_Json(['success' => false, 'message' => hetznercloud_L('session_expired')], 403);
        }
    }

    $params = hetznercloud_ServiceParams($serviceId);

    if (!$params) {
        hetznercloud_Json(['success' => false, 'message' => 'Unknown service.'], 404);
    }

    // Staff are not subject to the per-product feature switches.
    $options = hetznercloud_ElevateOptions(hetznercloud_Options($params));

    $result = hetznercloud_Dispatch($params, $action, $options);

    if ($result === null) {
        hetznercloud_Json(['success' => false, 'message' => 'Unknown action.'], 404);
    }

    hetznercloud_Json([
        'success' => (bool) $result['success'],
        'message' => (string) $result['message'],
        'data' => $result['data'] ?? null,
    ]);
}

if ($hetznercloudAdminEntryPoint) {
    hetznercloud_AdminEndpoint();
}
