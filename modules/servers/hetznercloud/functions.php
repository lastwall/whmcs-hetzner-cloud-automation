<?php
/**
 * Hetzner Cloud feature layer.
 *
 * Every function here returns either a normalised result array
 * ['success' => bool, 'message' => string, 'data' => mixed] or plain data.
 * WHMCS entry points live in hetznercloud.php.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/lang.php';

/* =========================================================================
 * Helpers
 * ====================================================================== */

/**
 * Normalised OK/error result.
 */
function hetznercloud_Ok($message = '', $data = null)
{
    return ['success' => true, 'message' => $message, 'data' => $data];
}

function hetznercloud_Fail($message, $data = null)
{
    return ['success' => false, 'message' => $message, 'data' => $data];
}

/**
 * The Hetzner server id belonging to a WHMCS service.
 *
 * Reads the serverID custom field first (backwards compatible with 1.x/2.x
 * installs) and falls back to the resource cache.
 */
function hetznercloud_ServerId($params)
{
    $id = $params['customfields']['serverID'] ?? null;

    if (!$id) {
        foreach (['serverid', 'ServerID', 'Server ID', 'server_id'] as $alias) {
            if (!empty($params['customfields'][$alias])) {
                $id = $params['customfields'][$alias];
                break;
            }
        }
    }

    if (!$id && !empty($params['serviceid'])) {
        $tracked = hetznercloud_TrackedResources($params['serviceid'], 'server');
        $id = $tracked[0]['resource_id'] ?? null;
    }

    return is_numeric($id) && $id > 0 ? (int) $id : null;
}

/**
 * Persist the Hetzner server id on the service.
 */
function hetznercloud_SetServerId($params, $serverId, $name = '')
{
    $fieldId = hetznercloud_CustomFieldId('serverID', $params['pid'] ?? 0);

    if ($fieldId) {
        try {
            Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                ['fieldid' => $fieldId, 'relid' => (int) $params['serviceid']],
                ['value' => (string) $serverId]
            );
        } catch (\Exception $e) {
            hetznercloud_LogCall('SetServerId', $serverId, $e->getMessage());
        }
    }

    hetznercloud_TrackResource($params['serviceid'] ?? 0, 'server', $serverId, $name);
}

/**
 * Look up a product custom field id by name (matching before the "|" separator).
 */
function hetznercloud_CustomFieldId($fieldName, $productId)
{
    if (!$productId) {
        return null;
    }

    try {
        $fields = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', (int) $productId)
            ->get(['id', 'fieldname']);

        foreach ($fields as $field) {
            $name = trim(explode('|', $field->fieldname)[0]);
            if (strcasecmp($name, $fieldName) === 0) {
                return (int) $field->id;
            }
        }
    } catch (\Exception $e) {
        // fall through
    }

    return null;
}

/**
 * Resolve a configurable option, falling back to the product setting.
 */
function hetznercloud_Option($params, $configurableName, $productKey, $default = '')
{
    $options = $params['configoptions'] ?? [];

    if (isset($options[$configurableName]) && $options[$configurableName] !== '') {
        return $options[$configurableName];
    }

    // WHMCS strips the "value|Label" display half already; be tolerant anyway.
    foreach ($options as $key => $value) {
        if (strcasecmp(trim($key), $configurableName) === 0 && $value !== '') {
            return trim(explode('|', $value)[0]);
        }
    }

    $value = $params[$productKey] ?? $default;

    return is_string($value) ? trim(explode('|', $value)[0]) : $value;
}

/**
 * All product/configurable settings for a service in one array.
 */
function hetznercloud_Options($params)
{
    $yes = function ($value) {
        return in_array(strtolower((string) $value), ['on', 'yes', '1', 'true', 'enable', 'enabled'], true);
    };

    $overrides = hetznercloud_ProductOverrides($params['pid'] ?? 0);

    /**
     * Limit precedence: the per-product override wins, then the configurable
     * option / product setting, then the shipped default.
     */
    $limit = function ($key, $configurableName) use ($params, $overrides) {
        $definitions = hetznercloud_LimitDefinitions();
        $definition = $definitions[$key];

        if ($overrides['limits'][$key] !== null) {
            return (int) $overrides['limits'][$key];
        }

        $inherited = hetznercloud_Option($params, $configurableName, 'configoption' . $definition['configoption'], '');

        if ($inherited !== '' && $inherited !== null) {
            return max(0, (int) $inherited);
        }

        return (int) ($definition['default'] ?? 0);
    };

    $provisionedIps = (int) hetznercloud_Option($params, 'Floating IPs', 'configoption9', 0);

    $options = [
        'prefix' => trim((string) ($params['configoption1'] ?? 'vps')),
        'type' => hetznercloud_Option($params, 'Server Type', 'configoption2', 'cx22'),
        'location' => hetznercloud_Option($params, 'Location', 'configoption3', 'fsn1'),
        'image' => hetznercloud_Option($params, 'OS Image', 'configoption4', 'ubuntu-24.04'),
        'backups' => $yes(hetznercloud_Option($params, 'Enable Backup', 'configoption5', '')),
        'automount' => $yes($params['configoption6'] ?? ''),
        'volume_size' => (int) hetznercloud_Option($params, 'Volume Size', 'configoption7', 0),
        'volume_format' => hetznercloud_Option($params, 'Disk Format', 'configoption8', 'ext4'),
        'floating_ips' => $provisionedIps,
        'floating_protocol' => strtolower(hetznercloud_Option($params, 'IP Protocol', 'configoption10', 'ipv4')),
        'floating_price' => (float) ($params['configoption11'] ?? 0),
        'snapshot_limit' => $limit('snapshot_limit', 'Snapshot Limit'),
        'firewall_limit' => $limit('firewall_limit', 'Firewall Limit'),
        'fw_in_limit' => $limit('fw_in_limit', 'Inbound Firewall Limit'),
        'fw_out_limit' => $limit('fw_out_limit', 'Outbound Firewall Limit'),
        'network_limit' => $limit('network_limit', 'Network Limit'),
        'floating_ip_limit' => $overrides['limits']['floating_ip_limit'] !== null
            ? (int) $overrides['limits']['floating_ip_limit']
            : $provisionedIps,
    ];

    // Feature switches are edited in Addons > Hetzner Cloud, not in the 24
    // configoption slots, so there is room for one per feature.
    foreach ($overrides['features'] as $key => $enabled) {
        $options['allow_' . $key] = (bool) $enabled;
    }

    return $options;
}

/**
 * Build a valid, unique Hetzner server name.
 */
function hetznercloud_BuildServerName($params, $options)
{
    $name = trim((string) ($params['domain'] ?? ''));

    if ($name === '') {
        $prefix = $options['prefix'] !== '' ? $options['prefix'] : 'vps';
        $name = $prefix . '-' . ($params['serviceid'] ?? 0);
    }

    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9\.\-]/', '-', $name);
    $name = trim(preg_replace('/-+/', '-', $name), '-.');

    if ($name === '' || !preg_match('/^[a-z0-9]/', $name)) {
        $name = 'vps-' . ($params['serviceid'] ?? 0);
    }

    return substr($name, 0, 63);
}

