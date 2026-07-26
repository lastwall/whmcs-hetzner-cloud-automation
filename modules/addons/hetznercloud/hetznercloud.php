<?php
/**
 * Hetzner Cloud - WHMCS addon module.
 *
 * Central settings and one-click setup for the provisioning module in
 * modules/servers/hetznercloud. Activating this addon creates the module
 * tables; the settings below are read by the provisioning module at runtime.
 *
 * The page reuses the client panel's stylesheet, so the admin side looks like
 * the customer side and there is no second design system to maintain.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once ROOTDIR . '/modules/servers/hetznercloud/version.php';
require_once ROOTDIR . '/modules/servers/hetznercloud/api.php';
require_once ROOTDIR . '/modules/servers/hetznercloud/storage.php';
require_once ROOTDIR . '/modules/servers/hetznercloud/lang.php';
require_once ROOTDIR . '/modules/servers/hetznercloud/setup.php';
require_once ROOTDIR . '/modules/servers/hetznercloud/panel.php';

function hetznercloud_config()
{
    $languages = array_keys(hetznercloud_AvailableLanguages());
    sort($languages);

    return [
        'name' => 'Hetzner Cloud',
        'description' => 'Settings, product setup and diagnostics for the Hetzner Cloud provisioning module.',
        'author' => HETZNERCLOUD_VERSION_AUTHOR,
        'language' => 'english',
        'version' => HETZNERCLOUD_VERSION,
        'fields' => [
            'default_language' => [
                'FriendlyName' => 'Default panel language',
                'Type' => 'dropdown',
                'Options' => implode(',', $languages),
                'Default' => 'english',
                'Description' => 'Used when the client has no language of their own.',
            ],
            'accent' => [
                'FriendlyName' => 'Accent colour',
                'Type' => 'text',
                'Size' => '10',
                'Default' => '#d50c2d',
                'Description' => 'Hex colour for buttons and highlights in the client panel.',
            ],
            'auto_setup' => [
                'FriendlyName' => 'Auto-create custom fields',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Create the serverID / ssh_key custom fields automatically when a product needs them.',
            ],
            'terminate_cleanup' => [
                'FriendlyName' => 'Clean up on termination',
                'Type' => 'yesno',
                'Default' => 'on',
                'Description' => 'Delete floating IPs, volumes, snapshots, firewalls, networks and SSH keys with the server.',
            ],
            'poll_interval' => [
                'FriendlyName' => 'Status refresh (seconds)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '15',
                'Description' => 'How often the client panel re-checks the server status. 0 disables polling.',
            ],
            'api_timeout' => [
                'FriendlyName' => 'API timeout (seconds)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '20',
                'Description' => 'Maximum time for one Hetzner API call. Always capped below PHP\'s '
                    . 'max_execution_time so a slow response cannot blank the page.',
            ],
        ],
    ];
}

function hetznercloud_activate()
{
    if (!hetznercloud_InstallTables()) {
        return ['status' => 'error', 'description' => 'Could not create the module tables. Check the WHMCS activity log.'];
    }

    return [
        'status' => 'success',
        'description' => 'Hetzner Cloud is ready. Open the addon to connect your API token.',
    ];
}

function hetznercloud_deactivate()
{
    // Resource cache is kept on purpose: reactivating must not orphan anything.
    return [
        'status' => 'success',
        'description' => 'Deactivated. Module tables were kept so nothing is orphaned.',
    ];
}

function hetznercloud_upgrade($vars)
{
    hetznercloud_InstallTables();
}

/* =========================================================================
 * Page
 * ====================================================================== */

/**
 * The tabs, in order.
 */
function hetznercloud_AddonTabs()
{
    return [
        'status' => 'Status',
        'connection' => 'Connection',
        'products' => 'Products',
        'create' => 'Create products',
    ];
}

function hetznercloud_AddonUrl($tab, $action = '')
{
    $url = 'addonmodules.php?module=hetznercloud&tab=' . urlencode($tab);

    return $action ? $url . '&hcaction=' . urlencode($action) : $url;
}

