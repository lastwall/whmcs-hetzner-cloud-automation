<?php
/**
 * Client area controller.
 *
 * Renders the panel and answers its JSON calls. WHMCS has already checked that
 * the logged-in client owns $params['serviceid'] before this file runs, so the
 * remaining job here is CSRF protection, feature gating and input validation.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/functions.php';

/**
 * Emit JSON and stop, discarding whatever WHMCS had buffered.
 */
function hetznercloud_Json($payload, $status = 200)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

/**
 * Actions that may be called with GET (they only read).
 */
function hetznercloud_ReadOnlyActions()
{
    return [
        'summary', 'status', 'metrics', 'history', 'os_images', 'isos',
        'snapshots', 'backups', 'floating_ips', 'firewalls', 'networks', 'ssh_keys',
        'volumes', 'addons',
    ];
}

/**
 * Feature flag required by an action, if any.
 */
function hetznercloud_ActionFeature($action)
{
    $map = [
        'metrics' => 'allow_graphs',
        'history' => 'allow_history',

        'power' => 'allow_power',
        'reset_password' => 'allow_reset_password',
        'console' => 'allow_console',

        'rescue' => 'allow_rescue',
        'rebuild' => 'allow_rebuild',
        'os_images' => 'allow_rebuild',

        'isos' => 'allow_iso',
        'iso_attach' => 'allow_iso',
        'iso_detach' => 'allow_iso',

        'backups' => 'allow_backups',
        'backup_toggle' => 'allow_backups_manage',

        'snapshots' => 'allow_snapshots',
        'snapshot_create' => 'allow_snapshots',
        'snapshot_delete' => 'allow_snapshots',
        'image_restore' => 'allow_backups',

        'floating_ips' => 'allow_floating_ips',
        'fip_delete' => 'allow_floating_ips',
        'fip_assign' => 'allow_floating_ips',
        'fip_rdns' => 'allow_rdns',
        'fip_order' => 'allow_floating_ips_order',

        'rdns' => 'allow_rdns',

        'networks' => 'allow_networks',
        'net_create' => 'allow_networks',
        'net_delete' => 'allow_networks',
        'net_subnet' => 'allow_networks',
        'net_route' => 'allow_networks',
        'net_attach' => 'allow_networks',

        'firewalls' => 'allow_firewall',
        'fw_create' => 'allow_firewall',
        'fw_delete' => 'allow_firewall',
        'fw_rules' => 'allow_firewall',
        'fw_apply' => 'allow_firewall',

        'volumes' => 'allow_volumes',

        'ssh_keys' => 'allow_ssh',
        'ssh_add' => 'allow_ssh',
        'ssh_delete' => 'allow_ssh',

        'addons' => 'allow_addons',
    ];

    return $map[$action] ?? null;
}

/**
 * Client area entry point.
 */
function hetznercloud_ClientAreaOutput($params)
{
    hetznercloud_InstallTables();

    // Read the two real superglobals: $_REQUEST also carries cookies on hosts
    // that leave request_order unset, which could hijack a normal page load.
    $action = (string) ($_POST['hcaction'] ?? $_GET['hcaction'] ?? '');

    if ($action !== '') {
        hetznercloud_HandleAjax($params, $action);
    }

    try {
        return hetznercloud_RenderPanel($params);
    } catch (\Throwable $e) {
        // A blank product page is the worst possible failure: WHMCS renders its
        // shell and the customer sees nothing at all. Always return something.
        hetznercloud_LogCall('ClientArea/Render', $params['serviceid'] ?? 0, $e->getMessage());

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'hcPanel' => '<div style="padding:14px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;'
                    . 'border-radius:10px;font:14px system-ui,sans-serif">'
                    . 'The Hetzner Cloud panel could not be loaded. Please try again in a moment or contact support.'
                    . '</div>',
            ],
        ];
    }
}

/* =========================================================================
 * JSON endpoint
 * ====================================================================== */

function hetznercloud_HandleAjax($params, $action)
{
    $options = hetznercloud_Options($params);
    $readOnly = in_array($action, hetznercloud_ReadOnlyActions(), true);

    if (!$readOnly) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            hetznercloud_Json(['success' => false, 'message' => hetznercloud_L('request_failed')], 405);
        }

        if (!hetznercloud_CheckCsrf($_POST['token'] ?? '')) {
            hetznercloud_Json(['success' => false, 'message' => hetznercloud_L('session_expired')], 403);
        }
    }

    $feature = hetznercloud_ActionFeature($action);

    if ($feature && empty($options[$feature])) {
        hetznercloud_Json(['success' => false, 'message' => hetznercloud_L('feature_disabled')], 403);
    }

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

/**
 * Route one action. Returns null for unknown actions.
 */