/**
 * Fetch the server object (memoised per request).
 */
function hetznercloud_Server($params, $refresh = false)
{
    static $cache = [];

    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return null;
    }

    if (!$refresh && isset($cache[$serverId])) {
        return $cache[$serverId];
    }

    $response = hetznercloud_Request($params, 'GET', "servers/{$serverId}");

    if (!$response['success'] || empty($response['data']['server'])) {
        $cache[$serverId] = null;
        return null;
    }

    $cache[$serverId] = $response['data']['server'];

    return $cache[$serverId];
}

/**
 * Map a Hetzner status onto a label + colour token used by the UI.
 */
function hetznercloud_StatusInfo($status)
{
    $map = [
        'running' => ['ok', 'status_running'],
        'off' => ['off', 'status_off'],
        'starting' => ['busy', 'status_starting'],
        'stopping' => ['busy', 'status_stopping'],
        'initializing' => ['busy', 'status_initializing'],
        'migrating' => ['busy', 'status_migrating'],
        'rebuilding' => ['busy', 'status_rebuilding'],
        'deleting' => ['bad', 'status_deleting'],
        'unknown' => ['muted', 'status_unknown'],
    ];

    [$tone, $key] = $map[$status] ?? ['muted', 'status_unknown'];

    return [
        'status' => $status,
        'tone' => $tone,
        'label' => hetznercloud_L($key),
    ];
}

/**
 * Human readable byte size.
 */
function hetznercloud_FormatBytes($bytes, $decimals = 2)
{
    $bytes = (float) $bytes;

    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

    return round($bytes / pow(1024, $power), $decimals) . ' ' . $units[$power];
}

/* =========================================================================
 * Provisioning
 * ====================================================================== */

/**
 * Create the server plus every extra the product asks for.
 */
function hetznercloud_Provision($params)
{
    hetznercloud_InstallTables();

    // Without the serverID custom field the id has nowhere to live, so create
    // the required fields up front unless an admin turned that off.
    if (hetznercloud_Setting('auto_setup', 'on') === 'on' && !empty($params['pid'])) {
        require_once __DIR__ . '/setup.php';
        hetznercloud_EnsureCustomFields($params['pid']);
    }

    $options = hetznercloud_Options($params);
    $labels = hetznercloud_Labels($params);
    $name = hetznercloud_BuildServerName($params, $options);

    $body = [
        'name' => $name,
        'server_type' => $options['type'],
        'image' => $options['image'],
        'location' => $options['location'],
        'start_after_create' => true,
        'labels' => $labels,
    ];

    // Optional SSH key supplied at order time.
    $sshKeyId = hetznercloud_ImportOrderSshKey($params);
    if ($sshKeyId) {
        $body['ssh_keys'] = [$sshKeyId];
    }

    $response = hetznercloud_Request($params, 'POST', 'servers', $body);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $server = $response['data']['server'] ?? [];
    $serverId = $server['id'] ?? null;

    if (!$serverId) {
        return hetznercloud_Fail('Hetzner did not return a server id.');
    }

    hetznercloud_SetServerId($params, $serverId, $name);

    $rootPassword = $response['data']['root_password'] ?? '';
    $ipv4 = $server['public_net']['ipv4']['ip'] ?? '';
    $ipv6 = $server['public_net']['ipv6']['ip'] ?? '';

    $update = ['dedicatedip' => $ipv4, 'username' => 'root'];
    if ($rootPassword) {
        $encrypted = hetznercloud_Encrypt($rootPassword);
        if ($encrypted !== null) {
            $update['password'] = $encrypted;
        }
    }
    if ($ipv6) {
        $update['assignedips'] = trim($ipv4 . "\n" . $ipv6);
    }

    try {
        Capsule::table('tblhosting')->where('id', (int) $params['serviceid'])->update($update);
    } catch (\Exception $e) {
        hetznercloud_LogCall('Provision/SaveService', $params['serviceid'], $e->getMessage());
    }

    // Wait for the create action so follow-up calls are not rejected as "locked".
    if (!empty($response['data']['action']['id'])) {
        hetznercloud_WaitAction($params, $response['data']['action']['id'], 120);
    }

    $warnings = [];

    if ($options['backups']) {
        $backup = hetznercloud_Action($params, $serverId, 'enable_backup');
        if (!$backup['success']) {
            $warnings[] = 'backups: ' . $backup['message'];
        }
    }

    if ($options['volume_size'] > 0) {
        $volume = hetznercloud_CreateVolume($params, $options['volume_size'], $options['volume_format'], $options['automount']);
        if (!$volume['success']) {
            $warnings[] = 'volume: ' . $volume['message'];
        }
    }

    if ($options['floating_ips'] > 0) {
        $protocols = $options['floating_protocol'] === 'both'
            ? ['ipv4', 'ipv6']
            : [$options['floating_protocol'] === 'ipv6' ? 'ipv6' : 'ipv4'];

        for ($i = 0; $i < $options['floating_ips']; $i++) {
            foreach ($protocols as $protocol) {
                $floating = hetznercloud_CreateFloatingIp($params, $protocol);
                if (!$floating['success']) {
                    $warnings[] = 'floating ip: ' . $floating['message'];
                }
            }
        }
    }

    if ($warnings) {
        hetznercloud_LogCall('Provision/Warnings', $params['serviceid'], $warnings);
    }

    return hetznercloud_Ok('Server created', ['server' => $server, 'warnings' => $warnings]);
}

/**
 * Create a Hetzner SSH key from the customer's order field, if present.
 */
function hetznercloud_ImportOrderSshKey($params)
{
    $publicKey = '';

    foreach (['ssh_key', 'sshkey', 'SSH Key', 'ssh key', 'SSH Public Key'] as $field) {
        if (!empty($params['customfields'][$field])) {
            $publicKey = trim($params['customfields'][$field]);
            break;
        }
    }

    if ($publicKey === '' && !empty($params['configoptions']['SSH Key'])) {
        $publicKey = trim($params['configoptions']['SSH Key']);
    }

    if ($publicKey === '') {
        return null;
    }

    $result = hetznercloud_CreateSshKey($params, 'whmcs-' . ($params['serviceid'] ?? 0), $publicKey);

    return $result['success'] ? ($result['data']['id'] ?? null) : null;
}

/**
 * Delete everything this service owns at Hetzner.
 */