function hetznercloud_output($vars)
{
    $tabs = hetznercloud_AddonTabs();
    $tab = (string) ($_GET['tab'] ?? 'status');

    if (!isset($tabs[$tab])) {
        $tab = 'status';
    }

    $notices = hetznercloud_AddonActions($tab);

    $accent = (string) hetznercloud_Setting('accent', '#d50c2d');
    if (!preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
        $accent = '#d50c2d';
    }

    $token = hetznercloud_CsrfToken();

    echo '<link href="' . hetznercloud_Asset('../modules/servers/hetznercloud/', 'style.css') . '" rel="stylesheet">';
    echo '<style>
        .hc-admin { max-width: 1180px; }
        .hc-admin h1 { margin: 0 0 2px; font-size: 22px; font-weight: 650; letter-spacing: -.02em; }
        .hc-admin h3 { margin-top: 4px; }
        .hc-admin .hc-tabs { margin-top: 14px; }
        .hc-admin .hc-tab { display: inline-block; text-decoration: none; }
        .hc-admin .hc-tab:hover { text-decoration: none; }
        .hc-admin .hc-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .hc-admin .hc-card + .hc-card, .hc-admin details.hc-card { margin-top: 14px; }
        .hc-admin .hc-table td, .hc-admin .hc-table th { vertical-align: middle; }
        .hc-admin details.hc-card > summary { cursor: pointer; font-weight: 650; list-style: none; }
        .hc-admin details.hc-card > summary::-webkit-details-marker { display: none; }
        .hc-admin details.hc-card > summary::before { content: "\\25B8"; display: inline-block; width: 14px; color: var(--hc-text-soft); transition: transform .15s; }
        .hc-admin details[open].hc-card > summary::before { transform: rotate(90deg); }
        .hc-admin .hc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; }
        .hc-admin .hc-inline { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .hc-admin .hc-field { min-width: 150px; }
        .hc-admin label.hc-check { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 5px; font-size: 13px; }
        .hc-admin .hc-billable { color: var(--hc-accent); font-size: 11px; font-weight: 650; text-transform: uppercase; }
    </style>';

    echo '<div class="hc hc-admin" data-theme="light" style="--hc-accent:' . hetznercloud_E($accent) . '">';

    echo '<h1>Hetzner Cloud <span style="color:var(--hc-text-soft);font-weight:400">'
        . hetznercloud_E(HETZNERCLOUD_VERSION) . '</span></h1>';
    echo '<p class="hc-muted">Provisioning module control centre. The settings above this page apply to every product.</p>';

    foreach ($notices as [$type, $message]) {
        echo '<div class="hc-note hc-note--' . ($type === 'success' ? 'ok' : 'bad') . '" style="margin-top:10px">'
            . hetznercloud_E($message) . '</div>';
    }

    // Tab bar.
    echo '<nav class="hc-tabs">';
    foreach ($tabs as $key => $label) {
        echo '<a class="hc-tab' . ($key === $tab ? ' is-active' : '') . '" href="'
            . hetznercloud_E(hetznercloud_AddonUrl($key)) . '">' . hetznercloud_E($label) . '</a>';
    }
    echo '</nav>';

    $renderer = 'hetznercloud_AddonTab' . ucfirst($tab);
    $renderer($token);

    echo '</div>';
}

/**
 * Run whatever the form posted, and return notices.
 */
