<?php
/**
 * One-click product setup: custom fields and configurable options.
 *
 * Everything in here is idempotent, so it is safe to run again after adding
 * new locations or images at Hetzner.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';

/* -------------------------------------------------------------------------
 * WHMCS server entry
 * ---------------------------------------------------------------------- */

/**
 * Create or update the WHMCS server entry for Hetzner Cloud from an API token.
 *
 * Saves an admin the trip to Setup > Products/Services > Servers, and refuses
 * to store a token Hetzner will not accept.
 *
 * @return array{success:bool,message:string,serverid:int,groupid:int}
 */
function hetznercloud_EnsureServerEntry($token, $name = 'Hetzner Cloud')
{
    $token = hetznercloud_CleanToken($token);

    if (!hetznercloud_LooksLikeToken($token)) {
        return [
            'success' => false,
            'message' => 'That does not look like a Hetzner Cloud API token. Expected 64 alphanumeric characters.',
            'serverid' => 0,
            'groupid' => 0,
        ];
    }

    $probe = hetznercloud_Request(['serveraccesshash' => $token], 'GET', 'locations', null, ['per_page' => 1]);

    if (!$probe['success']) {
        return [
            'success' => false,
            'message' => 'Hetzner rejected the token: ' . $probe['message'],
            'serverid' => 0,
            'groupid' => 0,
        ];
    }

    try {
        $columns = array_flip(Capsule::schema()->getColumnListing('tblservers'));
        $stored = hetznercloud_Encrypt($token);

        if ($stored === null) {
            return ['success' => false, 'message' => 'WHMCS encrypt() is unavailable, refusing to store the token in plain text.', 'serverid' => 0, 'groupid' => 0];
        }

        $defaults = array_intersect_key([
            'name' => $name,
            'type' => 'hetznercloud',
            'hostname' => 'api.hetzner.cloud',
            'ipaddress' => '',
            'assignedips' => '',
            'monthlycost' => '0.00',
            'noc' => '',
            'statusaddress' => '',
            'active' => 1,
            'disabled' => 0,
            'username' => '',
            'password' => '',
            'accesshash' => $stored,
            'secure' => 'on',
            'port' => '',
            'maxaccounts' => 0,
        ], $columns);

        $existing = Capsule::table('tblservers')->where('type', 'hetznercloud')->orderBy('id')->first();

        if ($existing) {
            Capsule::table('tblservers')->where('id', $existing->id)->update(array_intersect_key([
                'accesshash' => $stored,
                'hostname' => 'api.hetzner.cloud',
                'active' => 1,
                'disabled' => 0,
            ], $columns));

            $serverId = (int) $existing->id;
            $verb = 'updated';
        } else {
            $serverId = (int) Capsule::table('tblservers')->insertGetId($defaults);
            $verb = 'created';
        }

        $groupId = hetznercloud_EnsureServerGroup($serverId, $name);

        hetznercloud_InstallTables();
        hetznercloud_ClearCache();

        return [
            'success' => true,
            'message' => "Token verified. Server entry {$verb} and added to the \"{$name}\" server group.",
            'serverid' => $serverId,
            'groupid' => $groupId,
        ];
    } catch (\Exception $e) {
        hetznercloud_LogCall('EnsureServerEntry', $name, $e->getMessage());

        return ['success' => false, 'message' => $e->getMessage(), 'serverid' => 0, 'groupid' => 0];
    }
}

/**
 * Server group holding the Hetzner server entry, created on demand.
 */
function hetznercloud_EnsureServerGroup($serverId, $name = 'Hetzner Cloud')
{
    $groupId = (int) Capsule::table('tblservergroups')->where('name', $name)->value('id');

    if (!$groupId) {
        $columns = array_flip(Capsule::schema()->getColumnListing('tblservergroups'));
        $groupId = (int) Capsule::table('tblservergroups')->insertGetId(
            array_intersect_key(['name' => $name, 'filltype' => 1], $columns)
        );
    }

    $linked = Capsule::table('tblservergroupsrel')
        ->where('groupid', $groupId)
        ->where('serverid', (int) $serverId)
        ->exists();

    if (!$linked) {
        Capsule::table('tblservergroupsrel')->insert(['groupid' => $groupId, 'serverid' => (int) $serverId]);
    }

    return $groupId;
}