function hetznercloud_DestroyResources($params)
{
    $serviceId = (int) ($params['serviceid'] ?? 0);
    $selector = hetznercloud_LabelSelector($serviceId);
    $serverId = hetznercloud_ServerId($params);
    $errors = [];

    // Floating IPs
    foreach (hetznercloud_Collection($params, 'floating_ips', 'floating_ips', ['label_selector' => $selector])['items'] as $ip) {
        hetznercloud_Request($params, 'POST', "floating_ips/{$ip['id']}/actions/unassign");
        $delete = hetznercloud_Request($params, 'DELETE', "floating_ips/{$ip['id']}");
        if (!$delete['success']) {
            $errors[] = 'floating ip ' . $ip['id'] . ': ' . $delete['message'];
        }
    }

    // Volumes
    foreach (hetznercloud_Collection($params, 'volumes', 'volumes', ['label_selector' => $selector])['items'] as $volume) {
        $detach = hetznercloud_Request($params, 'POST', "volumes/{$volume['id']}/actions/detach");
        if (!empty($detach['data']['action']['id'])) {
            hetznercloud_WaitAction($params, $detach['data']['action']['id'], 60);
        }
        $delete = hetznercloud_Request($params, 'DELETE', "volumes/{$volume['id']}");
        if (!$delete['success']) {
            $errors[] = 'volume ' . $volume['id'] . ': ' . $delete['message'];
        }
    }

    // Firewalls
    foreach (hetznercloud_Collection($params, 'firewalls', 'firewalls', ['label_selector' => $selector])['items'] as $firewall) {
        if ($serverId) {
            hetznercloud_Request($params, 'POST', "firewalls/{$firewall['id']}/actions/remove_from_resources", [
                'remove_from' => [['type' => 'server', 'server' => ['id' => (int) $serverId]]],
            ]);
        }
        hetznercloud_Request($params, 'DELETE', "firewalls/{$firewall['id']}");
    }

    // Private networks
    foreach (hetznercloud_Collection($params, 'networks', 'networks', ['label_selector' => $selector])['items'] as $network) {
        hetznercloud_Request($params, 'DELETE', "networks/{$network['id']}");
    }

    // Snapshots / custom images
    foreach (hetznercloud_Collection($params, 'images', 'images', ['label_selector' => $selector, 'type' => 'snapshot'])['items'] as $image) {
        hetznercloud_Request($params, 'DELETE', "images/{$image['id']}");
    }

    // SSH keys
    foreach (hetznercloud_Collection($params, 'ssh_keys', 'ssh_keys', ['label_selector' => $selector])['items'] as $key) {
        hetznercloud_Request($params, 'DELETE', "ssh_keys/{$key['id']}");
    }

    return $errors;
}

/* =========================================================================
 * Power & basic server operations
 * ====================================================================== */

/**
 * poweron | poweroff | shutdown | reboot | reset
 */
function hetznercloud_PowerAction($params, $action)
{
    $allowed = ['poweron', 'poweroff', 'shutdown', 'reboot', 'reset'];

    if (!in_array($action, $allowed, true)) {
        return hetznercloud_Fail('Unsupported power action.');
    }

    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, $action);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'), $response['data'])
        : hetznercloud_Fail($response['message']);
}

/**
 * Reset the root password and store it back on the WHMCS service.
 */
function hetznercloud_ResetRootPassword($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, 'reset_password');

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $password = $response['data']['root_password'] ?? '';

    if ($password) {
        try {
            Capsule::table('tblhosting')
                ->where('id', (int) $params['serviceid'])
                ->update(['username' => 'root', 'password' => (string) hetznercloud_Encrypt($password)]);
        } catch (\Exception $e) {
            hetznercloud_LogCall('ResetPassword/Save', $params['serviceid'], $e->getMessage());
        }
    }

    return hetznercloud_Ok(hetznercloud_L('password_reset_done'), ['password' => $password]);
}

/**
 * Rebuild from an image (or a snapshot id).
 */
function hetznercloud_Rebuild($params, $image)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    if ($image === '' || $image === null) {
        return hetznercloud_Fail(hetznercloud_L('select_os'));
    }

    $response = hetznercloud_Action($params, $serverId, 'rebuild', ['image' => $image]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    if (!empty($response['data']['root_password'])) {
        try {
            Capsule::table('tblhosting')
                ->where('id', (int) $params['serviceid'])
                ->update(['username' => 'root', 'password' => (string) hetznercloud_Encrypt($response['data']['root_password'])]);
        } catch (\Exception $e) {
            hetznercloud_LogCall('Rebuild/Save', $params['serviceid'], $e->getMessage());
        }
    }

    return hetznercloud_Ok(hetznercloud_L('rebuild_started'), [
        'password' => $response['data']['root_password'] ?? '',
    ]);
}

/**
 * Change the server type. Hetzner requires the server to be off.
 */
function hetznercloud_ChangeType($params, $type, $upgradeDisk = false)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    if (!$type) {
        return hetznercloud_Fail('No server type selected.');
    }

    $server = hetznercloud_Server($params, true);
    $wasRunning = ($server['status'] ?? '') === 'running';

    if ($wasRunning) {
        $off = hetznercloud_Action($params, $serverId, 'poweroff');
        if (!$off['success']) {
            return hetznercloud_Fail($off['message']);
        }
        hetznercloud_WaitAction($params, $off['data']['action']['id'] ?? null, 90);
    }

    $response = hetznercloud_Action($params, $serverId, 'change_type', [
        'server_type' => $type,
        'upgrade_disk' => (bool) $upgradeDisk,
    ]);

    if (!$response['success']) {
        if ($wasRunning) {
            hetznercloud_Action($params, $serverId, 'poweron');
        }
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_WaitAction($params, $response['data']['action']['id'] ?? null, 300);

    if ($wasRunning) {
        hetznercloud_Action($params, $serverId, 'poweron');
    }

    return hetznercloud_Ok(hetznercloud_L('change_package_done'));
}

/**
 * Rescue mode.
 */
function hetznercloud_Rescue($params, $enable, $type = 'linux64')
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    if (!$enable) {
        $response = hetznercloud_Action($params, $serverId, 'disable_rescue');

        return $response['success']
            ? hetznercloud_Ok(hetznercloud_L('success'))
            : hetznercloud_Fail($response['message']);
    }

    $body = ['type' => in_array($type, ['linux64', 'linux32'], true) ? $type : 'linux64'];

    // Deploy any SSH keys the customer stored, so rescue is key-accessible too.
    $keys = hetznercloud_ListSshKeys($params);
    if ($keys['success'] && $keys['data']) {
        $body['ssh_keys'] = array_column($keys['data'], 'id');
    }

    $response = hetznercloud_Action($params, $serverId, 'enable_rescue', $body);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    return hetznercloud_Ok(hetznercloud_L('rescue_reboot_hint'), [
        'password' => $response['data']['root_password'] ?? '',
    ]);
}

/**
 * Reverse DNS for a server IP.
 */
function hetznercloud_SetServerRdns($params, $ip, $hostname)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, 'change_dns_ptr', [
        'ip' => $ip,
        'dns_ptr' => $hostname !== '' ? $hostname : null,
    ]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('rdns_saved'))
        : hetznercloud_Fail($response['message']);
}

/**
 * Task history for the server.
 */
function hetznercloud_History($params, $limit = 40)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Request($params, 'GET', "servers/{$serverId}/actions", null, [
        'sort' => 'started:desc',
        'per_page' => min(50, max(1, (int) $limit)),
    ]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $actions = [];
    foreach ((array) ($response['data']['actions'] ?? []) as $action) {
        $actions[] = [
            'id' => $action['id'],
            'command' => $action['command'],
            'status' => $action['status'],
            'progress' => $action['progress'] ?? 0,
            'started' => $action['started'] ?? null,
            'finished' => $action['finished'] ?? null,
            'error' => $action['error']['message'] ?? '',
        ];
    }

    return hetznercloud_Ok('', $actions);
}