function hetznercloud_AddonActions($tab)
{
    $notices = [];
    $action = (string) ($_POST['hcaction'] ?? $_GET['hcaction'] ?? '');

    if ($action === '') {
        return $notices;
    }

    // Every action here changes state - one of them replaces the API token the
    // whole installation provisions with - so all of them are POST-only and
    // CSRF checked. Without this an attacker page could drive a logged-in
    // administrator's browser into repointing provisioning at another account.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return [['error', 'That action must be submitted from this page.']];
    }

    if (!hetznercloud_CheckCsrf($_POST['token'] ?? '')) {
        return [['error', 'Security token mismatch — reload this page and try again.']];
    }

    if (!hetznercloud_CurrentAdminId()) {
        return [['error', 'Administrator login required.']];
    }

    switch ($action) {
        case 'connect':
            $result = hetznercloud_EnsureServerEntry(
                $_POST['api_token'] ?? '',
                trim($_POST['server_name'] ?? '') ?: 'Hetzner Cloud'
            );
            $notices[] = [$result['success'] ? 'success' : 'error', $result['message']];
            break;

        case 'setup':
            if (!empty($_POST['pid'])) {
                $result = hetznercloud_SetupProduct((int) $_POST['pid']);
                $notices[] = [$result['success'] ? 'success' : 'error', $result['message']
                    . ($result['fields'] ? ' Custom fields created: ' . implode(', ', $result['fields']) . '.' : '')];
            }
            break;

        case 'setup_all':
            foreach (hetznercloud_ModuleProducts() as $product) {
                $result = hetznercloud_SetupProduct((int) $product->id);
                $notices[] = [$result['success'] ? 'success' : 'error', $product->name . ': ' . $result['message']];
            }
            break;

        case 'save_features':
            if (!empty($_POST['pid'])) {
                $saved = hetznercloud_SaveProductOverrides(
                    (int) $_POST['pid'],
                    (array) ($_POST['features'] ?? []),
                    (array) ($_POST['limits'] ?? [])
                );
                $notices[] = [$saved ? 'success' : 'error',
                    $saved ? 'Client area features saved.' : 'Could not save the feature settings.'];
            }
            break;

        case 'clear_cache':
            $count = hetznercloud_ClearCache();
            $notices[] = ['success', "Cleared {$count} cached API responses."];
            break;

        case 'create_products':
            $choices = [
                'group' => trim($_POST['group_name'] ?? ''),
                'gid' => (int) ($_POST['gid'] ?? 0),
                'location' => $_POST['location'] ?? '',
                'image' => $_POST['image'] ?? '',
                'markup' => (float) ($_POST['markup'] ?? 0),
                'setupfee' => (float) ($_POST['setupfee'] ?? 0),
                'prefix' => trim($_POST['prefix'] ?? '') ?: 'vps',
                'autosetup' => $_POST['autosetup'] ?? 'payment',
            ];

            $selected = !empty($_POST['single'])
                ? [(string) $_POST['single']]
                : array_values((array) ($_POST['types'] ?? []));

            if (!$selected) {
                $notices[] = ['error', 'Select at least one server type.'];
            }

            foreach ($selected as $typeName) {
                $result = hetznercloud_CreateProductFromType((string) $typeName, $choices);
                $notices[] = [$result['success'] ? 'success' : 'error', $result['message']];
            }
            break;
    }

    return $notices;
}

/* -------------------------------------------------------------------------
 * Tab: status
 * ---------------------------------------------------------------------- */

function hetznercloud_AddonTabStatus($token)
{
    $connection = hetznercloud_Request([], 'GET', 'locations', null, ['per_page' => 1]);
    $products = hetznercloud_ModuleProducts();
    $tablesOk = false;
    $counts = [];

    try {
        $tablesOk = Capsule::schema()->hasTable('mod_hetznercloud_resources');

        if ($tablesOk) {
            foreach (Capsule::table('mod_hetznercloud_resources')
                ->select('type')->selectRaw('COUNT(*) as total')->groupBy('type')->get() as $row) {
                $counts[$row->type] = $row->total;
            }
        }
    } catch (\Exception $e) {
        $tablesOk = false;
    }

    $configured = 0;
    foreach ($products as $product) {
        if (hetznercloud_CustomFieldIdLookup('serverID', $product->id)) {
            $configured++;
        }
    }

    echo '<div class="hc-stats" style="margin-bottom:16px">';
    echo hetznercloud_StatCard('API connection', $connection['success'] ? 'Connected' : 'Failed',
        $connection['success'] ? 'ok' : 'bad');
    echo hetznercloud_StatCard('Database', $tablesOk ? 'Tables ready' : 'Missing', $tablesOk ? 'ok' : 'bad');
    echo hetznercloud_StatCard('Products using the module', (string) count($products));
    echo hetznercloud_StatCard('Tracked resources', (string) array_sum($counts));
    echo '</div>';

    if (!$connection['success']) {
        echo '<div class="hc-note hc-note--bad">' . hetznercloud_E($connection['message']) . '</div>';
    }

    /* Next steps ------------------------------------------------------- */

    $steps = [
        ['Connect an API token', $connection['success'], hetznercloud_AddonUrl('connection'), 'Connection'],
        ['Create module tables', $tablesOk, '', ''],
        ['Add at least one product', count($products) > 0, hetznercloud_AddonUrl('create'), 'Create products'],
        ['Run setup on every product', $products && $configured === count($products),
            hetznercloud_AddonUrl('products'), 'Products'],
    ];

    echo '<div class="hc-card"><h3>Getting started</h3><table class="hc-table"><tbody>';
    foreach ($steps as [$label, $done, $url, $linkLabel]) {
        echo '<tr><td style="width:34px">'
            . ($done ? '<span class="hc-chip hc-chip--ok">done</span>' : '<span class="hc-chip hc-chip--warn">todo</span>')
            . '</td><td>' . hetznercloud_E($label) . '</td><td class="hc-right">'
            . ($url && !$done ? '<a class="hc-btn hc-btn--sm" href="' . hetznercloud_E($url) . '">'
                . hetznercloud_E($linkLabel) . '</a>' : '')
            . '</td></tr>';
    }
    echo '</tbody></table></div>';

    /* Resources -------------------------------------------------------- */

    if ($counts) {
        echo '<div class="hc-card"><h3>Tracked resources</h3><div class="hc-tags">';
        foreach ($counts as $type => $count) {
            echo '<span class="hc-chip">' . hetznercloud_E($type) . ' · ' . (int) $count . '</span> ';
        }
        echo '</div><p class="hc-muted">Every resource the module creates at Hetzner is labelled with its '
            . 'WHMCS service id. This table mirrors those labels so listings and limit checks do not need an '
            . 'API call.</p></div>';
    }

    /* Maintenance ------------------------------------------------------ */

    echo '<div class="hc-card"><h3>Maintenance</h3>'
        . '<p class="hc-muted">Server types, locations, images and ISOs are cached for up to 24 hours. '
        . 'Refresh after Hetzner adds new plans or images.</p>'
        . '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('status', 'clear_cache')) . '">'
        . '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">'
        . '<button class="hc-btn" type="submit">Refresh API cache</button></form></div>';

    /* Languages -------------------------------------------------------- */

    echo '<div class="hc-card"><h3>Languages</h3>'
        . '<p class="hc-muted">Edit or add files in <code>modules/servers/hetznercloud/lang/</code>. Each file is a '
        . 'plain <code>$_LANG[\'key\'] = \'value\';</code> list and any missing key falls back to English.</p>'
        . '<div class="hc-tags">';
    foreach (hetznercloud_AvailableLanguages() as $language) {
        echo '<span class="hc-chip">' . hetznercloud_E($language) . '</span> ';
    }
    echo '</div></div>';
}