/**
 * The server group holding this module's server entry.
 *
 * Prefers a group actually linked to a hetznercloud server so a renamed group
 * still resolves, and falls back to the conventional name.
 */
function hetznercloud_ServerGroupId()
{
    try {
        $linked = (int) Capsule::table('tblservergroupsrel')
            ->join('tblservers', 'tblservers.id', '=', 'tblservergroupsrel.serverid')
            ->where('tblservers.type', 'hetznercloud')
            ->orderBy('tblservergroupsrel.groupid')
            ->value('tblservergroupsrel.groupid');

        if ($linked) {
            return $linked;
        }

        return (int) Capsule::table('tblservergroups')->where('name', 'Hetzner Cloud')->value('id');
    } catch (\Exception $e) {
        return 0;
    }
}

/**
 * Point a product at the Hetzner server group when it has none yet.
 */
function hetznercloud_AssignServerGroup($productId, $groupId = 0)
{
    try {
        if (!$groupId) {
            $groupId = hetznercloud_ServerGroupId();
        }

        if (!$groupId) {
            return false;
        }

        $current = (int) Capsule::table('tblproducts')->where('id', (int) $productId)->value('servergroup');

        if ($current) {
            return false;
        }

        Capsule::table('tblproducts')->where('id', (int) $productId)->update(['servergroup' => $groupId]);

        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Custom fields the module relies on.
 */
function hetznercloud_RequiredCustomFields()
{
    return [
        [
            'fieldname' => 'serverID',
            'fieldtype' => 'text',
            'description' => 'Hetzner Cloud server ID (filled in automatically)',
            'adminonly' => 'on',
            'required' => '',
            'showorder' => '',
        ],
        [
            'fieldname' => 'ssh_key',
            'fieldtype' => 'textarea',
            'description' => 'Paste an OpenSSH public key to have it deployed on first boot',
            'adminonly' => '',
            'required' => '',
            'showorder' => 'on',
        ],
    ];
}

/**
 * Create any missing custom field on a product.
 *
 * @return array Names of the fields that were created
 */
function hetznercloud_EnsureCustomFields($productId)
{
    $productId = (int) $productId;
    $created = [];

    if (!$productId) {
        return $created;
    }

    try {
        $existing = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $productId)
            ->pluck('fieldname')
            ->all();

        $existingNames = array_map(function ($name) {
            return strtolower(trim(explode('|', $name)[0]));
        }, $existing);

        $sortOrder = (int) Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', $productId)
            ->max('sortorder');

        foreach (hetznercloud_RequiredCustomFields() as $field) {
            if (in_array(strtolower($field['fieldname']), $existingNames, true)) {
                continue;
            }

            Capsule::table('tblcustomfields')->insert([
                'type' => 'product',
                'relid' => $productId,
                'fieldname' => $field['fieldname'],
                'fieldtype' => $field['fieldtype'],
                'description' => $field['description'],
                'fieldoptions' => '',
                'regexpr' => '',
                'adminonly' => $field['adminonly'],
                'required' => $field['required'],
                'showorder' => $field['showorder'],
                'showinvoice' => '',
                'sortorder' => ++$sortOrder,
            ]);

            $created[] = $field['fieldname'];
        }
    } catch (\Exception $e) {
        hetznercloud_LogCall('EnsureCustomFields', $productId, $e->getMessage());
    }

    return $created;
}

/**
 * The configurable options this module understands.
 *
 * type: 1 = dropdown, 3 = yes/no, 4 = quantity
 */
function hetznercloud_ConfigurableOptionDefinitions($params = [], $includeServerType = true)
{
    $locations = [];
    foreach (hetznercloud_Locations($params) as $location) {
        $label = trim(($location['city'] ?: $location['description']) . ', ' . $location['country']);
        $locations[] = $location['name'] . '|' . $label . ' (' . strtoupper($location['name']) . ')';
    }

    $images = [];
    foreach (hetznercloud_Images($params) as $image) {
        if ($image['type'] !== 'system') {
            continue;
        }
        $images[] = $image['name'] . '|' . $image['description'];
    }

    $types = [];
    foreach (hetznercloud_ServerTypes($params) as $type) {
        $types[] = $type['name'] . '|' . strtoupper($type['name']) . ' - '
            . $type['cores'] . ' vCPU, ' . $type['memory'] . ' GB RAM, ' . $type['disk'] . ' GB disk';
    }

    $definitions = [
        ['name' => 'Location', 'type' => 1, 'options' => $locations],
        ['name' => 'OS Image', 'type' => 1, 'options' => $images],
    ];

    // A product that already fixes its plan must not also offer a plan picker:
    // every entry would sit at the same price and the customer could upgrade
    // from the cheapest plan to the largest one for free in the cart.
    if ($includeServerType) {
        $definitions[] = ['name' => 'Server Type', 'type' => 1, 'options' => $types];
    }

    return array_merge($definitions, [
        ['name' => 'Disk Format', 'type' => 1, 'options' => ['ext4|Ext4', 'xfs|XFS']],
        ['name' => 'IP Protocol', 'type' => 1, 'options' => ['ipv4|IPv4', 'ipv6|IPv6', 'both|IPv4 + IPv6']],
        ['name' => 'Floating IPs', 'type' => 4, 'min' => 0, 'max' => 10],
        ['name' => 'Enable Backup', 'type' => 3],
        ['name' => 'Snapshot Limit', 'type' => 4, 'min' => 0, 'max' => 25],
        ['name' => 'Firewall Limit', 'type' => 4, 'min' => 0, 'max' => 5],
        ['name' => 'Inbound Firewall Limit', 'type' => 4, 'min' => 0, 'max' => 50],
        ['name' => 'Outbound Firewall Limit', 'type' => 4, 'min' => 0, 'max' => 50],
        ['name' => 'Network Limit', 'type' => 4, 'min' => 0, 'max' => 5],
    ]);
}

/**
 * Monthly gross price Hetzner charges for one floating IP.
 */
function hetznercloud_FloatingIpCost($params = [])
{
    $pricing = hetznercloud_Pricing($params);

    foreach (['floating_ips', 'primary_ips'] as $key) {
        foreach ((array) ($pricing[$key] ?? []) as $entry) {
            if (($entry['type'] ?? 'ipv4') !== 'ipv4') {
                continue;
            }
            foreach ((array) ($entry['prices'] ?? []) as $price) {
                $gross = (float) ($price['price_monthly']['gross'] ?? 0);
                if ($gross > 0) {
                    return $gross;
                }
            }
        }
    }

    // Legacy shape, then a sane default if the price list is unavailable.
    $legacy = (float) ($pricing['floating_ip']['price_monthly']['gross'] ?? 0);

    return $legacy > 0 ? $legacy : 0.60;
}

/**
 * Set the recurring price of one configurable sub-option, in every currency.
 *
 * @param bool $onlyIfUnset leave a price an administrator has already set alone,
 *                          so re-running setup never resets somebody's margin
 */
function hetznercloud_SetSubOptionPrice($subId, $monthly, $onlyIfUnset = true)
{
    if ($onlyIfUnset) {
        $existing = (float) Capsule::table('tblpricing')
            ->where('type', 'configoptions')
            ->where('relid', (int) $subId)
            ->max('monthly');

        if ($existing > 0) {
            return false;
        }
    }

    $currencies = Capsule::table('tblcurrencies')->get(['id', 'rate', 'default']);

    if (!count($currencies)) {
        $currencies = [(object) ['id' => 1, 'rate' => 1, 'default' => 1]];
    }

    foreach ($currencies as $currency) {
        $isDefault = !empty($currency->default);
        $rate = (float) ($currency->rate ?: 1);
        $price = round($monthly * ($isDefault ? 1 : ($rate ?: 1)), 2);

        Capsule::table('tblpricing')->updateOrInsert(
            ['type' => 'configoptions', 'currency' => (int) $currency->id, 'relid' => (int) $subId],
            ['monthly' => $price]
        );
    }

    return true;
}

/**
 * Give every entry of an offered Server Type picker the price of its own plan,
 * so a bigger plan actually costs more. Never overwrites a set price.
 */
function hetznercloud_PriceServerTypeOptions($groupId, $params = [])
{
    $optionId = (int) Capsule::table('tblproductconfigoptions')
        ->where('gid', $groupId)
        ->where('optionname', 'Server Type')
        ->value('id');

    if (!$optionId) {
        return 0;
    }

    $plans = [];
    foreach (hetznercloud_ServerTypes($params) as $type) {
        $plans[$type['name']] = (float) $type['price'];
    }

    $priced = 0;

    foreach (Capsule::table('tblproductconfigoptionssub')->where('configid', $optionId)->get(['id', 'optionname']) as $sub) {
        $value = trim(explode('|', (string) $sub->optionname)[0]);

        if (!isset($plans[$value]) || $plans[$value] <= 0) {
            continue;
        }

        if (hetznercloud_SetSubOptionPrice($sub->id, $plans[$value])) {
            $priced++;
        }
    }

    return $priced;
}

/**
 * Price the options that cost real money at Hetzner, so the cart is honest the
 * moment a product is generated.
 *
 * @param array $prices monthly => the product's sale price, markup => percent
 */
function hetznercloud_PriceGeneratedOptions($groupId, array $prices, $params = [])
{
    $monthly = (float) ($prices['monthly'] ?? 0);
    $markup = 1 + (((float) ($prices['markup'] ?? 0)) / 100);

    $priced = [];

    // Backups add 20% to the server price at Hetzner.
    if ($monthly > 0) {
        $priced['Enable Backup'] = round($monthly * 0.20, 2);
    }

    // Floating IPs are billed per address, per month.
    $priced['Floating IPs'] = round(hetznercloud_FloatingIpCost($params) * $markup, 2);

    foreach ($priced as $optionName => $price) {
        if ($price <= 0) {
            continue;
        }

        $optionId = (int) Capsule::table('tblproductconfigoptions')
            ->where('gid', $groupId)
            ->where('optionname', $optionName)
            ->value('id');

        if (!$optionId) {
            continue;
        }

        foreach (Capsule::table('tblproductconfigoptionssub')->where('configid', $optionId)->get(['id']) as $sub) {
            hetznercloud_SetSubOptionPrice($sub->id, $price);
        }
    }

    return $priced;
}

/**
 * Create (or refresh) the configurable option group for a product.
 *
 * @return array{success:bool,message:string,created:int,updated:int}
 */
function hetznercloud_GenerateConfigOptions($productId, $params = [], array $prices = [])
{
    $productId = (int) $productId;

    if (!$productId) {
        return ['success' => false, 'message' => 'No product id supplied.', 'created' => 0, 'updated' => 0];
    }

    try {
        $product = Capsule::table('tblproducts')->where('id', $productId)->first(['name', 'configoption2']);
        $productName = (string) ($product->name ?? '');
        $groupName = 'Hetzner Cloud - ' . ($productName ?: ('Product #' . $productId));

        // The plan is fixed by the product itself whenever the module setting
        // names one, which every generated product does.
        $fixedType = trim((string) ($product->configoption2 ?? ''));
        $includeServerType = $fixedType === '';

        $groupId = (int) Capsule::table('tblproductconfiggroups')->where('name', $groupName)->value('id');

        if (!$groupId) {
            $groupId = (int) Capsule::table('tblproductconfiggroups')->insertGetId([
                'name' => $groupName,
                'description' => 'Auto-generated by the Hetzner Cloud module',
            ]);
        }

        $linked = Capsule::table('tblproductconfiglinks')
            ->where('gid', $groupId)
            ->where('pid', $productId)
            ->exists();

        if (!$linked) {
            Capsule::table('tblproductconfiglinks')->insert(['gid' => $groupId, 'pid' => $productId]);
        }

        $created = 0;
        $updated = 0;
        $order = 0;

        foreach (hetznercloud_ConfigurableOptionDefinitions($params, $includeServerType) as $definition) {
            $order++;

            if ($definition['type'] === 1 && empty($definition['options'])) {
                continue; // API unavailable - do not create an empty dropdown
            }

            $optionId = (int) Capsule::table('tblproductconfigoptions')
                ->where('gid', $groupId)
                ->where('optionname', $definition['name'])
                ->value('id');

            if (!$optionId) {
                $optionId = (int) Capsule::table('tblproductconfigoptions')->insertGetId([
                    'gid' => $groupId,
                    'optionname' => $definition['name'],
                    'optiontype' => $definition['type'],
                    'qtyminimum' => $definition['min'] ?? 0,
                    'qtymaximum' => $definition['max'] ?? 0,
                    'order' => $order,
                    'hidden' => '',
                ]);
                $created++;
            } else {
                Capsule::table('tblproductconfigoptions')->where('id', $optionId)->update([
                    'optiontype' => $definition['type'],
                    'qtyminimum' => $definition['min'] ?? 0,
                    'qtymaximum' => $definition['max'] ?? 0,
                ]);
                $updated++;
            }

            $subOptions = $definition['type'] === 1
                ? $definition['options']
                : [$definition['name']];

            hetznercloud_SyncSubOptions($optionId, $subOptions);
        }

        $notes = [];

        // A group built before the plan was fixed may still carry a Server Type
        // picker. Hide rather than delete: existing orders keep their values,
        // but nobody can switch plan for free on a new order.
        if (!$includeServerType) {
            $stale = (int) Capsule::table('tblproductconfigoptions')
                ->where('gid', $groupId)
                ->where('optionname', 'Server Type')
                ->where('hidden', '')
                ->value('id');

            if ($stale) {
                Capsule::table('tblproductconfigoptions')->where('id', $stale)->update(['hidden' => 'on']);
                $notes[] = 'the Server Type picker was hidden because this product already fixes its plan';
            }
        } else {
            // Plan picker is offered: make each plan carry its own price.
            $pricedPlans = hetznercloud_PriceServerTypeOptions($groupId, $params);

            if ($pricedPlans) {
                $notes[] = $pricedPlans . ' server types priced from the Hetzner list (review your margin)';
            }
        }

        if ($prices) {
            $applied = hetznercloud_PriceGeneratedOptions($groupId, $prices, $params);

            foreach ($applied as $optionName => $price) {
                $notes[] = strtolower($optionName) . ' priced at ' . number_format($price, 2);
            }
        }

        $message = "Configurable option group \"{$groupName}\" is ready ({$created} added, {$updated} refreshed)";
        $message .= $notes ? ' — ' . implode(', ', $notes) . '.' : '.';

        return [
            'success' => true,
            'message' => $message,
            'created' => $created,
            'updated' => $updated,
            'gid' => $groupId,
        ];
    } catch (\Exception $e) {
        hetznercloud_LogCall('GenerateConfigOptions', $productId, $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage(), 'created' => 0, 'updated' => 0];
    }
}

/**
 * Add missing sub-options (and their zero-price rows) without touching prices
 * an administrator has already set.
 */
function hetznercloud_SyncSubOptions($optionId, array $subOptions)
{
    $existing = Capsule::table('tblproductconfigoptionssub')
        ->where('configid', $optionId)
        ->pluck('optionname')
        ->all();

    $sortOrder = (int) Capsule::table('tblproductconfigoptionssub')
        ->where('configid', $optionId)
        ->max('sortorder');

    foreach ($subOptions as $subOption) {
        if (in_array($subOption, $existing, true)) {
            continue;
        }

        $subId = (int) Capsule::table('tblproductconfigoptionssub')->insertGetId([
            'configid' => $optionId,
            'optionname' => $subOption,
            'sortorder' => ++$sortOrder,
            'hidden' => '',
        ]);

        hetznercloud_CreateZeroPricing($subId);
    }
}

/**
 * WHMCS needs a pricing row per currency for every configurable sub-option.
 */
function hetznercloud_CreateZeroPricing($relId)
{
    $currencies = Capsule::table('tblcurrencies')->pluck('id')->all() ?: [1];

    foreach ($currencies as $currencyId) {
        $exists = Capsule::table('tblpricing')
            ->where('type', 'configoptions')
            ->where('currency', $currencyId)
            ->where('relid', $relId)
            ->exists();

        if ($exists) {
            continue;
        }

        Capsule::table('tblpricing')->insert([
            'type' => 'configoptions',
            'currency' => $currencyId,
            'relid' => $relId,
            'msetupfee' => 0,
            'qsetupfee' => 0,
            'ssetupfee' => 0,
            'asetupfee' => 0,
            'bsetupfee' => 0,
            'tsetupfee' => 0,
            'monthly' => 0,
            'quarterly' => 0,
            'semiannually' => 0,
            'annually' => 0,
            'biennially' => 0,
            'triennially' => 0,
        ]);
    }
}

/**
 * Full product setup: tables, custom fields, configurable options.
 */
function hetznercloud_SetupProduct($productId, $params = [], array $prices = [])
{
    hetznercloud_InstallTables();

    $fields = hetznercloud_EnsureCustomFields($productId);
    $grouped = hetznercloud_AssignServerGroup($productId);
    $options = hetznercloud_GenerateConfigOptions($productId, $params, $prices);

    return [
        'success' => $options['success'],
        'fields' => $fields,
        'message' => $options['message'] . ($grouped ? ' Server group assigned.' : ''),
    ];
}

/* -------------------------------------------------------------------------
 * Product creation from Hetzner server types
 * ---------------------------------------------------------------------- */

/**
 * A slug that is not taken yet on the given table.
 */
function hetznercloud_UniqueSlug($table, $base)
{
    try {
        if (!Capsule::schema()->hasColumn($table, 'slug')) {
            return null;
        }
    } catch (\Exception $e) {
        return null;
    }

    $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($base))), '-');
    $slug = $slug !== '' ? $slug : 'hetzner';

    $candidate = $slug;
    $suffix = 1;

    while (Capsule::table($table)->where('slug', $candidate)->exists()) {
        $candidate = $slug . '-' . (++$suffix);
    }

    return $candidate;
}