function hetznercloud_Dispatch($params, $action, $options)
{
    $post = $_POST;
    $get = $_GET;

    switch ($action) {

        /* ---- read ---- */

        case 'summary':
            return hetznercloud_Ok('', hetznercloud_Summary($params, $options));

        case 'status':
            $server = hetznercloud_Server($params, true);
            return hetznercloud_Ok('', hetznercloud_StatusInfo($server['status'] ?? 'unknown') + [
                'locked' => !empty($server['locked']),
                'iso' => $server['iso']['description'] ?? '',
                'rescue' => !empty($server['rescue_enabled']),
            ]);

        case 'metrics':
            return hetznercloud_Metrics($params, $get['minutes'] ?? 1440);

        case 'history':
            return hetznercloud_History($params);

        case 'os_images':
            return hetznercloud_Ok('', hetznercloud_Images($params));

        case 'isos':
            return hetznercloud_Ok('', hetznercloud_Isos($params));

        case 'snapshots':
            return hetznercloud_ListImages($params, 'snapshot');

        case 'backups':
            return hetznercloud_ListImages($params, 'backup');

        case 'floating_ips':
            return hetznercloud_ListFloatingIps($params);

        case 'firewalls':
            return hetznercloud_ListFirewalls($params);

        case 'networks':
            return hetznercloud_ListNetworks($params);

        case 'ssh_keys':
            return hetznercloud_ListSshKeys($params);

        case 'volumes':
            return hetznercloud_ListVolumes($params);

        case 'addons':
            return hetznercloud_Addons($params);

        /* ---- power & server ---- */

        case 'power':
            $map = [
                'on' => 'poweron',
                'off' => 'poweroff',
                'shutdown' => 'shutdown',
                'reboot' => 'reboot',
                'reset' => 'reset',
            ];
            $wanted = $map[$post['op'] ?? ''] ?? null;
            if (!$wanted) {
                return hetznercloud_Fail('Unsupported power action.');
            }
            return hetznercloud_PowerAction($params, $wanted);

        case 'reset_password':
            return hetznercloud_ResetRootPassword($params);

        case 'rebuild':
            return hetznercloud_Rebuild($params, trim((string) ($post['image'] ?? '')));

        case 'rescue':
            return hetznercloud_Rescue($params, ($post['enable'] ?? '') === '1', $post['type'] ?? 'linux64');

        case 'console':
            return hetznercloud_Console($params);

        case 'rdns':
            return hetznercloud_SetServerRdns($params, (string) ($post['ip'] ?? ''), trim((string) ($post['hostname'] ?? '')));

        /* ---- ISO ---- */

        case 'iso_attach':
            return hetznercloud_AttachIso($params, trim((string) ($post['iso'] ?? '')));

        case 'iso_detach':
            return hetznercloud_DetachIso($params);

        /* ---- backups & snapshots ---- */

        case 'backup_toggle':
            return hetznercloud_Backups($params, ($post['enable'] ?? '') === '1');

        case 'snapshot_create':
            return hetznercloud_CreateSnapshot($params, (string) ($post['name'] ?? ''));

        case 'snapshot_delete':
            return hetznercloud_DeleteImage($params, (int) ($post['id'] ?? 0));

        case 'image_restore':
            return hetznercloud_RestoreImage($params, (int) ($post['id'] ?? 0));

        /* ---- floating IPs ---- */

        case 'fip_order':
            return hetznercloud_OrderFloatingIp($params, $post['protocol'] ?? 'ipv4');

        case 'fip_delete':
            return hetznercloud_DeleteFloatingIp($params, (int) ($post['id'] ?? 0));

        case 'fip_assign':
            return hetznercloud_AssignFloatingIp($params, (int) ($post['id'] ?? 0), ($post['assign'] ?? '1') === '1');

        case 'fip_rdns':
            return hetznercloud_FloatingIpRdns(
                $params,
                (int) ($post['id'] ?? 0),
                (string) ($post['ip'] ?? ''),
                trim((string) ($post['hostname'] ?? ''))
            );

        /* ---- firewalls ---- */

        case 'fw_create':
            return hetznercloud_CreateFirewall($params, (string) ($post['name'] ?? ''));

        case 'fw_delete':
            return hetznercloud_DeleteFirewall($params, (int) ($post['id'] ?? 0));

        case 'fw_rules':
            $rules = json_decode((string) ($post['rules'] ?? '[]'), true);
            if (!is_array($rules)) {
                return hetznercloud_Fail('Invalid rule set.');
            }
            return hetznercloud_SetFirewallRules($params, (int) ($post['id'] ?? 0), $rules);

        case 'fw_apply':
            return hetznercloud_ApplyFirewall($params, (int) ($post['id'] ?? 0), ($post['apply'] ?? '1') === '1');

        /* ---- networks ---- */

        case 'net_create':
            return hetznercloud_CreateNetwork($params, (string) ($post['name'] ?? ''), (string) ($post['ip_range'] ?? '10.0.0.0/16'));

        case 'net_delete':
            return hetznercloud_DeleteNetwork($params, (int) ($post['id'] ?? 0));

        case 'net_subnet':
            return hetznercloud_NetworkSubnet($params, (int) ($post['id'] ?? 0), ($post['add'] ?? '1') === '1', [
                'ip_range' => (string) ($post['ip_range'] ?? ''),
                'network_zone' => (string) ($post['network_zone'] ?? ''),
            ]);

        case 'net_route':
            return hetznercloud_NetworkRoute(
                $params,
                (int) ($post['id'] ?? 0),
                ($post['add'] ?? '1') === '1',
                (string) ($post['destination'] ?? ''),
                (string) ($post['gateway'] ?? '')
            );

        case 'net_attach':
            return hetznercloud_NetworkAttach($params, (int) ($post['id'] ?? 0), ($post['attach'] ?? '1') === '1');

        /* ---- SSH keys ---- */

        case 'ssh_add':
            return hetznercloud_CreateSshKey($params, (string) ($post['name'] ?? 'key'), (string) ($post['public_key'] ?? ''));

        case 'ssh_delete':
            return hetznercloud_DeleteSshKey($params, (int) ($post['id'] ?? 0));
    }

    return null;
}