function hetznercloud_StatCard($label, $value, $tone = '')
{
    $colour = $tone === 'ok' ? 'var(--hc-ok)' : ($tone === 'bad' ? 'var(--hc-bad)' : 'inherit');

    return '<div class="hc-stat"><b>' . hetznercloud_E($label) . '</b>'
        . '<span style="color:' . $colour . '">' . hetznercloud_E($value) . '</span></div>';
}

/* -------------------------------------------------------------------------
 * Tab: connection
 * ---------------------------------------------------------------------- */

function hetznercloud_AddonTabConnection($token)
{
    $entries = hetznercloud_ServerEntries();
    $activeToken = hetznercloud_Token();

    echo '<div class="hc-card"><h3>API token</h3>'
        . '<p class="hc-muted">Paste a Hetzner Cloud API token and the module verifies it, then creates or updates '
        . 'the WHMCS server entry and its server group for you — no need to visit '
        . '<em>Setup → Products/Services → Servers</em>. Create the token in the Hetzner Console under '
        . '<em>Project → Security → API tokens</em> with <strong>Read &amp; Write</strong> permission.</p>';

    echo '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('connection', 'connect')) . '" class="hc-inline">'
        . '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">'
        . '<div class="hc-field"><label>Server entry name</label>'
        . '<input type="text" class="hc-input" name="server_name" value="Hetzner Cloud"></div>'
        . '<div class="hc-field" style="flex:1 1 320px"><label>API token</label>'
        . '<input type="password" class="hc-input" name="api_token" autocomplete="off" '
        . 'placeholder="64 characters" style="font-family:ui-monospace,monospace"></div>'
        . '<button class="hc-btn hc-btn--accent" type="submit">Verify &amp; save</button>'
        . '</form></div>';

    echo '<div class="hc-card"><h3>Server entries</h3>';

    if (!count($entries)) {
        echo '<div class="hc-empty">No server entry yet — paste a token above to create one.</div>';
    } else {
        echo '<div class="hc-table-wrap"><table class="hc-table"><thead><tr>'
            . '<th>Server entry</th><th>Enabled</th><th class="hc-right">Stored token</th></tr></thead><tbody>';

        foreach ($entries as $entry) {
            $resolved = hetznercloud_UnlockToken($entry->accesshash);

            if ($resolved === '') {
                $shape = '<span class="hc-chip hc-chip--bad">empty</span>';
            } elseif (hetznercloud_LooksLikeToken($resolved)) {
                $shape = '<span class="hc-chip hc-chip--ok">' . strlen($resolved) . ' chars · valid shape</span>';
            } else {
                $shape = '<span class="hc-chip hc-chip--bad">' . strlen($resolved) . ' chars · not a Hetzner token</span>';
            }

            echo '<tr><td><strong>' . hetznercloud_E($entry->name) . '</strong> '
                . '<span class="hc-muted">#' . (int) $entry->id . '</span></td>'
                . '<td>' . ($entry->active
                    ? '<span class="hc-chip hc-chip--ok">yes</span>'
                    : '<span class="hc-chip hc-chip--bad">no</span>') . '</td>'
                . '<td class="hc-right">' . $shape . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    if ($activeToken !== '' && !hetznercloud_LooksLikeToken($activeToken)) {
        echo '<div class="hc-note hc-note--bad" style="margin-top:12px">The token in use is '
            . strlen($activeToken) . ' characters and does not match the Hetzner format. Re-paste it above — '
            . 'a token copied with a line break, or a truncated paste, makes the API answer with an opaque '
            . 'HTTP 400.</div>';
    }

    echo '</div>';
}

/* -------------------------------------------------------------------------
 * Tab: products
 * ---------------------------------------------------------------------- */

function hetznercloud_AddonTabProducts($token)
{
    $products = hetznercloud_ModuleProducts();

    echo '<div class="hc-card"><h3>Product setup</h3>'
        . '<p class="hc-muted">Setup creates the <code>serverID</code> and <code>ssh_key</code> custom fields, links '
        . 'a full configurable option group (location, image, server type, disk format, IP protocol, floating IPs, '
        . 'backups and the snapshot / firewall limits) and assigns the server group. Safe to run again after Hetzner '
        . 'adds new images or locations.</p>'
        . '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('products', 'setup_all')) . '">'
        . '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">'
        . '<button class="hc-btn" type="submit">Run setup for every product</button></form></div>';

    if (!$products) {
        echo '<div class="hc-note hc-note--warn">No product uses the module yet. '
            . '<a href="' . hetznercloud_E(hetznercloud_AddonUrl('create')) . '">Create some from Hetzner plans</a>.</div>';
        return;
    }

    $definitions = hetznercloud_FeatureDefinitions();
    $limitDefinitions = hetznercloud_LimitDefinitions();
    $onlyOne = count($products) === 1;

    foreach ($products as $product) {
        $overrides = hetznercloud_ProductOverrides((int) $product->id);
        $hasFields = (bool) hetznercloud_CustomFieldIdLookup('serverID', $product->id);

        $groupLinked = false;
        try {
            $groupLinked = Capsule::table('tblproductconfiglinks')->where('pid', $product->id)->exists();
        } catch (\Exception $e) {
            $groupLinked = false;
        }

        echo '<details class="hc-card"' . ($onlyOne ? ' open' : '') . '>';
        echo '<summary>' . hetznercloud_E($product->name)
            . ' <span class="hc-muted">#' . (int) $product->id . '</span> &nbsp; '
            . ($hasFields ? '<span class="hc-chip hc-chip--ok">custom fields</span>'
                : '<span class="hc-chip hc-chip--bad">custom fields missing</span>') . ' '
            . ($groupLinked ? '<span class="hc-chip hc-chip--ok">configurable options</span>'
                : '<span class="hc-chip">no configurable options</span>')
            . '</summary>';

        echo '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('products', 'setup')) . '" style="margin:10px 0">'
            . '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">'
            . '<input type="hidden" name="pid" value="' . (int) $product->id . '">'
            . '<button class="hc-btn' . ($hasFields ? '' : ' hc-btn--accent') . '" type="submit">Run setup</button>'
            . '</form>';

        echo '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('products', 'save_features')) . '">'
            . '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">'
            . '<input type="hidden" name="pid" value="' . (int) $product->id . '">';

        echo '<h4 style="margin:6px 0 4px">Client area features</h4>'
            . '<p class="hc-muted" style="margin-bottom:10px">Unticking a box hides that section from the client '
            . 'panel <em>and</em> refuses the matching API call. Features marked '
            . '<span class="hc-billable">billable</span> cost the customer money at Hetzner, so they are off by '
            . 'default — sell them as a configurable option instead.</p>';

        $grouped = [];
        foreach ($definitions as $key => $definition) {
            $grouped[$definition['group']][$key] = $definition;
        }

        echo '<div class="hc-grid">';
        foreach ($grouped as $groupName => $features) {
            echo '<div><b style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:.05em;'
                . 'color:var(--hc-text-soft);margin-bottom:6px">' . hetznercloud_E($groupName) . '</b>';

            foreach ($features as $key => $definition) {
                echo '<label class="hc-check">'
                    . '<input type="checkbox" name="features[' . hetznercloud_E($key) . ']" value="1"'
                    . (!empty($overrides['features'][$key]) ? ' checked' : '') . '>'
                    . '<span>' . hetznercloud_E($definition['label'])
                    . (!empty($definition['billable']) ? ' <span class="hc-billable">billable</span>' : '')
                    . '</span></label>';
            }

            echo '</div>';
        }
        echo '</div>';

        echo '<h4 style="margin:16px 0 4px">Limits</h4>'
            . '<p class="hc-muted" style="margin-bottom:10px">Leave blank to inherit the product setting or '
            . 'configurable option.</p><div class="hc-inline">';

        foreach ($limitDefinitions as $key => $definition) {
            $value = $overrides['limits'][$key];
            echo '<div class="hc-field"><label>' . hetznercloud_E($definition['label']) . '</label>'
                . '<input type="number" min="0" class="hc-input" style="width:120px" '
                . 'name="limits[' . hetznercloud_E($key) . ']" value="' . ($value === null ? '' : (int) $value) . '" '
                . 'placeholder="' . ($definition['default'] === null ? 'inherit' : (int) $definition['default'])
                . '"></div>';
        }

        echo '</div><p style="margin-top:14px"><button class="hc-btn hc-btn--accent" type="submit">Save features</button></p>';
        echo '</form></details>';
    }
}

/* -------------------------------------------------------------------------
 * Tab: create products
 * ---------------------------------------------------------------------- */

function hetznercloud_AddonTabCreate($token)
{
    $serverTypes = hetznercloud_ServerTypes();
    $locationPairs = hetznercloud_LocationPairs();
    $imagePairs = hetznercloud_ImagePairs();
    $groups = hetznercloud_ProductGroups();

    $currency = '';
    try {
        $currency = (string) Capsule::table('tblcurrencies')->where('default', 1)->value('code');
    } catch (\Exception $e) {
        $currency = '';
    }

    if (!$serverTypes) {
        echo '<div class="hc-note hc-note--bad">No server types available. Fix the '
            . '<a href="' . hetznercloud_E(hetznercloud_AddonUrl('connection')) . '">API connection</a>, then '
            . 'refresh the API cache from the Status tab.</div>';
        return;
    }

    echo '<div class="hc-card"><h3>Create products from Hetzner plans</h3>'
        . '<p class="hc-muted">Every non-deprecated server type Hetzner offers, with its own monthly gross price. '
        . 'Pick a group, location, image and markup, then create one plan or all of them at once. Each new product '
        . 'is fully wired: module, server group, custom fields, configurable options and pricing. Running it twice '
        . 'will not duplicate anything.</p>';

    echo '<form method="post" action="' . hetznercloud_E(hetznercloud_AddonUrl('create', 'create_products')) . '">';
    echo '<input type="hidden" name="token" value="' . hetznercloud_E($token) . '">';

    echo '<div class="hc-inline" style="margin-bottom:14px">';

    echo '<div class="hc-field"><label>Product group</label><select class="hc-select" name="gid">'
        . '<option value="0">— create new —</option>';
    foreach ($groups as $group) {
        echo '<option value="' . (int) $group->id . '">' . hetznercloud_E($group->name) . '</option>';
    }
    echo '</select></div>';

    echo '<div class="hc-field"><label>New group name</label>'
        . '<input type="text" class="hc-input" name="group_name" value="Hetzner Cloud VPS"></div>';

    echo '<div class="hc-field"><label>Location</label><select class="hc-select" name="location">';
    foreach ($locationPairs as $value => $label) {
        echo '<option value="' . hetznercloud_E($value) . '">' . hetznercloud_E($label) . '</option>';
    }
    echo '</select></div>';

    echo '<div class="hc-field"><label>OS image</label><select class="hc-select" name="image">';
    foreach ($imagePairs as $value => $label) {
        echo '<option value="' . hetznercloud_E($value) . '"'
            . (stripos($value, 'ubuntu-24') === 0 ? ' selected' : '') . '>' . hetznercloud_E($label) . '</option>';
    }
    echo '</select></div>';

    echo '<div class="hc-field"><label>Markup %</label>'
        . '<input type="number" class="hc-input" style="width:100px" name="markup" value="30" min="0" max="1000" step="1"></div>';
    echo '<div class="hc-field"><label>Setup fee</label>'
        . '<input type="number" class="hc-input" style="width:100px" name="setupfee" value="0" min="0" step="0.01"></div>';
    echo '<div class="hc-field"><label>Hostname prefix</label>'
        . '<input type="text" class="hc-input" style="width:110px" name="prefix" value="vps"></div>';

    echo '<div class="hc-field"><label>Auto setup</label><select class="hc-select" name="autosetup">'
        . '<option value="payment">On first payment</option>'
        . '<option value="on">On order</option>'
        . '<option value="order">On manual approval</option>'
        . '<option value="">Off</option></select></div>';

    echo '</div>';

    echo '<div class="hc-table-wrap"><table class="hc-table"><thead><tr>'
        . '<th style="width:36px"><input type="checkbox" id="hc-all"></th>'
        . '<th>Plan</th><th>vCPU</th><th>RAM</th><th>Disk</th><th>Arch</th>'
        . '<th>Hetzner / month</th><th>Your price</th><th class="hc-right"></th></tr></thead><tbody>';

    foreach ($serverTypes as $type) {
        echo '<tr>'
            . '<td><input type="checkbox" name="types[]" class="hc-pick" value="' . hetznercloud_E($type['name']) . '"></td>'
            . '<td><strong>' . hetznercloud_E(strtoupper($type['name'])) . '</strong> '
            . '<span class="hc-muted">' . hetznercloud_E($type['cpu_type']) . '</span></td>'
            . '<td>' . (int) $type['cores'] . '</td>'
            . '<td>' . (float) $type['memory'] . ' GB</td>'
            . '<td>' . (int) $type['disk'] . ' GB</td>'
            . '<td>' . hetznercloud_E($type['architecture']) . '</td>'
            . '<td>' . number_format((float) $type['price'], 2) . ' EUR</td>'
            . '<td class="hc-price" data-base="' . (float) $type['price'] . '">—</td>'
            . '<td class="hc-right"><button class="hc-btn hc-btn--sm" type="submit" name="single" value="'
            . hetznercloud_E($type['name']) . '">Create</button></td></tr>';
    }

    echo '</tbody></table></div>';

    echo '<p style="margin-top:14px"><button class="hc-btn hc-btn--accent" type="submit">Create selected products</button>'
        . '<span class="hc-muted" style="margin-inline-start:10px">Prices are Hetzner gross EUR'
        . ($currency && strtoupper($currency) !== 'EUR'
            ? ', written into your default currency (' . hetznercloud_E($currency) . ') without conversion — review after import'
            : '')
        . '.</span></p>';

    echo '</form></div>';

    echo '<script>(function(){
        var all=document.getElementById("hc-all");
        var picks=[].slice.call(document.querySelectorAll(".hc-pick"));
        var markup=document.querySelector("input[name=markup]");
        function paint(){
            var m=1+((parseFloat(markup.value)||0)/100);
            [].slice.call(document.querySelectorAll(".hc-price")).forEach(function(cell){
                cell.textContent=(parseFloat(cell.dataset.base)*m).toFixed(2);
            });
        }
        if(all){all.addEventListener("change",function(){picks.forEach(function(p){p.checked=all.checked;});});}
        if(markup){markup.addEventListener("input",paint);paint();}
    })();</script>';
}

/**
 * Small wrapper so the addon does not need the whole provisioning file set.
 */
function hetznercloud_CustomFieldIdLookup($fieldName, $productId)
{
    try {
        $fields = Capsule::table('tblcustomfields')
            ->where('type', 'product')
            ->where('relid', (int) $productId)
            ->pluck('fieldname')
            ->all();

        foreach ($fields as $name) {
            if (strcasecmp(trim(explode('|', $name)[0]), $fieldName) === 0) {
                return true;
            }
        }
    } catch (\Exception $e) {
        return false;
    }

    return false;
}