/**
 * Find or create a WHMCS product group.
 */
function hetznercloud_EnsureProductGroup($name)
{
    $name = trim($name) !== '' ? trim($name) : 'Hetzner Cloud VPS';

    $gid = (int) Capsule::table('tblproductgroups')->where('name', $name)->value('id');

    if ($gid) {
        return $gid;
    }

    $columns = array_flip(Capsule::schema()->getColumnListing('tblproductgroups'));

    $row = array_intersect_key([
        'name' => $name,
        'slug' => hetznercloud_UniqueSlug('tblproductgroups', $name),
        'headline' => $name,
        'tagline' => 'Cloud servers powered by Hetzner',
        'orderfrmtpl' => '',
        'disabledgateways' => '',
        'hidden' => 0,
        'order' => 0,
    ], $columns);

    return (int) Capsule::table('tblproductgroups')->insertGetId($row);
}

/**
 * Write the recurring price of a product. Only monthly is offered; every other
 * term is set to -1, which is how WHMCS marks a term as unavailable.
 */
function hetznercloud_SetProductPricing($productId, $monthly, $setupFee = 0)
{
    $currencies = Capsule::table('tblcurrencies')->get(['id', 'rate', 'default']);

    if (!count($currencies)) {
        $currencies = [(object) ['id' => 1, 'rate' => 1, 'default' => 1]];
    }

    foreach ($currencies as $currency) {
        $rate = (float) ($currency->rate ?: 1);
        $isDefault = !empty($currency->default);
        $price = round($monthly * ($isDefault ? 1 : ($rate ?: 1)), 2);
        $setup = round($setupFee * ($isDefault ? 1 : ($rate ?: 1)), 2);

        Capsule::table('tblpricing')->updateOrInsert(
            ['type' => 'product', 'currency' => (int) $currency->id, 'relid' => (int) $productId],
            [
                'msetupfee' => $setup,
                'qsetupfee' => -1,
                'ssetupfee' => -1,
                'asetupfee' => -1,
                'bsetupfee' => -1,
                'tsetupfee' => -1,
                'monthly' => $price,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ]
        );
    }
}