/* =========================================================================
 * Panel data
 * ====================================================================== */

/**
 * Everything the overview tab shows.
 */
function hetznercloud_Summary($params, $options = null)
{
    $options = $options ?: hetznercloud_Options($params);

    try {
        $server = hetznercloud_Server($params, true);
    } catch (\Throwable $e) {
        hetznercloud_LogCall('Summary', $params['serviceid'] ?? 0, $e->getMessage());
        $server = null;
    }

    if (!$server) {
        return [
            'available' => false,
            'status' => hetznercloud_StatusInfo('unknown'),
            'name' => '',
            'type' => '',
            'location' => '',
            'ipv4' => '',
            'ipv6' => '',
            'iso' => '',
            'rescue' => false,
            'bandwidth' => null,
        ];
    }

    $type = $server['server_type'] ?? [];
    $ipv4 = $server['public_net']['ipv4']['ip'] ?? '';
    $ipv6Net = $server['public_net']['ipv6']['ip'] ?? '';
    $ipv6 = $ipv6Net;

    // Hetzner assigns a /64; the first usable address is ::1.
    if ($ipv6Net && strpos($ipv6Net, '/') !== false) {
        $ipv6 = rtrim(explode('/', $ipv6Net)[0], ':') . '::1';
    }

    $privateIps = [];
    foreach ((array) ($server['private_net'] ?? []) as $private) {
        $privateIps[] = $private['ip'] ?? '';
    }

    $bandwidth = hetznercloud_Bandwidth($params);

    return [
        'available' => true,
        'id' => $server['id'],
        'name' => $server['name'] ?? '',
        'status' => hetznercloud_StatusInfo($server['status'] ?? 'unknown'),
        'locked' => !empty($server['locked']),
        'ipv4' => $ipv4,
        'ipv6' => $ipv6,
        'ipv6_network' => $ipv6Net,
        'ptr' => $server['public_net']['ipv4']['dns_ptr'] ?? '',
        'ptr6' => $server['public_net']['ipv6']['dns_ptr'][0]['dns_ptr'] ?? '',
        'private_ips' => $privateIps,
        'image' => $server['image']['description'] ?? hetznercloud_L('status_unknown'),
        'type' => strtoupper($type['name'] ?? ''),
        'cores' => $type['cores'] ?? 0,
        'memory' => $type['memory'] ?? 0,
        'disk' => $type['disk'] ?? 0,
        'cpu_type' => $type['cpu_type'] ?? '',
        'architecture' => $type['architecture'] ?? 'x86',
        // Older servers can come back without the city set; fall back through
        // the other location fields so the panel never shows an empty "()".
        'location' => $server['datacenter']['location']['city']
            ?? $server['datacenter']['location']['description']
            ?? $server['datacenter']['location']['name']
            ?? '',
        'country' => $server['datacenter']['location']['country'] ?? '',
        'datacenter' => $server['datacenter']['name'] ?? '',
        'created' => $server['created'] ?? '',
        'backups' => !empty($server['backup_window']),
        'backup_window' => $server['backup_window'] ?? '',
        'rescue' => !empty($server['rescue_enabled']),
        'iso' => $server['iso']['description'] ?? '',
        'iso_name' => $server['iso']['name'] ?? '',
        'bandwidth' => $bandwidth['success'] ? $bandwidth['data'] : null,
    ];
}