/* =========================================================================
 * Metrics & bandwidth
 * ====================================================================== */

/**
 * The full metric dashboard in one API call.
 *
 * Hetzner accepts a comma separated type list, so CPU, disk and network all
 * arrive together. Each chart carries exactly one unit - never two scales on
 * one plot - and the series order fixes the colour each series gets.
 *
 * @param int $minutes Window length in minutes (30 minutes to 30 days)
 */
function hetznercloud_Metrics($params, $minutes = 1440)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $minutes = max(30, min(43200, (int) $minutes));
    $end = time();
    $start = $end - ($minutes * 60);

    $response = hetznercloud_Request($params, 'GET', "servers/{$serverId}/metrics", null, [
        'type' => 'cpu,disk,network',
        'start' => gmdate('c', $start),
        'end' => gmdate('c', $end),
    ]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $metrics = (array) ($response['data']['metrics'] ?? []);
    $series = (array) ($metrics['time_series'] ?? []);

    /** Turn one Hetzner time series into Chart.js points. */
    $points = function ($key) use ($series) {
        if (empty($series[$key]['values'])) {
            return [];
        }

        $data = [];
        foreach ($series[$key]['values'] as $point) {
            $data[] = [
                'x' => (int) round((float) $point[0]) * 1000,
                'y' => round((float) $point[1], 3),
            ];
        }

        return $data;
    };

    $definitions = [
        [
            'key' => 'cpu',
            'title' => hetznercloud_L('cpu_usage'),
            'unit' => 'percent',
            'series' => [['label' => hetznercloud_L('cpu_usage'), 'key' => 'cpu']],
        ],
        [
            'key' => 'net_bandwidth',
            'title' => hetznercloud_L('network_traffic'),
            'unit' => 'bytes',
            'series' => [
                ['label' => hetznercloud_L('inbound'), 'key' => 'network.0.bandwidth.in'],
                ['label' => hetznercloud_L('outbound'), 'key' => 'network.0.bandwidth.out'],
            ],
        ],
        [
            'key' => 'disk_bandwidth',
            'title' => hetznercloud_L('disk_io'),
            'unit' => 'bytes',
            'series' => [
                ['label' => hetznercloud_L('read'), 'key' => 'disk.0.bandwidth.read'],
                ['label' => hetznercloud_L('write'), 'key' => 'disk.0.bandwidth.write'],
            ],
        ],
        [
            'key' => 'net_packets',
            'title' => hetznercloud_L('network_packets'),
            'unit' => 'pps',
            'series' => [
                ['label' => hetznercloud_L('inbound'), 'key' => 'network.0.pps.in'],
                ['label' => hetznercloud_L('outbound'), 'key' => 'network.0.pps.out'],
            ],
        ],
        [
            'key' => 'disk_iops',
            'title' => hetznercloud_L('disk_iops'),
            'unit' => 'iops',
            'series' => [
                ['label' => hetznercloud_L('read'), 'key' => 'disk.0.iops.read'],
                ['label' => hetznercloud_L('write'), 'key' => 'disk.0.iops.write'],
            ],
        ],
    ];

    $charts = [];

    foreach ($definitions as $definition) {
        $seriesOut = [];

        foreach ($definition['series'] as $entry) {
            $data = $points($entry['key']);

            if (!$data) {
                continue;
            }

            $seriesOut[] = ['label' => $entry['label'], 'data' => $data];
        }

        if (!$seriesOut) {
            continue;
        }

        $charts[] = [
            'key' => $definition['key'],
            'title' => $definition['title'],
            'unit' => $definition['unit'],
            'series' => $seriesOut,
        ];
    }

    return hetznercloud_Ok('', [
        'charts' => $charts,
        'start' => $start,
        'end' => $end,
        'step' => (float) ($metrics['step'] ?? 0),
    ]);
}

/**
 * Traffic counters from the server object.
 */
function hetznercloud_Bandwidth($params)
{
    $server = hetznercloud_Server($params);

    if (!$server) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $included = (float) ($server['included_traffic'] ?? 0);
    $out = (float) ($server['outgoing_traffic'] ?? 0);
    $in = (float) ($server['ingoing_traffic'] ?? 0);
    $used = $out; // Hetzner only bills outgoing traffic.

    return hetznercloud_Ok('', [
        'included' => $included,
        'included_h' => hetznercloud_FormatBytes($included),
        'outgoing' => $out,
        'outgoing_h' => hetznercloud_FormatBytes($out),
        'incoming' => $in,
        'incoming_h' => hetznercloud_FormatBytes($in),
        'used' => $used,
        'used_h' => hetznercloud_FormatBytes($used),
        'remaining' => max(0, $included - $used),
        'remaining_h' => hetznercloud_FormatBytes(max(0, $included - $used)),
        'percent' => $included > 0 ? min(100, round(($used / $included) * 100, 1)) : 0,
    ]);
}

/* =========================================================================
 * Console
 * ====================================================================== */

/**
 * Request a noVNC session and return the local console URL.
 */
function hetznercloud_Console($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, 'request_console');

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $wss = $response['data']['wss_url'] ?? '';
    $password = $response['data']['password'] ?? '';

    if (!$wss || !$password) {
        return hetznercloud_Fail('Hetzner did not return a console session.');
    }

    // The credentials stay server-side; the browser only gets a service id.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['hetznercloud_console'][(int) $params['serviceid']] = [
        'host' => $wss,
        'password' => $password,
        'name' => $params['domain'] ?: ('server-' . $serverId),
        'expires' => time() + 600,
    ];

    // Relative to the module directory: the caller knows whether it is rendering
    // in the client area or under /admin/ and prefixes accordingly.
    return hetznercloud_Ok('', [
        'path' => 'console.php?service=' . (int) $params['serviceid'],
    ]);
}

/* =========================================================================
 * Images: backups & snapshots
 * ====================================================================== */

/**
 * List images of a type belonging to this server/service.
 *
 * @param string $type snapshot|backup
 */
function hetznercloud_ListImages($params, $type)
{
    $serverId = hetznercloud_ServerId($params);
    $query = ['type' => $type, 'sort' => 'created:desc'];

    if ($type === 'backup') {
        if (!$serverId) {
            return hetznercloud_Ok('', []);
        }
        $query['bound_to'] = $serverId;
    } else {
        $query['label_selector'] = hetznercloud_LabelSelector($params['serviceid'] ?? 0);
    }

    $result = hetznercloud_Collection($params, 'images', 'images', $query);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $images = [];
    foreach ($result['items'] as $image) {
        $images[] = [
            'id' => $image['id'],
            'description' => $image['description'] ?: ('#' . $image['id']),
            'type' => $image['type'],
            'status' => $image['status'],
            'created' => $image['created'] ?? '',
            'size' => round((float) ($image['image_size'] ?? 0), 2),
            'disk_size' => $image['disk_size'] ?? 0,
            'protected' => !empty($image['protection']['delete']),
        ];
    }

    if ($type === 'snapshot') {
        hetznercloud_SyncResources($params['serviceid'] ?? 0, 'snapshot', array_map(function ($i) {
            return ['id' => $i['id'], 'name' => $i['description']];
        }, $images));
    }

    return hetznercloud_Ok('', $images);
}

