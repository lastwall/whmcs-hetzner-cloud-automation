<?php
/**
 * Local persistence for the Hetzner Cloud module.
 *
 * Hetzner labels are the source of truth for which resource belongs to which
 * WHMCS service; the mod_hetznercloud_resources table is a cache so listings
 * and limit checks do not need an API round trip.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/api.php';

/* -------------------------------------------------------------------------
 * Security helpers
 *
 * Shared by the client area, the admin service page and the addon, so all
 * three use one CSRF token and one crypto path.
 * ---------------------------------------------------------------------- */

/**
 * Session CSRF token.
 *
 * Prefers WHMCS's own token so it matches anything the host renders, and falls
 * back to a token of our own if that helper is ever removed.
 */
function hetznercloud_CsrfToken()
{
    if (function_exists('generate_token')) {
        $token = generate_token('plain');

        if (is_string($token) && $token !== '') {
            return $token;
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['hetznercloud_token'])) {
        $_SESSION['hetznercloud_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['hetznercloud_token'];
}

/**
 * Constant-time CSRF check for a submitted form or JSON call.
 */
function hetznercloud_CheckCsrf($submitted)
{
    $expected = hetznercloud_CsrfToken();

    return is_string($submitted) && $submitted !== '' && hash_equals($expected, $submitted);
}

/**
 * The logged-in admin id, or 0.
 *
 * Uses the WHMCS 8/9 CurrentUser service when available and falls back to the
 * session key older releases populate.
 */
function hetznercloud_CurrentAdminId()
{
    if (class_exists('\WHMCS\Authentication\CurrentUser')) {
        try {
            $admin = (new \WHMCS\Authentication\CurrentUser())->admin();

            if ($admin && !empty($admin->id)) {
                return (int) $admin->id;
            }
        } catch (\Throwable $e) {
            // fall through to the session
        }
    }

    return (int) ($_SESSION['adminid'] ?? 0);
}

/**
 * The logged-in client id, or 0.
 */
function hetznercloud_CurrentClientId()
{
    if (class_exists('\WHMCS\Authentication\CurrentUser')) {
        try {
            $client = (new \WHMCS\Authentication\CurrentUser())->client();

            if ($client && !empty($client->id)) {
                return (int) $client->id;
            }
        } catch (\Throwable $e) {
            // fall through to the session
        }
    }

    return (int) ($_SESSION['uid'] ?? 0);
}

/**
 * Encrypt a secret with WHMCS's key.
 *
 * Returns null when the helper is unavailable, so a caller stores nothing
 * rather than silently writing a plaintext password to the database.
 */
function hetznercloud_Encrypt($value)
{
    if (!function_exists('encrypt')) {
        hetznercloud_LogCall('Encrypt', '', 'WHMCS encrypt() is unavailable - secret not stored');
        return null;
    }

    return encrypt($value);
}

/**
 * Decrypt a WHMCS-encrypted value.
 */
function hetznercloud_Decrypt($value)
{
    if ($value === null || $value === '' || !function_exists('decrypt')) {
        return '';
    }

    return (string) decrypt($value);
}

/* -------------------------------------------------------------------------
 * Schema
 * ---------------------------------------------------------------------- */

/**
 * Create the module tables. Safe to call repeatedly.
 */
function hetznercloud_InstallTables()
{
    static $done = null;

    if ($done !== null) {
        return $done;
    }

    try {
        if (!Capsule::schema()->hasTable('mod_hetznercloud_resources')) {
            Capsule::schema()->create('mod_hetznercloud_resources', function ($table) {
                $table->increments('id');
                $table->integer('serviceid')->index();
                $table->string('type', 32)->index();
                $table->string('resource_id', 64);
                $table->string('name', 191)->default('');
                $table->text('meta')->nullable();
                $table->integer('created_at')->default(0);
                $table->integer('updated_at')->default(0);
                $table->unique(['type', 'resource_id'], 'hc_type_resource');
            });
        }

        if (!Capsule::schema()->hasTable('mod_hetznercloud_settings')) {
            Capsule::schema()->create('mod_hetznercloud_settings', function ($table) {
                $table->string('setting', 64)->primary();
                $table->text('value')->nullable();
            });
        }

        $done = true;
    } catch (\Exception $e) {
        hetznercloud_LogCall('InstallTables', '', $e->getMessage());
        $done = false;
    }

    return $done;
}

/* -------------------------------------------------------------------------
 * Settings (global + per product)
 * ---------------------------------------------------------------------- */

/**
 * Read a module setting.
 */
function hetznercloud_Setting($key, $default = null, $forget = false)
{
    static $cache = null;

    if ($forget) {
        $cache = null;
        return $default;
    }

    if ($cache === null) {
        $cache = [];

        // Values managed from Addons > Hetzner Cloud (WHMCS stores them here).
        try {
            foreach (Capsule::table('tbladdonmodules')->where('module', 'hetznercloud')->get() as $row) {
                $cache[$row->setting] = $row->value;
            }
        } catch (\Exception $e) {
            // addon not activated - fine
        }

        try {
            foreach (Capsule::table('mod_hetznercloud_settings')->get() as $row) {
                $cache[$row->setting] = $row->value;
            }
        } catch (\Exception $e) {
            // table not created yet - fine
        }
    }

    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/**
 * Write a module setting.
 */
function hetznercloud_SetSetting($key, $value)
{
    try {
        Capsule::table('mod_hetznercloud_settings')->updateOrInsert(
            ['setting' => $key],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value)]
        );

        hetznercloud_Setting(null, null, true);

        return true;
    } catch (\Exception $e) {
        hetznercloud_LogCall('SetSetting', $key, $e->getMessage());
        return false;
    }
}

/* -------------------------------------------------------------------------
 * Per-product feature matrix
 *
 * WHMCS only gives a module 24 configoption slots, which is not enough to hold
 * a switch per feature. Feature visibility therefore lives here and is edited
 * from Addons > Hetzner Cloud. Anything not saved falls back to the default
 * below, so a product an admin never touched still gets the full panel.
 * ---------------------------------------------------------------------- */

/**
 * Every switchable client-area feature.
 *
 * "billable" marks the ones that cost the customer money, so they default to
 * off and are sold through a configurable option instead.
 */
function hetznercloud_FeatureDefinitions()
{
    return [
        'power' => ['label' => 'Power controls', 'group' => 'Server', 'default' => 1],
        'reset_password' => ['label' => 'Reset root password', 'group' => 'Server', 'default' => 1],
        'console' => ['label' => 'noVNC console', 'group' => 'Server', 'default' => 1],
        'rebuild' => ['label' => 'Rebuild / reinstall OS', 'group' => 'Server', 'default' => 1],
        'rescue' => ['label' => 'Rescue mode', 'group' => 'Server', 'default' => 1],
        'iso' => ['label' => 'Mount ISO images', 'group' => 'Server', 'default' => 1],
        'change_plan' => ['label' => 'Change plan link', 'group' => 'Server', 'default' => 1],

        'graphs' => ['label' => 'CPU / disk / network graphs', 'group' => 'Monitoring', 'default' => 1],
        'bandwidth' => ['label' => 'Bandwidth usage', 'group' => 'Monitoring', 'default' => 1],
        'history' => ['label' => 'Task history', 'group' => 'Monitoring', 'default' => 1],

        'backups' => ['label' => 'Show backups', 'group' => 'Data', 'default' => 1],
        'backups_manage' => ['label' => 'Let the client switch backups on/off', 'group' => 'Data', 'default' => 0, 'billable' => true],
        'snapshots' => ['label' => 'Snapshots', 'group' => 'Data', 'default' => 1],
        'volumes' => ['label' => 'Show attached volumes', 'group' => 'Data', 'default' => 1],

        'floating_ips' => ['label' => 'Show floating IPs', 'group' => 'Network', 'default' => 1],
        'floating_ips_order' => ['label' => 'Let the client order floating IPs', 'group' => 'Network', 'default' => 0, 'billable' => true],
        'rdns' => ['label' => 'Reverse DNS (IPv4 + IPv6)', 'group' => 'Network', 'default' => 1],
        'networks' => ['label' => 'Private networks', 'group' => 'Network', 'default' => 1],
        'firewall' => ['label' => 'Firewalls', 'group' => 'Network', 'default' => 1],

        'ssh' => ['label' => 'SSH keys', 'group' => 'Access', 'default' => 1],
        'addons' => ['label' => 'Add-ons / upgrade tab', 'group' => 'Access', 'default' => 1],
    ];
}

/**
 * Numeric per-product limits. A null value means "inherit the product setting".
 */
function hetznercloud_LimitDefinitions()
{
    return [
        'snapshot_limit' => ['label' => 'Snapshots', 'configoption' => 12, 'default' => 3],
        'firewall_limit' => ['label' => 'Firewalls', 'configoption' => 13, 'default' => 2],
        'fw_in_limit' => ['label' => 'Inbound rules per firewall', 'configoption' => 14, 'default' => 10],
        'fw_out_limit' => ['label' => 'Outbound rules per firewall', 'configoption' => 15, 'default' => 20],
        'network_limit' => ['label' => 'Private networks', 'configoption' => 16, 'default' => 1],
        'floating_ip_limit' => ['label' => 'Floating IPs the client may hold', 'configoption' => 9, 'default' => null],
    ];
}

/**
 * Saved overrides for a product.
 *
 * @return array{features:array<string,bool>,limits:array<string,int|null>}
 */
function hetznercloud_ProductOverrides($productId)
{
    $saved = json_decode((string) hetznercloud_Setting('product_' . (int) $productId, ''), true);
    $saved = is_array($saved) ? $saved : [];

    $features = [];
    foreach (hetznercloud_FeatureDefinitions() as $key => $definition) {
        $features[$key] = array_key_exists($key, $saved['features'] ?? [])
            ? (bool) $saved['features'][$key]
            : (bool) $definition['default'];
    }

    $limits = [];
    foreach (hetznercloud_LimitDefinitions() as $key => $definition) {
        $value = $saved['limits'][$key] ?? null;
        $limits[$key] = ($value === null || $value === '') ? null : max(0, (int) $value);
    }

    return ['features' => $features, 'limits' => $limits];
}

/**
 * Store the overrides for a product.
 */
function hetznercloud_SaveProductOverrides($productId, array $features, array $limits)
{
    $clean = ['features' => [], 'limits' => []];

    foreach (hetznercloud_FeatureDefinitions() as $key => $definition) {
        $clean['features'][$key] = !empty($features[$key]) ? 1 : 0;
    }

    foreach (hetznercloud_LimitDefinitions() as $key => $definition) {
        $value = $limits[$key] ?? '';
        $clean['limits'][$key] = ($value === '' || $value === null) ? null : max(0, (int) $value);
    }

    return hetznercloud_SetSetting('product_' . (int) $productId, json_encode($clean));
}

/* -------------------------------------------------------------------------
 * Labels
 * ---------------------------------------------------------------------- */

/**
 * Labels stamped onto every Hetzner resource this module creates.
 */
function hetznercloud_Labels($params)
{
    $labels = [
        'whmcs' => 'true',
        'whmcs_service' => (string) ($params['serviceid'] ?? '0'),
    ];

    if (!empty($params['clientsdetails']['userid'])) {
        $labels['whmcs_client'] = (string) $params['clientsdetails']['userid'];
    }
    if (!empty($params['pid'])) {
        $labels['whmcs_product'] = (string) $params['pid'];
    }

    return $labels;
}

/**
 * Label selector string used to query resources belonging to a service.
 */
function hetznercloud_LabelSelector($serviceId)
{
    return 'whmcs_service==' . (int) $serviceId;
}

/* -------------------------------------------------------------------------
 * Resource cache
 * ---------------------------------------------------------------------- */

/**
 * Remember a Hetzner resource against a WHMCS service.
 */
function hetznercloud_TrackResource($serviceId, $type, $resourceId, $name = '', $meta = [])
{
    if (!$serviceId || !$resourceId) {
        return false;
    }

    try {
        Capsule::table('mod_hetznercloud_resources')->updateOrInsert(
            ['type' => $type, 'resource_id' => (string) $resourceId],
            [
                'serviceid' => (int) $serviceId,
                'name' => mb_substr((string) $name, 0, 190),
                'meta' => json_encode($meta),
                'created_at' => time(),
                'updated_at' => time(),
            ]
        );
        return true;
    } catch (\Exception $e) {
        hetznercloud_LogCall('TrackResource', "{$type}/{$resourceId}", $e->getMessage());
        return false;
    }
}

/**
 * Forget a resource (after it has been deleted at Hetzner).
 */
function hetznercloud_UntrackResource($type, $resourceId)
{
    try {
        Capsule::table('mod_hetznercloud_resources')
            ->where('type', $type)
            ->where('resource_id', (string) $resourceId)
            ->delete();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Forget every resource of a service (used on termination).
 */
function hetznercloud_UntrackService($serviceId)
{
    try {
        Capsule::table('mod_hetznercloud_resources')->where('serviceid', (int) $serviceId)->delete();
        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Cached resources for a service.
 *
 * @return array[] each entry: id, type, resource_id, name, meta
 */
function hetznercloud_TrackedResources($serviceId, $type = null)
{
    try {
        $query = Capsule::table('mod_hetznercloud_resources')->where('serviceid', (int) $serviceId);

        if ($type !== null) {
            $query->where('type', $type);
        }

        $rows = [];
        foreach ($query->orderBy('id')->get() as $row) {
            $rows[] = [
                'id' => $row->id,
                'type' => $row->type,
                'resource_id' => $row->resource_id,
                'name' => $row->name,
                'meta' => json_decode((string) $row->meta, true) ?: [],
            ];
        }

        return $rows;
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Which service owns a given resource (used to authorise client actions).
 */
function hetznercloud_ResourceOwner($type, $resourceId)
{
    try {
        return (int) Capsule::table('mod_hetznercloud_resources')
            ->where('type', $type)
            ->where('resource_id', (string) $resourceId)
            ->value('serviceid');
    } catch (\Exception $e) {
        return 0;
    }
}

/**
 * Replace the cached set for one resource type with what the API just returned.
 *
 * @param array $items Hetzner objects (must contain id / name)
 */
function hetznercloud_SyncResources($serviceId, $type, array $items)
{
    try {
        $keep = [];

        foreach ($items as $item) {
            if (empty($item['id'])) {
                continue;
            }
            $keep[] = (string) $item['id'];
            hetznercloud_TrackResource($serviceId, $type, $item['id'], $item['name'] ?? '', []);
        }

        $query = Capsule::table('mod_hetznercloud_resources')
            ->where('serviceid', (int) $serviceId)
            ->where('type', $type);

        if ($keep) {
            $query->whereNotIn('resource_id', $keep);
        }

        $query->delete();

        return true;
    } catch (\Exception $e) {
        return false;
    }
}