/**
 * Which tabs a set of options unlocks.
 */
function hetznercloud_PanelTabs($options)
{
    return [
        'overview' => true,
        'graphs' => (bool) $options['allow_graphs'],
        'backups' => (bool) $options['allow_backups'],
        'snapshots' => $options['allow_snapshots'] && $options['snapshot_limit'] > 0,
        'network' => (bool) ($options['allow_floating_ips'] || $options['allow_rdns']
            || ($options['allow_networks'] && $options['network_limit'] > 0)
            || ($options['allow_volumes'] && $options['volume_size'] > 0)),
        'firewall' => $options['allow_firewall'] && $options['firewall_limit'] > 0,
        'iso' => (bool) $options['allow_iso'],
        'rescue' => (bool) $options['allow_rescue'],
        'rebuild' => (bool) $options['allow_rebuild'],
        'ssh' => (bool) $options['allow_ssh'],
        'addons' => (bool) $options['allow_addons'],
        'history' => (bool) $options['allow_history'],
    ];
}

/**
 * Staff view: every feature on, and a workable allowance even when the product
 * sells none. Never applied to a client request.
 */
function hetznercloud_ElevateOptions(array $options)
{
    foreach ($options as $key => $value) {
        if (strpos($key, 'allow_') === 0) {
            $options[$key] = true;
        }
    }

    $options['snapshot_limit'] = max(1, (int) $options['snapshot_limit']);
    $options['firewall_limit'] = max(1, (int) $options['firewall_limit']);
    $options['network_limit'] = max(1, (int) $options['network_limit']);
    $options['floating_ip_limit'] = max(1, (int) $options['floating_ip_limit']);
    $options['fw_in_limit'] = max(10, (int) $options['fw_in_limit']);
    $options['fw_out_limit'] = max(10, (int) $options['fw_out_limit']);

    return $options;
}

/**
 * Everything the panel needs, for either side of WHMCS.
 *
 * @param string $context "client" or "admin". Admin ignores the per-product
 *                        feature switches - staff always see every tool.
 */
function hetznercloud_PanelConfig($params, $context = 'client')
{
    $options = hetznercloud_Options($params);

    if ($context === 'admin') {
        $options = hetznercloud_ElevateOptions($options);
    }

    $accent = (string) hetznercloud_Setting('accent', '#d50c2d');
    if (!preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
        $accent = '#d50c2d';
    }

    $serviceId = (int) $params['serviceid'];
    $isAdmin = $context === 'admin';

    return [
        'context' => $context,
        'serviceid' => $serviceId,
        'serverid' => hetznercloud_ServerId($params),
        'token' => hetznercloud_CsrfToken(),
        'endpoint' => $isAdmin
            ? '../modules/servers/hetznercloud/admin.php?id=' . $serviceId
            : 'clientarea.php?action=productdetails&id=' . $serviceId,
        'upgradeUrl' => $isAdmin ? '' : 'upgrade.php?type=package&id=' . $serviceId,
        'addonsUrl' => $isAdmin ? '' : 'upgrade.php?type=configoptions&id=' . $serviceId,
        'moduleUrl' => $isAdmin ? '../modules/servers/hetznercloud/' : 'modules/servers/hetznercloud/',
        'version' => HETZNERCLOUD_VERSION,
        'accent' => $accent,
        'poll' => max(0, (int) hetznercloud_Setting('poll_interval', 15)),
        'rtl' => !$isAdmin && hetznercloud_IsRtl($params),
        'lang' => hetznercloud_LangArray($params),
        'options' => $options,
        'tabs' => hetznercloud_PanelTabs($options),
        'summary' => hetznercloud_Summary($params, $options),
        'username' => ($params['username'] ?? '') ?: 'root',
        'password' => (string) ($params['password'] ?? ''),
        'currency' => $params['clientsdetails']['currency_code'] ?? '',
        'floatingPrice' => $options['floating_price'],
        'networkZone' => hetznercloud_NetworkZoneForLocation($params, $options['location']),
    ];
}

/**
 * Client area output: one Smarty variable holding the rendered panel.
 */
function hetznercloud_RenderPanel($params)
{
    require_once __DIR__ . '/panel.php';

    $html = hetznercloud_PanelHtml(hetznercloud_PanelConfig($params, 'client'));

    // The panel is always several kilobytes. Anything shorter means it was
    // built empty, which would reach the customer as a blank product page with
    // nothing in any log to explain it.
    if (strlen($html) < 500) {
        hetznercloud_LogCall('ClientArea/EmptyPanel', $params['serviceid'] ?? 0, [
            'length' => strlen($html),
            'html' => substr($html, 0, 400),
        ]);
    }

    return [
        'templatefile' => 'clientarea',
        'vars' => ['hcPanel' => $html],
    ];
}
