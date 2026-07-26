<?php
/**
 * The control panel markup.
 *
 * Rendered by both the client area (via templates/clientarea.tpl) and the admin
 * service page (via admin.php), so there is exactly one copy of the UI. The
 * caller supplies a config array; everything else - styling, behaviour, the
 * JSON endpoint - is identical on both sides.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Escape for HTML output.
 */
function hetznercloud_E($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Asset URL with a cache buster taken from the file's own timestamp.
 *
 * Keying this to the module version means an edited stylesheet or script keeps
 * serving from the browser cache until the version happens to change - which is
 * how a half-updated panel ends up silently broken.
 */
function hetznercloud_Asset($moduleUrl, $file)
{
    $path = __DIR__ . '/templates/' . $file;
    $stamp = is_readable($path) ? filemtime($path) : time();

    return hetznercloud_E($moduleUrl . 'templates/' . $file . '?v=' . $stamp);
}

/**
 * Copy button + read-only field.
 */
function hetznercloud_Field($label, $value, $id, $type = 'text', $extra = '')
{
    $copyIcon = '<svg viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5h10"/></svg>';

    return '<div class="hc-field"><label for="' . hetznercloud_E($id) . '">' . hetznercloud_E($label) . '</label>'
        . '<div class="hc-copy">'
        . '<input type="' . $type . '" id="' . hetznercloud_E($id) . '" value="' . hetznercloud_E($value) . '" readonly>'
        . $extra
        . '<button type="button" class="hc-icon-btn" data-copy="#' . hetznercloud_E($id) . '" title="'
        . hetznercloud_E(hetznercloud_L('copy')) . '">' . $copyIcon . '</button>'
        . '</div></div>';
}

/**
 * Build the whole panel.
 *
 * @param array $cfg see hetznercloud_PanelConfig()
 */
function hetznercloud_PanelHtml(array $cfg)
{
    $lang = $cfg['lang'];
    $options = $cfg['options'];
    $tabs = $cfg['tabs'];
    $summary = $cfg['summary'];

    $l = function ($key) use ($lang) {
        return hetznercloud_E($lang[$key] ?? $key);
    };

    $json = json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $version = hetznercloud_E($cfg['version']);

    if ($json === false) {
        return '<div class="hc-note hc-note--bad">Hetzner Cloud: the panel configuration could not be encoded '
            . '(' . hetznercloud_E(json_last_error_msg()) . ').</div>';
    }

    $html = '<link href="' . hetznercloud_Asset($cfg['moduleUrl'], 'style.css') . '" rel="stylesheet">';

    $html .= '<div class="hc" id="hc-app" dir="' . ($cfg['rtl'] ? 'rtl' : 'ltr')
        . '" style="--hc-accent: ' . hetznercloud_E($cfg['accent']) . ';">';

    $html .= '<noscript><div class="hc-note hc-note--warn">This control panel needs JavaScript enabled.</div></noscript>';

    /* ---- hero ---------------------------------------------------------- */

    $html .= '<header class="hc-hero"><div class="hc-hero__id">'
        . '<span class="hc-dot" id="hc-status-dot" data-tone="' . hetznercloud_E($summary['status']['tone']) . '"></span>'
        . '<div><h2 id="hc-server-name">'
        . ($summary['available'] ? hetznercloud_E($summary['name']) : $l('status_provisioning'))
        . '</h2><p id="hc-status-text">'
        . hetznercloud_E(implode(' · ', array_filter([
            $summary['status']['label'],
            $summary['available'] ? $summary['type'] : '',
            $summary['available'] ? $summary['location'] : '',
        ], function ($v) { return is_string($v) && $v !== ''; })))
        . '</p></div></div>';

    $html .= '<div class="hc-hero__actions">';

    if (!empty($options['allow_power'])) {
        $power = '<svg viewBox="0 0 24 24"><path d="M12 3v9M6.4 6.4a8 8 0 1 0 11.2 0"/></svg>';
        $html .= '<button type="button" class="hc-btn hc-btn--ok" data-power="on" title="' . $l('power_on') . '">'
            . $power . '<span>' . $l('power_on') . '</span></button>';
        $html .= '<button type="button" class="hc-btn" data-power="shutdown" title="' . $l('shutdown') . '">'
            . $power . '<span>' . $l('shutdown') . '</span></button>';
        $html .= '<button type="button" class="hc-btn" data-power="reboot" title="' . $l('reboot') . '">'
            . '<svg viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-2.6-6.4M21 3v6h-6"/></svg>'
            . '<span>' . $l('reboot') . '</span></button>';
        $html .= '<button type="button" class="hc-btn hc-btn--bad" data-power="off" title="' . $l('power_off') . '">'
            . '<svg viewBox="0 0 24 24"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>'
            . '<span>' . $l('power_off') . '</span></button>';
    }

    if (!empty($options['allow_console'])) {
        $html .= '<button type="button" class="hc-btn hc-btn--accent" id="hc-console" title="' . $l('console') . '">'
            . '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 10l3 2-3 2M13 14h4"/></svg>'
            . '<span>' . $l('console') . '</span></button>';
    }

    $html .= '<button type="button" class="hc-icon-btn" id="hc-theme" title="Theme">'
        . '<svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg></button>';

    $html .= '</div></header>';

    /* ---- tabs ---------------------------------------------------------- */

    $tabLabels = [
        'overview' => 'tab_overview',
        'graphs' => 'tab_graphs',
        'backups' => 'tab_backups',
        'snapshots' => 'tab_snapshots',
        'network' => 'tab_network',
        'firewall' => 'tab_firewall',
        'iso' => 'tab_iso',
        'rescue' => 'tab_rescue',
        'rebuild' => 'tab_rebuild',
        'ssh' => 'tab_ssh',
        'addons' => 'tab_addons',
        'history' => 'tab_history',
    ];

    $html .= '<nav class="hc-tabs" id="hc-tabs" role="tablist">';
    $first = true;
    foreach ($tabLabels as $key => $labelKey) {
        if (empty($tabs[$key])) {
            continue;
        }
        $html .= '<button type="button" class="hc-tab' . ($first ? ' is-active' : '') . '" data-tab="'
            . $key . '" role="tab">' . $l($labelKey) . '</button>';
        $first = false;
    }
    $html .= '</nav>';

    $html .= '<main class="hc-panels">';

    /* ---- overview ------------------------------------------------------ */

    $html .= '<section class="hc-panel is-active" data-panel="overview"><div class="hc-cols">';

    $html .= '<div class="hc-card"><h3>' . $l('tab_overview') . '</h3><dl class="hc-specs" id="hc-specs"></dl></div>';

    $html .= '<div class="hc-card"><h3>' . $l('access_info') . '</h3>';
    $html .= hetznercloud_Field($lang['ipv4'] ?? 'IPv4', $summary['ipv4'] ?? '', 'hc-ipv4');

    if (!empty($summary['ipv6'])) {
        $html .= hetznercloud_Field($lang['ipv6'] ?? 'IPv6', $summary['ipv6'], 'hc-ipv6');
    }

    $html .= hetznercloud_Field($lang['username'] ?? 'Username', $cfg['username'], 'hc-user');

    $eye = '<button type="button" class="hc-icon-btn" id="hc-pass-toggle" title="' . $l('show') . '">'
        . '<svg viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></button>';
    $html .= hetznercloud_Field($lang['password'] ?? 'Password', $cfg['password'], 'hc-pass', 'password', $eye);

    $html .= hetznercloud_Field(
        $lang['ssh_command'] ?? 'SSH command',
        'ssh ' . $cfg['username'] . '@' . ($summary['ipv4'] ?? ''),
        'hc-ssh'
    );

    $html .= '<div class="hc-row">';
    if (!empty($options['allow_reset_password'])) {
        $html .= '<button type="button" class="hc-btn hc-btn--accent" id="hc-reset-pass">' . $l('reset_password') . '</button>';
    }
    if (!empty($options['allow_change_plan']) && !empty($cfg['upgradeUrl'])) {
        $html .= '<a class="hc-btn" href="' . hetznercloud_E($cfg['upgradeUrl']) . '">' . $l('change_package') . '</a>';
    }
    if (!empty($options['allow_power'])) {
        $html .= '<button type="button" class="hc-btn hc-btn--bad" data-power="reset">' . $l('reset') . '</button>';
    }
    $html .= '</div></div></div>';

    if (!empty($options['allow_bandwidth'])) {
        $html .= '<div class="hc-card"><h3>' . $l('bandwidth') . '</h3><div id="hc-bandwidth"></div></div>';
    }

    $html .= '</section>';

    /* ---- graphs -------------------------------------------------------- */

    if (!empty($tabs['graphs'])) {
        $ranges = [30 => 'range_30m', 60 => 'range_1h', 360 => 'range_6h',
            1440 => 'range_24h', 10080 => 'range_7d', 43200 => 'range_30d'];

        $html .= '<section class="hc-panel" data-panel="graphs">'
            . '<div class="hc-filters" role="group" aria-label="' . $l('tab_graphs') . '">'
            . '<div class="hc-segment" id="hc-metric-range">';

        foreach ($ranges as $minutes => $labelKey) {
            $html .= '<button type="button" class="' . ($minutes === 1440 ? 'is-active' : '')
                . '" data-minutes="' . $minutes . '">' . $l($labelKey) . '</button>';
        }

        $html .= '</div><div class="hc-filters__end">'
            . '<button type="button" class="hc-btn hc-btn--sm" id="hc-metric-table" aria-pressed="false">'
            . $l('table_view') . '</button>'
            . '<button type="button" class="hc-btn hc-btn--sm" id="hc-metric-refresh">' . $l('refresh') . '</button>'
            . '<span class="hc-muted" id="hc-metric-stamp"></span>'
            . '</div></div><div class="hc-charts" id="hc-charts"></div></section>';
    }

    /* ---- backups ------------------------------------------------------- */

    if (!empty($tabs['backups'])) {
        $html .= '<section class="hc-panel" data-panel="backups"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('backups') . '</h3><div id="hc-backup-toggle"></div></div>'
            . '<p class="hc-muted">' . $l('backup_intro') . '</p><div id="hc-backup-list"></div></div></section>';
    }

    /* ---- snapshots ----------------------------------------------------- */

    if (!empty($tabs['snapshots'])) {
        $html .= '<section class="hc-panel" data-panel="snapshots"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('snapshots') . '</h3>'
            . '<button type="button" class="hc-btn hc-btn--accent" id="hc-snapshot-create">' . $l('create_snapshot') . '</button></div>'
            . '<p class="hc-muted">' . $l('snapshot_intro') . '</p><div id="hc-snapshot-list"></div></div></section>';
    }

    /* ---- network ------------------------------------------------------- */

    if (!empty($tabs['network'])) {
        $html .= '<section class="hc-panel" data-panel="network">';

        if (!empty($options['allow_floating_ips'])) {
            $html .= '<div class="hc-card"><div class="hc-card__head"><h3>' . $l('floating_ips') . '</h3>';
            if (!empty($options['allow_floating_ips_order'])) {
                $html .= '<button type="button" class="hc-btn hc-btn--accent" id="hc-fip-create">'
                    . $l('create_floating_ip') . '</button>';
            }
            $html .= '</div><p class="hc-muted">' . $l('floating_ip_intro') . '</p><div id="hc-fip-list"></div></div>';
        }

        if (!empty($options['allow_rdns'])) {
            $html .= '<div class="hc-card"><h3>' . $l('reverse_dns') . '</h3>'
                . '<p class="hc-muted">' . $l('rdns_intro') . '</p><div id="hc-rdns"></div></div>';
        }

        if (!empty($options['allow_volumes']) && $options['volume_size'] > 0) {
            $html .= '<div class="hc-card"><h3>' . $l('disk') . '</h3><div id="hc-volume-list"></div></div>';
        }

        if (!empty($options['allow_networks']) && $options['network_limit'] > 0) {
            $html .= '<div class="hc-card"><div class="hc-card__head"><h3>' . $l('networks') . '</h3>'
                . '<button type="button" class="hc-btn hc-btn--accent" id="hc-net-create">' . $l('create_network') . '</button></div>'
                . '<p class="hc-muted">' . $l('network_intro') . '</p><div id="hc-net-list"></div></div>';
        }

        $html .= '</section>';
    }

    /* ---- firewall ------------------------------------------------------ */

    if (!empty($tabs['firewall'])) {
        $html .= '<section class="hc-panel" data-panel="firewall"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('firewalls') . '</h3>'
            . '<button type="button" class="hc-btn hc-btn--accent" id="hc-fw-create">' . $l('create_firewall') . '</button></div>'
            . '<p class="hc-muted">' . $l('firewall_intro') . '</p><div id="hc-fw-list"></div></div></section>';
    }

    /* ---- iso ----------------------------------------------------------- */

    if (!empty($tabs['iso'])) {
        $html .= '<section class="hc-panel" data-panel="iso"><div class="hc-card">'
            . '<h3>' . $l('iso') . '</h3><p class="hc-muted">' . $l('iso_intro') . '</p>'
            . '<div id="hc-iso-current"></div>'
            . '<input type="search" class="hc-input" id="hc-iso-search" placeholder="' . $l('search_iso') . '">'
            . '<div id="hc-iso-list" class="hc-scroll"></div></div></section>';
    }

    /* ---- rescue -------------------------------------------------------- */

    if (!empty($tabs['rescue'])) {
        $html .= '<section class="hc-panel" data-panel="rescue"><div class="hc-card">'
            . '<h3>' . $l('rescue_mode') . '</h3><p class="hc-muted">' . $l('rescue_intro') . '</p>'
            . '<div id="hc-rescue"></div></div></section>';
    }

    /* ---- rebuild ------------------------------------------------------- */

    if (!empty($tabs['rebuild'])) {
        $html .= '<section class="hc-panel" data-panel="rebuild"><div class="hc-card">'
            . '<h3>' . $l('rebuild') . '</h3>'
            . '<div class="hc-note hc-note--warn">' . $l('rebuild_warning') . '</div>'
            . '<input type="search" class="hc-input" id="hc-image-search" placeholder="' . $l('select_os') . '">'
            . '<div id="hc-image-list" class="hc-scroll"></div>'
            . '<button type="button" class="hc-btn hc-btn--bad" id="hc-rebuild-go" disabled>' . $l('rebuild') . '</button>'
            . '</div></section>';
    }

    /* ---- ssh ----------------------------------------------------------- */

    if (!empty($tabs['ssh'])) {
        $html .= '<section class="hc-panel" data-panel="ssh"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('ssh_keys') . '</h3>'
            . '<button type="button" class="hc-btn hc-btn--accent" id="hc-ssh-add">' . $l('add_ssh_key') . '</button></div>'
            . '<p class="hc-muted">' . $l('ssh_intro') . '</p><div id="hc-ssh-list"></div></div></section>';
    }

    /* ---- addons -------------------------------------------------------- */

    if (!empty($tabs['addons'])) {
        $html .= '<section class="hc-panel" data-panel="addons"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('tab_addons') . '</h3>'
            . (!empty($cfg['addonsUrl'])
                ? '<a class="hc-btn hc-btn--accent" href="' . hetznercloud_E($cfg['addonsUrl']) . '">' . $l('addons_buy') . '</a>'
                : '')
            . '</div><p class="hc-muted">' . $l('addons_intro') . '</p><div id="hc-addons"></div></div></section>';
    }

    /* ---- history ------------------------------------------------------- */

    if (!empty($tabs['history'])) {
        $html .= '<section class="hc-panel" data-panel="history"><div class="hc-card">'
            . '<div class="hc-card__head"><h3>' . $l('history') . '</h3>'
            . '<button type="button" class="hc-btn" id="hc-history-refresh">' . $l('refresh') . '</button></div>'
            . '<div id="hc-history"></div></div></section>';
    }

    $html .= '</main>';

    $html .= '<footer class="hc-foot"><span>Hetzner Cloud module v' . $version . '</span>'
        . '<a href="https://github.com/lastwall/whmcs-hetzner-cloud-automation" target="_blank" rel="noopener">GitHub</a></footer>';

    $html .= '<div class="hc-toasts" id="hc-toasts" aria-live="polite"></div>';
    $html .= '<div class="hc-modal" id="hc-modal" hidden><div class="hc-modal__box" role="dialog" aria-modal="true"></div></div>';

    $html .= '</div>';

    $html .= '<script>window.HC_CONFIG = ' . $json . ';</script>';
    $html .= '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>';
    $html .= '<script src="' . hetznercloud_Asset($cfg['moduleUrl'], 'script.js') . '" defer></script>';

    return $html;
}