/**
 * Default product settings written into configoption1..24.
 *
 * Values for dropdown settings must be the exact "value|Label" string that
 * hetznercloud_ConfigOptions() offers, otherwise WHMCS shows nothing selected.
 */
function hetznercloud_ProductConfigOptions($type, array $choices)
{
    $typePairs = hetznercloud_TypePairs();
    $locationPairs = hetznercloud_LocationPairs();
    $imagePairs = hetznercloud_ImagePairs();

    $location = $choices['location'] ?? '';
    $image = $choices['image'] ?? '';

    if (!isset($locationPairs[$location])) {
        $location = (string) array_key_first($locationPairs ?: ['fsn1' => '']);
    }
    if (!isset($imagePairs[$image])) {
        $image = (string) array_key_first($imagePairs ?: ['ubuntu-24.04' => '']);
    }

    return [
        'configoption1' => $choices['prefix'] ?? 'vps',
        'configoption2' => hetznercloud_DropdownValue($type['name'], $typePairs[$type['name']] ?? $type['name']),
        'configoption3' => hetznercloud_DropdownValue($location, $locationPairs[$location] ?? $location),
        'configoption4' => hetznercloud_DropdownValue($image, $imagePairs[$image] ?? $image),
        'configoption5' => '',
        'configoption6' => '',
        'configoption7' => '0',
        'configoption8' => hetznercloud_DropdownValue('ext4', 'Ext4'),
        'configoption9' => '0',
        'configoption10' => hetznercloud_DropdownValue('ipv4', 'IPv4'),
        'configoption11' => '0.00',
        'configoption12' => (string) ($choices['snapshot_limit'] ?? 3),
        'configoption13' => (string) ($choices['firewall_limit'] ?? 2),
        'configoption14' => '10',
        'configoption15' => '10',
        'configoption16' => (string) ($choices['network_limit'] ?? 1),
        'configoption17' => 'No',
    ];
}