/**
 * Take a snapshot, honouring the product's snapshot limit.
 */
function hetznercloud_CreateSnapshot($params, $description = '')
{
    $serverId = hetznercloud_ServerId($params);
    $options = hetznercloud_Options($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $existing = hetznercloud_ListImages($params, 'snapshot');
    $count = $existing['success'] ? count($existing['data']) : 0;

    if ($options['snapshot_limit'] > 0 && $count >= $options['snapshot_limit']) {
        return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['snapshot_limit']]));
    }

    if ($options['snapshot_limit'] <= 0) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $description = trim($description) !== ''
        ? trim($description)
        : ('snapshot-' . date('Y-m-d-His'));

    $response = hetznercloud_Action($params, $serverId, 'create_image', [
        'description' => mb_substr($description, 0, 100),
        'type' => 'snapshot',
        'labels' => hetznercloud_Labels($params),
    ]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $image = $response['data']['image'] ?? [];
    if (!empty($image['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'snapshot', $image['id'], $description);
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $image);
}

/**
 * Delete a snapshot owned by this service.
 */
function hetznercloud_DeleteImage($params, $imageId)
{
    if (!hetznercloud_OwnsResource($params, 'images', $imageId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $response = hetznercloud_Request($params, 'DELETE', "images/{$imageId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_UntrackResource('snapshot', $imageId);

    return hetznercloud_Ok(hetznercloud_L('success'));
}

/**
 * Restore a snapshot or backup onto the server (a rebuild from that image).
 */
function hetznercloud_RestoreImage($params, $imageId)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Request($params, 'GET', "images/{$imageId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $image = $response['data']['image'] ?? [];
    $boundTo = $image['bound_to'] ?? null;
    $labelService = (int) ($image['labels']['whmcs_service'] ?? 0);

    if ((int) $boundTo !== (int) $serverId && $labelService !== (int) ($params['serviceid'] ?? -1)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    return hetznercloud_Rebuild($params, (string) $imageId);
}

/**
 * Automatic backups on/off.
 */
function hetznercloud_Backups($params, $enable)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, $enable ? 'enable_backup' : 'disable_backup');

    return $response['success']
        ? hetznercloud_Ok($enable ? hetznercloud_L('backups_enabled') : hetznercloud_L('backups_disabled'))
        : hetznercloud_Fail($response['message']);
}

/* =========================================================================
 * ISO
 * ====================================================================== */

function hetznercloud_AttachIso($params, $iso)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    if ($iso === '') {
        return hetznercloud_Fail('No ISO selected.');
    }

    $response = hetznercloud_Action($params, $serverId, 'attach_iso', ['iso' => $iso]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('iso_mounted_notice'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_DetachIso($params)
{
    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $response = hetznercloud_Action($params, $serverId, 'detach_iso');

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

/* =========================================================================
 * Floating IPs
 * ====================================================================== */

function hetznercloud_ListFloatingIps($params)
{
    $result = hetznercloud_Collection($params, 'floating_ips', 'floating_ips', [
        'label_selector' => hetznercloud_LabelSelector($params['serviceid'] ?? 0),
    ]);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $ips = [];
    foreach ($result['items'] as $ip) {
        $ips[] = [
            'id' => $ip['id'],
            'ip' => $ip['ip'],
            'type' => $ip['type'],
            'server' => $ip['server'] ?? null,
            'description' => $ip['description'] ?? '',
            'dns_ptr' => $ip['dns_ptr'][0]['dns_ptr'] ?? '',
            'home_location' => $ip['home_location']['name'] ?? '',
            'blocked' => !empty($ip['blocked']),
        ];
    }

    hetznercloud_SyncResources($params['serviceid'] ?? 0, 'floating_ip', array_map(function ($i) {
        return ['id' => $i['id'], 'name' => $i['ip']];
    }, $ips));

    return hetznercloud_Ok('', $ips);
}

function hetznercloud_CreateFloatingIp($params, $type = 'ipv4')
{
    $serverId = hetznercloud_ServerId($params);
    $type = $type === 'ipv6' ? 'ipv6' : 'ipv4';

    $body = [
        'type' => $type,
        'description' => 'WHMCS service #' . ($params['serviceid'] ?? 0),
        'labels' => hetznercloud_Labels($params),
    ];

    if ($serverId) {
        $body['server'] = (int) $serverId;
    } else {
        $options = hetznercloud_Options($params);
        $body['home_location'] = $options['location'];
    }

    $response = hetznercloud_Request($params, 'POST', 'floating_ips', $body);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $ip = $response['data']['floating_ip'] ?? [];

    if (!empty($ip['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'floating_ip', $ip['id'], $ip['ip'] ?? '');
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $ip);
}

/**
 * Customer-initiated floating IP order, limited by the product setting.
 */
function hetznercloud_OrderFloatingIp($params, $type = 'ipv4')
{
    $options = hetznercloud_Options($params);
    $current = hetznercloud_ListFloatingIps($params);
    $count = $current['success'] ? count($current['data']) : 0;

    if (empty($options['allow_floating_ips_order']) || $options['floating_ip_limit'] <= 0) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    if ($count >= $options['floating_ip_limit']) {
        return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['floating_ip_limit']]));
    }

    if ($options['floating_protocol'] !== 'both' && $type !== $options['floating_protocol']) {
        $type = $options['floating_protocol'];
    }

    return hetznercloud_CreateFloatingIp($params, $type);
}

function hetznercloud_DeleteFloatingIp($params, $ipId)
{
    if (!hetznercloud_OwnsResource($params, 'floating_ips', $ipId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    hetznercloud_Request($params, 'POST', "floating_ips/{$ipId}/actions/unassign");
    $response = hetznercloud_Request($params, 'DELETE', "floating_ips/{$ipId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_UntrackResource('floating_ip', $ipId);

    return hetznercloud_Ok(hetznercloud_L('success'));
}

function hetznercloud_AssignFloatingIp($params, $ipId, $assign = true)
{
    if (!hetznercloud_OwnsResource($params, 'floating_ips', $ipId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    if (!$assign) {
        $response = hetznercloud_Request($params, 'POST', "floating_ips/{$ipId}/actions/unassign");
    } else {
        $serverId = hetznercloud_ServerId($params);
        if (!$serverId) {
            return hetznercloud_Fail(hetznercloud_L('server_missing'));
        }
        $response = hetznercloud_Request($params, 'POST', "floating_ips/{$ipId}/actions/assign", [
            'server' => (int) $serverId,
        ]);
    }

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_FloatingIpRdns($params, $ipId, $ip, $hostname)
{
    if (!hetznercloud_OwnsResource($params, 'floating_ips', $ipId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $response = hetznercloud_Request($params, 'POST', "floating_ips/{$ipId}/actions/change_dns_ptr", [
        'ip' => $ip,
        'dns_ptr' => $hostname !== '' ? $hostname : null,
    ]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('rdns_saved'))
        : hetznercloud_Fail($response['message']);
}

/* =========================================================================
 * Firewalls
 * ====================================================================== */

function hetznercloud_ListFirewalls($params)
{
    $result = hetznercloud_Collection($params, 'firewalls', 'firewalls', [
        'label_selector' => hetznercloud_LabelSelector($params['serviceid'] ?? 0),
    ]);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $serverId = hetznercloud_ServerId($params);
    $firewalls = [];

    foreach ($result['items'] as $firewall) {
        $applied = false;
        foreach ((array) ($firewall['applied_to'] ?? []) as $resource) {
            if (($resource['type'] ?? '') === 'server' && (int) ($resource['server']['id'] ?? 0) === (int) $serverId) {
                $applied = true;
            }
        }

        $firewalls[] = [
            'id' => $firewall['id'],
            'name' => $firewall['name'],
            'applied' => $applied,
            'rules' => array_map(function ($rule) {
                return [
                    'direction' => $rule['direction'],
                    'protocol' => $rule['protocol'],
                    'port' => $rule['port'] ?? '',
                    'source_ips' => $rule['source_ips'] ?? [],
                    'destination_ips' => $rule['destination_ips'] ?? [],
                    'description' => $rule['description'] ?? '',
                ];
            }, (array) ($firewall['rules'] ?? [])),
        ];
    }

    hetznercloud_SyncResources($params['serviceid'] ?? 0, 'firewall', array_map(function ($f) {
        return ['id' => $f['id'], 'name' => $f['name']];
    }, $firewalls));

    return hetznercloud_Ok('', $firewalls);
}

function hetznercloud_CreateFirewall($params, $name, $applyToServer = true)
{
    $options = hetznercloud_Options($params);

    if ($options['firewall_limit'] <= 0) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $existing = hetznercloud_ListFirewalls($params);
    if ($existing['success'] && count($existing['data']) >= $options['firewall_limit']) {
        return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['firewall_limit']]));
    }

    $name = hetznercloud_SafeName($name, 'firewall-' . ($params['serviceid'] ?? 0));

    $body = [
        'name' => $name,
        'labels' => hetznercloud_Labels($params),
        'rules' => [],
    ];

    $serverId = hetznercloud_ServerId($params);
    if ($applyToServer && $serverId) {
        $body['apply_to'] = [['type' => 'server', 'server' => ['id' => (int) $serverId]]];
    }

    $response = hetznercloud_Request($params, 'POST', 'firewalls', $body);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $firewall = $response['data']['firewall'] ?? [];
    if (!empty($firewall['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'firewall', $firewall['id'], $name);
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $firewall);
}

/**
 * Replace the rule set of a firewall, enforcing the per-direction limits.
 *
 * @param array $rules [{direction, protocol, port, source_ips[], destination_ips[], description}]
 */
function hetznercloud_SetFirewallRules($params, $firewallId, array $rules)
{
    if (!hetznercloud_OwnsResource($params, 'firewalls', $firewallId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $options = hetznercloud_Options($params);
    $clean = [];
    $counts = ['in' => 0, 'out' => 0];

    foreach ($rules as $rule) {
        $direction = ($rule['direction'] ?? 'in') === 'out' ? 'out' : 'in';
        $protocol = strtolower($rule['protocol'] ?? 'tcp');

        if (!in_array($protocol, ['tcp', 'udp', 'icmp', 'esp', 'gre'], true)) {
            continue;
        }

        $counts[$direction]++;

        if ($direction === 'in' && $options['fw_in_limit'] > 0 && $counts['in'] > $options['fw_in_limit']) {
            return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['fw_in_limit']]));
        }
        if ($direction === 'out' && $options['fw_out_limit'] > 0 && $counts['out'] > $options['fw_out_limit']) {
            return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['fw_out_limit']]));
        }

        $entry = [
            'direction' => $direction,
            'protocol' => $protocol,
            'description' => mb_substr((string) ($rule['description'] ?? ''), 0, 255),
        ];

        if (in_array($protocol, ['tcp', 'udp'], true)) {
            $port = trim((string) ($rule['port'] ?? ''));
            if ($port === '' || !preg_match('/^(any|\d{1,5}(-\d{1,5})?)$/', $port)) {
                continue;
            }
            $entry['port'] = $port;
        }

        $ips = array_values(array_filter(array_map('trim', (array) ($rule['ips'] ?? $rule['source_ips'] ?? $rule['destination_ips'] ?? []))));
        $ips = array_filter($ips, function ($cidr) {
            return (bool) preg_match('#^[0-9a-f:.]+/\d{1,3}$#i', $cidr);
        });

        if (!$ips) {
            $ips = $direction === 'in' ? ['0.0.0.0/0', '::/0'] : ['0.0.0.0/0', '::/0'];
        }

        if ($direction === 'in') {
            $entry['source_ips'] = array_values($ips);
        } else {
            $entry['destination_ips'] = array_values($ips);
        }

        $clean[] = $entry;
    }

    $response = hetznercloud_Request($params, 'POST', "firewalls/{$firewallId}/actions/set_rules", [
        'rules' => $clean,
    ]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_ApplyFirewall($params, $firewallId, $apply = true)
{
    if (!hetznercloud_OwnsResource($params, 'firewalls', $firewallId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $resource = [['type' => 'server', 'server' => ['id' => (int) $serverId]]];

    $response = $apply
        ? hetznercloud_Request($params, 'POST', "firewalls/{$firewallId}/actions/apply_to_resources", ['apply_to' => $resource])
        : hetznercloud_Request($params, 'POST', "firewalls/{$firewallId}/actions/remove_from_resources", ['remove_from' => $resource]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_DeleteFirewall($params, $firewallId)
{
    if (!hetznercloud_OwnsResource($params, 'firewalls', $firewallId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    hetznercloud_ApplyFirewall($params, $firewallId, false);
    $response = hetznercloud_Request($params, 'DELETE', "firewalls/{$firewallId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_UntrackResource('firewall', $firewallId);

    return hetznercloud_Ok(hetznercloud_L('success'));
}

/* =========================================================================
 * Private networks
 * ====================================================================== */

function hetznercloud_ListNetworks($params)
{
    $result = hetznercloud_Collection($params, 'networks', 'networks', [
        'label_selector' => hetznercloud_LabelSelector($params['serviceid'] ?? 0),
    ]);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $serverId = hetznercloud_ServerId($params);
    $networks = [];

    foreach ($result['items'] as $network) {
        $attached = in_array((int) $serverId, array_map('intval', (array) ($network['servers'] ?? [])), true);

        $networks[] = [
            'id' => $network['id'],
            'name' => $network['name'],
            'ip_range' => $network['ip_range'],
            'attached' => $attached,
            'subnets' => array_map(function ($subnet) {
                return [
                    'type' => $subnet['type'] ?? 'cloud',
                    'ip_range' => $subnet['ip_range'] ?? '',
                    'network_zone' => $subnet['network_zone'] ?? '',
                    'gateway' => $subnet['gateway'] ?? '',
                ];
            }, (array) ($network['subnets'] ?? [])),
            'routes' => array_map(function ($route) {
                return [
                    'destination' => $route['destination'] ?? '',
                    'gateway' => $route['gateway'] ?? '',
                ];
            }, (array) ($network['routes'] ?? [])),
        ];
    }

    hetznercloud_SyncResources($params['serviceid'] ?? 0, 'network', array_map(function ($n) {
        return ['id' => $n['id'], 'name' => $n['name']];
    }, $networks));

    return hetznercloud_Ok('', $networks);
}

function hetznercloud_CreateNetwork($params, $name, $ipRange = '10.0.0.0/16')
{
    $options = hetznercloud_Options($params);

    if ($options['network_limit'] <= 0) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $existing = hetznercloud_ListNetworks($params);
    if ($existing['success'] && count($existing['data']) >= $options['network_limit']) {
        return hetznercloud_Fail(hetznercloud_L('limit_reached', ['limit' => $options['network_limit']]));
    }

    if (!preg_match('#^10\.\d{1,3}\.\d{1,3}\.\d{1,3}/\d{1,2}$#', $ipRange)
        && !preg_match('#^172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}/\d{1,2}$#', $ipRange)
        && !preg_match('#^192\.168\.\d{1,3}\.\d{1,3}/\d{1,2}$#', $ipRange)) {
        return hetznercloud_Fail('IP range must be inside 10.0.0.0/8, 172.16.0.0/12 or 192.168.0.0/16.');
    }

    $response = hetznercloud_Request($params, 'POST', 'networks', [
        'name' => hetznercloud_SafeName($name, 'net-' . ($params['serviceid'] ?? 0)),
        'ip_range' => $ipRange,
        'labels' => hetznercloud_Labels($params),
    ]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $network = $response['data']['network'] ?? [];
    if (!empty($network['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'network', $network['id'], $network['name'] ?? '');
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $network);
}

function hetznercloud_DeleteNetwork($params, $networkId)
{
    if (!hetznercloud_OwnsResource($params, 'networks', $networkId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $response = hetznercloud_Request($params, 'DELETE', "networks/{$networkId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_UntrackResource('network', $networkId);

    return hetznercloud_Ok(hetznercloud_L('success'));
}

function hetznercloud_NetworkSubnet($params, $networkId, $add, $data = [])
{
    if (!hetznercloud_OwnsResource($params, 'networks', $networkId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    if ($add) {
        $zone = $data['network_zone'] ?? '';
        if (!$zone) {
            $options = hetznercloud_Options($params);
            $zone = hetznercloud_NetworkZoneForLocation($params, $options['location']);
        }

        $body = [
            'type' => 'cloud',
            'network_zone' => $zone,
            'ip_range' => $data['ip_range'] ?? '',
        ];

        if ($body['ip_range'] === '') {
            unset($body['ip_range']);
        }

        $response = hetznercloud_Request($params, 'POST', "networks/{$networkId}/actions/add_subnet", $body);
    } else {
        $response = hetznercloud_Request($params, 'POST', "networks/{$networkId}/actions/delete_subnet", [
            'ip_range' => $data['ip_range'] ?? '',
        ]);
    }

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_NetworkRoute($params, $networkId, $add, $destination, $gateway)
{
    if (!hetznercloud_OwnsResource($params, 'networks', $networkId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $action = $add ? 'add_route' : 'delete_route';

    $response = hetznercloud_Request($params, 'POST', "networks/{$networkId}/actions/{$action}", [
        'destination' => $destination,
        'gateway' => $gateway,
    ]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

function hetznercloud_NetworkAttach($params, $networkId, $attach = true)
{
    if (!hetznercloud_OwnsResource($params, 'networks', $networkId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $serverId = hetznercloud_ServerId($params);

    if (!$serverId) {
        return hetznercloud_Fail(hetznercloud_L('server_missing'));
    }

    $action = $attach ? 'attach_to_network' : 'detach_from_network';

    $response = hetznercloud_Action($params, $serverId, $action, ['network' => (int) $networkId]);

    return $response['success']
        ? hetznercloud_Ok(hetznercloud_L('success'))
        : hetznercloud_Fail($response['message']);
}

/**
 * Network zone that matches a location (eu-central, us-east, ...).
 */
function hetznercloud_NetworkZoneForLocation($params, $locationName)
{
    foreach (hetznercloud_Locations($params) as $location) {
        if ($location['name'] === $locationName && $location['network_zone']) {
            return $location['network_zone'];
        }
    }

    return 'eu-central';
}

/* =========================================================================
 * Volumes
 * ====================================================================== */

function hetznercloud_ListVolumes($params)
{
    $result = hetznercloud_Collection($params, 'volumes', 'volumes', [
        'label_selector' => hetznercloud_LabelSelector($params['serviceid'] ?? 0),
    ]);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $volumes = [];
    foreach ($result['items'] as $volume) {
        $volumes[] = [
            'id' => $volume['id'],
            'name' => $volume['name'],
            'size' => $volume['size'],
            'format' => $volume['format'] ?? '',
            'linux_device' => $volume['linux_device'] ?? '',
            'server' => $volume['server'] ?? null,
            'location' => $volume['location']['name'] ?? '',
        ];
    }

    hetznercloud_SyncResources($params['serviceid'] ?? 0, 'volume', array_map(function ($v) {
        return ['id' => $v['id'], 'name' => $v['name']];
    }, $volumes));

    return hetznercloud_Ok('', $volumes);
}

function hetznercloud_CreateVolume($params, $size, $format = 'ext4', $automount = false)
{
    $serverId = hetznercloud_ServerId($params);
    $size = max(10, (int) $size);

    $body = [
        'name' => hetznercloud_SafeName('vol-' . ($params['serviceid'] ?? 0) . '-' . substr(md5(uniqid('', true)), 0, 6), 'volume'),
        'size' => $size,
        'labels' => hetznercloud_Labels($params),
    ];

    if (in_array($format, ['ext4', 'xfs'], true)) {
        $body['format'] = $format;
    }

    if ($serverId) {
        $body['server'] = (int) $serverId;
        $body['automount'] = (bool) $automount;
    } else {
        $options = hetznercloud_Options($params);
        $body['location'] = $options['location'];
    }

    $response = hetznercloud_Request($params, 'POST', 'volumes', $body);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $volume = $response['data']['volume'] ?? [];
    if (!empty($volume['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'volume', $volume['id'], $volume['name'] ?? '');
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $volume);
}

/* =========================================================================
 * SSH keys
 * ====================================================================== */

function hetznercloud_ListSshKeys($params)
{
    $result = hetznercloud_Collection($params, 'ssh_keys', 'ssh_keys', [
        'label_selector' => hetznercloud_LabelSelector($params['serviceid'] ?? 0),
    ]);

    if (!$result['success']) {
        return hetznercloud_Fail($result['message']);
    }

    $keys = [];
    foreach ($result['items'] as $key) {
        $keys[] = [
            'id' => $key['id'],
            'name' => $key['name'],
            'fingerprint' => $key['fingerprint'],
            'public_key' => $key['public_key'],
            'created' => $key['created'] ?? '',
        ];
    }

    hetznercloud_SyncResources($params['serviceid'] ?? 0, 'ssh_key', array_map(function ($k) {
        return ['id' => $k['id'], 'name' => $k['name']];
    }, $keys));

    return hetznercloud_Ok('', $keys);
}

function hetznercloud_CreateSshKey($params, $name, $publicKey)
{
    $publicKey = trim(preg_replace('/\s+/', ' ', (string) $publicKey));

    if (!preg_match('/^(ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp(256|384|521)|sk-ssh-ed25519@openssh\.com|sk-ecdsa-sha2-nistp256@openssh\.com)\s+[A-Za-z0-9+\/=]+/', $publicKey)) {
        return hetznercloud_Fail('That does not look like an OpenSSH public key.');
    }

    $response = hetznercloud_Request($params, 'POST', 'ssh_keys', [
        'name' => hetznercloud_SafeName($name, 'key-' . ($params['serviceid'] ?? 0)) . '-' . substr(md5($publicKey), 0, 6),
        'public_key' => $publicKey,
        'labels' => hetznercloud_Labels($params),
    ]);

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    $key = $response['data']['ssh_key'] ?? [];
    if (!empty($key['id'])) {
        hetznercloud_TrackResource($params['serviceid'] ?? 0, 'ssh_key', $key['id'], $key['name'] ?? '');
    }

    return hetznercloud_Ok(hetznercloud_L('success'), $key);
}

function hetznercloud_DeleteSshKey($params, $keyId)
{
    if (!hetznercloud_OwnsResource($params, 'ssh_keys', $keyId)) {
        return hetznercloud_Fail(hetznercloud_L('feature_disabled'));
    }

    $response = hetznercloud_Request($params, 'DELETE', "ssh_keys/{$keyId}");

    if (!$response['success']) {
        return hetznercloud_Fail($response['message']);
    }

    hetznercloud_UntrackResource('ssh_key', $keyId);

    return hetznercloud_Ok(hetznercloud_L('success'));
}

/* =========================================================================
 * Purchasable add-ons
 * ====================================================================== */

/**
 * The product's configurable options, with prices and what the client holds
 * today. Buying more goes through the normal WHMCS upgrade flow so it is
 * invoiced properly; ChangePackage then reconciles the server.
 */
function hetznercloud_Addons($params)
{
    $productId = (int) ($params['pid'] ?? 0);
    $currencyId = (int) ($params['clientsdetails']['currency'] ?? 0);

    if (!$productId) {
        return hetznercloud_Ok('', ['items' => [], 'currency' => '']);
    }

    try {
        if (!$currencyId) {
            $currencyId = (int) Capsule::table('tblcurrencies')->where('default', 1)->value('id') ?: 1;
        }

        $currency = Capsule::table('tblcurrencies')->where('id', $currencyId)->first(['prefix', 'suffix', 'code']);

        $groupIds = Capsule::table('tblproductconfiglinks')->where('pid', $productId)->pluck('gid')->all();

        if (!$groupIds) {
            return hetznercloud_Ok('', ['items' => [], 'currency' => $currency->code ?? '']);
        }

        $rows = Capsule::table('tblproductconfigoptions')
            ->whereIn('gid', $groupIds)
            ->orderBy('order')
            ->get(['id', 'optionname', 'optiontype', 'qtyminimum', 'qtymaximum', 'hidden']);

        $selected = $params['configoptions'] ?? [];
        $items = [];

        foreach ($rows as $row) {
            if (!empty($row->hidden)) {
                continue;
            }

            $subs = Capsule::table('tblproductconfigoptionssub')
                ->where('configid', $row->id)
                ->orderBy('sortorder')
                ->get(['id', 'optionname']);

            $choices = [];
            foreach ($subs as $sub) {
                $price = Capsule::table('tblpricing')
                    ->where('type', 'configoptions')
                    ->where('currency', $currencyId)
                    ->where('relid', $sub->id)
                    ->value('monthly');

                $parts = explode('|', $sub->optionname, 2);

                $choices[] = [
                    'value' => trim($parts[0]),
                    'label' => trim($parts[1] ?? $parts[0]),
                    'monthly' => round((float) $price, 2),
                ];
            }

            $current = $selected[$row->optionname] ?? '';

            $items[] = [
                'name' => $row->optionname,
                'quantity' => (int) $row->optiontype === 4,
                'yesno' => (int) $row->optiontype === 3,
                'min' => (int) $row->qtyminimum,
                'max' => (int) $row->qtymaximum,
                'current' => is_scalar($current) ? (string) $current : '',
                'choices' => $choices,
            ];
        }

        return hetznercloud_Ok('', [
            'items' => $items,
            'currency' => $currency->code ?? '',
            'prefix' => $currency->prefix ?? '',
            'suffix' => $currency->suffix ?? '',
        ]);
    } catch (\Exception $e) {
        hetznercloud_LogCall('Addons', $productId, $e->getMessage());

        return hetznercloud_Fail(hetznercloud_L('request_failed'));
    }
}

/* =========================================================================
 * Shared guards
 * ====================================================================== */

/**
 * Confirm a resource carries this service's label before mutating it.
 *
 * The DB cache answers instantly for anything we created; otherwise we ask
 * Hetzner, because the label is the real source of truth.
 */
function hetznercloud_OwnsResource($params, $collection, $resourceId)
{
    $serviceId = (int) ($params['serviceid'] ?? 0);

    if (!$serviceId || !$resourceId || !ctype_digit((string) $resourceId)) {
        return false;
    }

    $typeMap = [
        'floating_ips' => 'floating_ip',
        'firewalls' => 'firewall',
        'networks' => 'network',
        'volumes' => 'volume',
        'ssh_keys' => 'ssh_key',
        'images' => 'snapshot',
    ];

    if (isset($typeMap[$collection])
        && hetznercloud_ResourceOwner($typeMap[$collection], $resourceId) === $serviceId) {
        return true;
    }

    $singular = [
        'floating_ips' => 'floating_ip',
        'firewalls' => 'firewall',
        'networks' => 'network',
        'volumes' => 'volume',
        'ssh_keys' => 'ssh_key',
        'images' => 'image',
    ][$collection] ?? null;

    if (!$singular) {
        return false;
    }

    $response = hetznercloud_Request($params, 'GET', "{$collection}/{$resourceId}");

    if (!$response['success']) {
        return false;
    }

    $labels = $response['data'][$singular]['labels'] ?? [];

    return (int) ($labels['whmcs_service'] ?? 0) === $serviceId;
}

/**
 * Hetzner names must be RFC1123-ish.
 */
function hetznercloud_SafeName($name, $fallback)
{
    $name = strtolower(trim((string) $name));
    $name = preg_replace('/[^a-z0-9\-\.]/', '-', $name);
    $name = trim(preg_replace('/-+/', '-', $name), '-.');

    if ($name === '' || !preg_match('/^[a-z0-9]/', $name)) {
        return $fallback;
    }

    return substr($name, 0, 60);
}