/**
 * Create a WHMCS product from a Hetzner server type.
 *
 * Idempotent: a product already carrying this server type in configoption2 is
 * left alone, so "create all" can be pressed twice without making duplicates.
 *
 * @param string $typeName e.g. "cx22"
 * @param array  $choices  group / gid / location / image / markup / prefix / autosetup / setupfee
 *
 * @return array{success:bool,message:string,pid:int}
 */
function hetznercloud_CreateProductFromType($typeName, array $choices)
{
    $type = null;

    foreach (hetznercloud_ServerTypes() as $candidate) {
        if ($candidate['name'] === $typeName) {
            $type = $candidate;
            break;
        }
    }

    if (!$type) {
        return ['success' => false, 'message' => "Unknown server type \"{$typeName}\".", 'pid' => 0];
    }

    try {
        $configOptions = hetznercloud_ProductConfigOptions($type, $choices);

        $existing = Capsule::table('tblproducts')
            ->where('servertype', 'hetznercloud')
            ->where('configoption2', $configOptions['configoption2'])
            ->where('configoption3', $configOptions['configoption3'])
            ->first(['id', 'name']);

        if ($existing) {
            return [
                'success' => true,
                'message' => strtoupper($typeName) . ': already exists as "' . $existing->name . '" - skipped.',
                'pid' => (int) $existing->id,
            ];
        }

        $gid = (int) ($choices['gid'] ?? 0) ?: hetznercloud_EnsureProductGroup($choices['group'] ?? '');
        $serverGroup = hetznercloud_ServerGroupId();

        $name = strtoupper($type['name']) . ' - ' . $type['cores'] . ' vCPU / '
            . $type['memory'] . ' GB RAM / ' . $type['disk'] . ' GB';

        $description = $type['cores'] . ' dedicated ' . ($type['cpu_type'] === 'dedicated' ? 'vCPU cores' : 'vCPU')
            . ', ' . $type['memory'] . ' GB RAM, ' . $type['disk'] . ' GB NVMe SSD, '
            . strtoupper($type['architecture']) . ', 20 TB traffic included.';

        $columns = array_flip(Capsule::schema()->getColumnListing('tblproducts'));

        $row = array_intersect_key(array_merge([
            'type' => 'server',
            'gid' => $gid,
            'name' => $name,
            'slug' => hetznercloud_UniqueSlug('tblproducts', $name),
            'description' => $description,
            'hidden' => 0,
            'showdomainoptions' => 0,
            'tax' => 1,
            'proratabilling' => 0,
            'paytype' => 'recurring',
            'allowqty' => 0,
            'subdomain' => '',
            'autosetup' => $choices['autosetup'] ?? 'payment',
            'servertype' => 'hetznercloud',
            'servergroup' => $serverGroup,
            'stockcontrol' => 0,
            'qty' => 0,
            'retired' => 0,
        ], $configOptions), $columns);

        $pid = (int) Capsule::table('tblproducts')->insertGetId($row);

        if (!$pid) {
            return ['success' => false, 'message' => strtoupper($typeName) . ': product insert failed.', 'pid' => 0];
        }

        $markup = (float) ($choices['markup'] ?? 0);
        $monthly = round($type['price'] * (1 + ($markup / 100)), 2);

        hetznercloud_SetProductPricing($pid, $monthly, (float) ($choices['setupfee'] ?? 0));

        // Hand the sale price down so backups and floating IPs get real prices
        // instead of being free upgrades in the cart.
        $setup = hetznercloud_SetupProduct($pid, [], ['monthly' => $monthly, 'markup' => $markup]);

        return [
            'success' => true,
            'message' => strtoupper($typeName) . ': created "' . $name . '" at ' . number_format($monthly, 2)
                . ' / month. ' . $setup['message'],
            'pid' => $pid,
        ];
    } catch (\Exception $e) {
        hetznercloud_LogCall('CreateProductFromType', $typeName, $e->getMessage());

        return ['success' => false, 'message' => strtoupper($typeName) . ': ' . $e->getMessage(), 'pid' => 0];
    }
}

/**
 * Product groups for the picker.
 */
function hetznercloud_ProductGroups()
{
    try {
        return Capsule::table('tblproductgroups')->orderBy('name')->get(['id', 'name'])->all();
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Every product using this module.
 */
function hetznercloud_ModuleProducts()
{
    try {
        return Capsule::table('tblproducts')
            ->where('servertype', 'hetznercloud')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();
    } catch (\Exception $e) {
        return [];
    }
}
