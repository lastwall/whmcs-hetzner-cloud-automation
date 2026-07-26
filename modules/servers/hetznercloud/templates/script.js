/* ==========================================================================
   Hetzner Cloud client panel
   Vanilla JS, no framework. Talks to clientarea.php?...&hcaction=<action>
   ========================================================================== */

(function () {
    'use strict';

    var CFG = window.HC_CONFIG || {};
    var LANG = CFG.lang || {};
    var OPT = CFG.options || {};
    var root = document.getElementById('hc-app');

    if (!root) {
        return;
    }

    // A missing config means the inline <script> block failed to parse. Say so
    // rather than half-working with every endpoint undefined.
    if (!window.HC_CONFIG || !CFG.endpoint) {
        root.insertAdjacentHTML('afterbegin',
            '<div class="hc-note hc-note--bad">Control panel configuration failed to load. '
            + 'Check the browser console and the WHMCS module log.</div>');
        return;
    }

    /* ---------------------------------------------------------------- utils */

    function t(key, replace) {
        var value = LANG[key] || key;
        if (replace) {
            Object.keys(replace).forEach(function (name) {
                value = value.split('{' + name + '}').join(replace[name]);
            });
        }
        return value;
    }

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function $(selector, scope) {
        return (scope || root).querySelector(selector);
    }

    function $$(selector, scope) {
        return Array.prototype.slice.call((scope || root).querySelectorAll(selector));
    }

    function bytes(value) {
        value = Number(value) || 0;
        if (value <= 0) { return '0 B'; }
        var units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        var power = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
        return (value / Math.pow(1024, power)).toFixed(power ? 2 : 0) + ' ' + units[power];
    }

    function date(value) {
        if (!value) { return '-'; }
        var parsed = new Date(value);
        return isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleString();
    }

    /* ------------------------------------------------------------------ api */

    function api(action, data, query) {
        var url = CFG.endpoint + '&hcaction=' + encodeURIComponent(action);

        if (query) {
            Object.keys(query).forEach(function (key) {
                url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(query[key]);
            });
        }

        var init = { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };

        if (data) {
            var body = new FormData();
            body.append('token', CFG.token);
            Object.keys(data).forEach(function (key) { body.append(key, data[key]); });
            init.method = 'POST';
            init.body = body;
        }

        // Report what actually came back. A silent failure here is how a broken
        // endpoint turns into an empty panel with no explanation.
        return fetch(url, init)
            .then(function (response) {
                return response.text().then(function (body) {
                    try {
                        return JSON.parse(body);
                    } catch (e) {
                        var snippet = body.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
                        return {
                            success: false,
                            message: t('request_failed') + ' (HTTP ' + response.status + ')'
                                + (snippet ? ': ' + snippet : '')
                        };
                    }
                });
            })
            .catch(function (error) {
                return { success: false, message: t('request_failed') + ': ' + (error && error.message || 'network error') };
            });
    }

    /* --------------------------------------------------------------- toasts */

    var toastBox = document.getElementById('hc-toasts');

    function toast(message, kind) {
        if (!toastBox || !message) { return; }

        var node = document.createElement('div');
        node.className = 'hc-toast hc-toast--' + (kind || 'info');
        node.textContent = message;
        toastBox.appendChild(node);

        setTimeout(function () {
            node.style.transition = 'opacity .3s, transform .3s';
            node.style.opacity = '0';
            node.style.transform = 'translateY(6px)';
            setTimeout(function () { node.remove(); }, 320);
        }, kind === 'bad' ? 7000 : 4000);
    }

    function report(result) {
        toast(result.message || (result.success ? t('success') : t('request_failed')), result.success ? 'ok' : 'bad');
        return result;
    }

    /* ---------------------------------------------------------------- modal */

    var modalRoot = document.getElementById('hc-modal');
    var modalBox = modalRoot ? modalRoot.querySelector('.hc-modal__box') : null;

    function closeModal() {
        if (modalRoot) {
            modalRoot.hidden = true;
            modalBox.innerHTML = '';
        }
    }

    function openModal(html) {
        if (!modalRoot) { return null; }
        modalBox.innerHTML = html;
        modalRoot.hidden = false;
        var focusable = modalBox.querySelector('input, select, textarea, button');
        if (focusable) { focusable.focus(); }
        return modalBox;
    }

    if (modalRoot) {
        modalRoot.addEventListener('click', function (event) {
            if (event.target === modalRoot || event.target.closest('[data-modal-close]')) {
                closeModal();
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modalRoot.hidden) { closeModal(); }
        });
    }

    /**
     * Promise-based confirmation. Pass requireWord to force the customer to
     * type it before the destructive button unlocks.
     */
    function confirmDialog(config) {
        return new Promise(function (resolve) {
            var needsWord = !!config.requireWord;
            var html = '<h3>' + esc(config.title || t('are_you_sure')) + '</h3>'
                + '<div class="hc-note ' + (config.danger ? 'hc-note--bad' : '') + '">' + config.body + '</div>'
                + (needsWord
                    ? '<div class="hc-field"><label>' + esc(t('type_confirm', { word: config.requireWord })) + '</label>'
                        + '<input type="text" class="hc-input" id="hc-confirm-word" autocomplete="off"></div>'
                    : '')
                + '<div class="hc-modal__foot">'
                + '<button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn ' + (config.danger ? 'hc-btn--bad' : 'hc-btn--accent') + '" id="hc-confirm-go"'
                + (needsWord ? ' disabled' : '') + '>' + esc(config.confirmLabel || t('confirm')) + '</button>'
                + '</div>';

            var box = openModal(html);
            if (!box) { resolve(false); return; }

            var go = box.querySelector('#hc-confirm-go');
            var word = box.querySelector('#hc-confirm-word');

            if (word) {
                word.addEventListener('input', function () {
                    go.disabled = word.value.trim().toUpperCase() !== String(config.requireWord).toUpperCase();
                });
            }

            go.addEventListener('click', function () { closeModal(); resolve(true); });
            box.querySelectorAll('[data-modal-close]').forEach(function (button) {
                button.addEventListener('click', function () { resolve(false); });
            });
        });
    }

    /* --------------------------------------------------------------- render */

    function skeleton(rows) {
        var html = '';
        for (var i = 0; i < (rows || 3); i++) {
            html += '<div class="hc-skeleton" style="width:' + (60 + (i % 3) * 15) + '%"></div>';
        }
        return html;
    }

    function empty(message) {
        return '<div class="hc-empty">' + esc(message) + '</div>';
    }

    function table(headers, rows) {
        if (!rows.length) { return ''; }
        var html = '<div class="hc-table-wrap"><table class="hc-table"><thead><tr>';
        headers.forEach(function (header, index) {
            html += '<th' + (index === headers.length - 1 ? ' class="hc-right"' : '') + '>' + esc(header) + '</th>';
        });
        html += '</tr></thead><tbody>';
        rows.forEach(function (cells) {
            html += '<tr>';
            cells.forEach(function (cell, index) {
                html += '<td' + (index === cells.length - 1 ? ' class="hc-right"' : '') + '>' + cell + '</td>';
            });
            html += '</tr>';
        });
        return html + '</tbody></table></div>';
    }

    function busy(button, on) {
        if (!button) { return; }
        button.classList.toggle('is-busy', !!on);
    }

    /* ---------------------------------------------------------------- theme */

    var themeKey = 'hc-theme';

    function applyTheme(value) {
        if (value === 'dark' || value === 'light') {
            root.setAttribute('data-theme', value);
        } else {
            root.removeAttribute('data-theme');
        }
    }

    try { applyTheme(localStorage.getItem(themeKey)); } catch (e) { /* private mode */ }

    var themeButton = document.getElementById('hc-theme');
    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var current = root.getAttribute('data-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var next = current ? (current === 'dark' ? 'light' : 'dark') : (prefersDark ? 'light' : 'dark');
            applyTheme(next);
            try { localStorage.setItem(themeKey, next); } catch (e) { /* ignore */ }
        });
    }

    /* ----------------------------------------------------------------- tabs */

    var loaded = {};
    var loaders = {};

    function showTab(name) {
        $$('.hc-tab').forEach(function (tab) { tab.classList.toggle('is-active', tab.dataset.tab === name); });
        $$('.hc-panel').forEach(function (panel) { panel.classList.toggle('is-active', panel.dataset.panel === name); });

        if (!loaded[name] && loaders[name]) {
            loaded[name] = true;
            loaders[name]();
        }
    }

    $$('.hc-tab').forEach(function (tab) {
        tab.addEventListener('click', function () { showTab(tab.dataset.tab); });
    });

    /* -------------------------------------------------------- copy & fields */

    root.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-copy]');
        if (!trigger) { return; }

        var field = $(trigger.dataset.copy);
        if (!field) { return; }

        var value = field.value;
        var done = function () { toast(t('copied'), 'ok'); };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(done, function () { legacyCopy(field, done); });
        } else {
            legacyCopy(field, done);
        }
    });

    function legacyCopy(field, done) {
        var type = field.type;
        field.type = 'text';
        field.select();
        try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
        field.type = type;
        window.getSelection().removeAllRanges();
    }

    var passField = document.getElementById('hc-pass');
    var passToggle = document.getElementById('hc-pass-toggle');
    if (passToggle && passField) {
        passToggle.addEventListener('click', function () {
            passField.type = passField.type === 'password' ? 'text' : 'password';
            passToggle.title = passField.type === 'password' ? t('show') : t('hide');
        });
    }

    /* ---------------------------------------------------------------- power */

    var statusDot = document.getElementById('hc-status-dot');
    var statusText = document.getElementById('hc-status-text');
    var summary = CFG.summary || {};

    function paintStatus(state) {
        if (statusDot) { statusDot.dataset.tone = state.tone || 'muted'; }
        if (statusText) {
            var extra = summary.available ? ' · ' + (summary.type || '') + ' · ' + (summary.location || '') : '';
            statusText.textContent = (state.label || '') + extra;
        }
    }

    var pollTimer = null;

    function pollStatus(times) {
        clearTimeout(pollTimer);
        var remaining = times || 1;

        var tick = function () {
            api('status').then(function (result) {
                if (result.success && result.data) {
                    paintStatus(result.data);
                }
                remaining--;
                if (remaining > 0) {
                    pollTimer = setTimeout(tick, 4000);
                } else if (CFG.poll > 0) {
                    pollTimer = setTimeout(function () { pollStatus(1); }, CFG.poll * 1000);
                }
            });
        };

        pollTimer = setTimeout(tick, 1200);
    }

    var powerLabels = { on: 'power_on', off: 'power_off', shutdown: 'shutdown', reboot: 'reboot', reset: 'reset' };

    $$('[data-power]').forEach(function (button) {
        button.addEventListener('click', function () {
            var op = button.dataset.power;

            var run = function () {
                busy(button, true);
                api('power', { op: op }).then(function (result) {
                    busy(button, false);
                    report(result);
                    pollStatus(4);
                });
            };

            if (op === 'off' || op === 'reset') {
                confirmDialog({
                    title: t(powerLabels[op]),
                    body: esc(t('are_you_sure')),
                    danger: true
                }).then(function (ok) { if (ok) { run(); } });
            } else {
                run();
            }
        });
    });

    /* -------------------------------------------------------------- console */

    var consoleButton = document.getElementById('hc-console');
    if (consoleButton) {
        consoleButton.addEventListener('click', function () {
            busy(consoleButton, true);
            var popup = window.open('', 'hcconsole', 'width=1100,height=720');

            api('console', {}).then(function (result) {
                busy(consoleButton, false);

                if (result.success && result.data && result.data.path) {
                    var url = CFG.moduleUrl + result.data.path;
                    if (popup) {
                        popup.location = url;
                    } else {
                        window.location = url;
                    }
                    return;
                }

                report(result);

                // Show the reason in the window we opened rather than closing it
                // and leaving the customer staring at nothing.
                if (popup) {
                    try {
                        popup.document.write('<!doctype html><meta charset="utf-8"><title>Console</title>'
                            + '<body style="font:15px/1.6 system-ui,sans-serif;background:#0d1017;color:#e7eaf0;'
                            + 'display:grid;place-items:center;height:100vh;margin:0">'
                            + '<div style="max-width:420px;padding:24px;text-align:center">'
                            + '<h1 style="font-size:17px;margin:0 0 8px">Console unavailable</h1><p style="color:#98a2b3">'
                            + String(result.message || '').replace(/[<>&]/g, '') + '</p></div>');
                        popup.document.close();
                    } catch (e) {
                        popup.close();
                    }
                }
            });
        });
    }

    /* ------------------------------------------------------- reset password */

    var resetButton = document.getElementById('hc-reset-pass');
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            confirmDialog({
                title: t('reset_password'),
                body: esc(t('are_you_sure')),
                danger: true
            }).then(function (ok) {
                if (!ok) { return; }
                busy(resetButton, true);
                api('reset_password', {}).then(function (result) {
                    busy(resetButton, false);
                    report(result);
                    if (result.success && result.data && result.data.password && passField) {
                        passField.value = result.data.password;
                        passField.type = 'text';
                    }
                });
            });
        });
    }

    /* --------------------------------------------------------------- overview */

    /**
     * "Falkenstein, DE (fsn1-dc14)" — skipping whichever parts Hetzner omits.
     */
    function formatLocation(data) {
        var place = [data.location, data.country].filter(Boolean).map(esc).join(', ');

        if (data.datacenter) {
            place += (place ? ' ' : '') + '(' + esc(data.datacenter) + ')';
        }

        return place || esc(t('status_unknown'));
    }

    function paintSpecs(data) {
        var box = document.getElementById('hc-specs');
        if (!box) { return; }

        if (!data || !data.available) {
            box.innerHTML = empty(t('server_missing'));
            return;
        }

        summary = data;

        var rows = [
            [t('status'), esc(data.status.label)],
            [t('server_type'), esc(data.type)],
            [t('vcpu'), esc(data.cores) + ' × ' + esc(data.cpu_type)],
            [t('ram'), esc(data.memory) + ' GB'],
            [t('disk'), esc(data.disk) + ' GB'],
            [t('architecture'), esc(data.architecture)],
            [t('operating_system'), esc(data.image)],
            [t('location'), formatLocation(data)],
            [t('created'), date(data.created)],
            [t('backups'), data.backups ? '<span class="hc-chip hc-chip--ok">' + esc(t('enabled')) + '</span>'
                : '<span class="hc-chip">' + esc(t('disabled')) + '</span>'],
            [t('server_id'), '#' + esc(data.id)]
        ];

        if (data.iso) {
            rows.push([t('iso'), '<span class="hc-chip hc-chip--warn">' + esc(data.iso) + '</span>']);
        }
        if (data.rescue) {
            rows.push([t('rescue_mode'), '<span class="hc-chip hc-chip--warn">' + esc(t('enabled')) + '</span>']);
        }
        if (data.private_ips && data.private_ips.length) {
            rows.push([t('private_ip'), esc(data.private_ips.join(', '))]);
        }

        box.innerHTML = rows.map(function (row) {
            return '<div><dt>' + esc(row[0]) + '</dt><dd>' + row[1] + '</dd></div>';
        }).join('');

        paintStatus(data.status);
        paintBandwidth(data.bandwidth);
    }

    function paintBandwidth(data) {
        var box = document.getElementById('hc-bandwidth');
        if (!box) { return; }

        if (!data) {
            box.innerHTML = empty(t('no_data'));
            return;
        }

        var tone = data.percent >= 100 ? ' is-bad' : (data.percent >= 80 ? ' is-warn' : '');

        box.innerHTML = '<div class="hc-meter' + tone + '"><span style="width:' + Math.min(100, data.percent) + '%"></span></div>'
            + '<div class="hc-meter-legend"><span>' + esc(data.used_h) + ' ' + esc(t('traffic_used')) + '</span>'
            + '<span>' + esc(data.included_h) + ' ' + esc(t('included_traffic')) + '</span></div>'
            + '<div class="hc-stats" style="margin-top:12px">'
            + '<div class="hc-stat"><b>' + esc(t('outgoing_traffic')) + '</b><span>' + esc(data.outgoing_h) + '</span></div>'
            + '<div class="hc-stat"><b>' + esc(t('incoming_traffic')) + '</b><span>' + esc(data.incoming_h) + '</span></div>'
            + '<div class="hc-stat"><b>' + esc(t('traffic_remaining')) + '</b><span>' + esc(data.remaining_h) + '</span></div>'
            + '</div>'
            + (data.percent >= 100 ? '<div class="hc-note hc-note--warn">' + esc(t('traffic_overage')) + '</div>' : '');
    }

    function refreshSummary() {
        api('summary').then(function (result) {
            if (result.success) { paintSpecs(result.data); }
        });
    }

    paintSpecs(CFG.summary);

    // The server-side first paint can come back empty when the Hetzner API was
    // slow or briefly unreachable during page render. Retry from the browser so
    // the panel heals itself instead of needing a reload.
    if (!CFG.summary || !CFG.summary.available) {
        refreshSummary();
    }

    /* ---------------------------------------------------------------- graphs
       A chart per measure, one unit per chart (never two y-scales), a shared
       range filter above them all, a crosshair that reads every series at the
       hovered time, and a table view so no value is hover-only.
    ---------------------------------------------------------------------- */

    var charts = {};
    var metricMinutes = 1440;
    var metricTable = false;
    var metricTimer = null;
    var metricPayload = null;

    /** Unit-aware formatting. One formatter per unit, used by axis and tooltip. */
    var UNITS = {
        percent: function (value) { return value.toFixed(value < 10 ? 1 : 0) + ' %'; },
        bytes: function (value) { return bytes(value) + '/s'; },
        pps: function (value) { return Math.round(value).toLocaleString() + ' p/s'; },
        iops: function (value) { return Math.round(value).toLocaleString() + ' IO/s'; }
    };

    function unitFormat(unit, value) {
        return (UNITS[unit] || UNITS.iops)(Number(value) || 0);
    }

    function token(name, fallback) {
        return getComputedStyle(root).getPropertyValue(name).trim() || fallback;
    }

    /** Validated two-slot categorical palette, stepped per mode. */
    function seriesColour(index) {
        return index === 0
            ? token('--hc-series-1', '#2a78d6')
            : token('--hc-series-2', '#eb6834');
    }

    function timeLabel(value) {
        var when = new Date(value);
        return metricMinutes > 2880
            ? when.toLocaleDateString([], { month: 'short', day: 'numeric' })
            : when.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /** current / average / peak for one series. */
    function summarise(data) {
        if (!data.length) { return { last: 0, avg: 0, max: 0 }; }

        var total = 0;
        var max = -Infinity;

        data.forEach(function (point) {
            total += point.y;
            if (point.y > max) { max = point.y; }
        });

        return { last: data[data.length - 1].y, avg: total / data.length, max: max };
    }

    /**
     * Crosshair: a hairline at the hovered x so the reader aims at a time,
     * not at a 2px line.
     */
    var crosshair = {
        id: 'hcCrosshair',
        afterDatasetsDraw: function (instance) {
            var active = instance.tooltip && instance.tooltip.getActiveElements
                ? instance.tooltip.getActiveElements()
                : [];

            if (!active.length) { return; }

            var ctx = instance.ctx;
            var x = active[0].element.x;

            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, instance.chartArea.top);
            ctx.lineTo(x, instance.chartArea.bottom);
            ctx.lineWidth = 1;
            ctx.strokeStyle = token('--hc-chart-axis', '#c3c2b7');
            ctx.stroke();
            ctx.restore();
        }
    };

    function buildChart(canvas, spec) {
        var ctx = canvas.getContext('2d');
        var grid = token('--hc-chart-grid', '#e4e7ec');
        var ink = token('--hc-text-soft', '#667085');
        var single = spec.series.length === 1;

        var datasets = spec.series.map(function (entry, index) {
            var colour = seriesColour(index);

            return {
                label: entry.label,
                data: entry.data,
                borderColor: colour,
                // Scriptable so the gradient is built from the real plot area:
                // at construction time the canvas has not been laid out yet.
                backgroundColor: function (context) {
                    var area = context.chart.chartArea;

                    if (!area) { return colour + '00'; }

                    var gradient = context.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    gradient.addColorStop(0, colour + (single ? '2e' : '20'));
                    gradient.addColorStop(1, colour + '00');

                    return gradient;
                },
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 4,
                pointHoverBorderWidth: 2,
                pointHoverBackgroundColor: colour,
                pointHoverBorderColor: token('--hc-surface', '#ffffff'),
                pointHitRadius: 24,
                // Each point is an average over the sampling step, so a step
                // line states what was measured; a smooth curve invents motion
                // between samples. Hetzner draws these stepped for the same reason.
                stepped: 'before',
                fill: true
            };
        });

        return new Chart(ctx, {
            type: 'line',
            data: { datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                parsing: false,
                animation: { duration: 300 },
                interaction: { mode: 'index', intersect: false, axis: 'x' },
                layout: { padding: { top: 6, right: 4 } },
                plugins: {
                    // Identity lives in the card legend, which also carries the
                    // current value as text - never colour alone.
                    legend: { display: false },
                    tooltip: {
                        displayColors: true,
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 2,
                        padding: 10,
                        cornerRadius: 8,
                        backgroundColor: token('--hc-text', '#101828'),
                        titleFont: { size: 11, weight: '600' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            title: function (items) { return new Date(items[0].parsed.x).toLocaleString(); },
                            // Value leads, series name follows.
                            label: function (item) {
                                return ' ' + unitFormat(spec.unit, item.parsed.y) + '  ' + item.dataset.label;
                            },
                            labelPointStyle: function () { return { pointStyle: 'line', rotation: 0 }; }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        // Chart.js defaults to bounds:'ticks', which widens the
                        // axis to the next round tick and leaves dead space
                        // before the first sample and after the last.
                        bounds: 'data',
                        border: { color: grid },
                        grid: { color: grid, drawTicks: false },
                        ticks: {
                            color: ink,
                            maxRotation: 0,
                            autoSkipPadding: 24,
                            maxTicksLimit: 8,
                            font: { size: 11 },
                            callback: timeLabel
                        }
                    },
                    y: {
                        beginAtZero: true,
                        // Scale to the data. Pinning CPU to 100% flattens a
                        // typical 1% server into an unreadable line on the floor.
                        grace: '8%',
                        border: { display: false },
                        grid: { color: grid, drawTicks: false },
                        ticks: {
                            color: ink,
                            maxTicksLimit: 5,
                            padding: 8,
                            font: { size: 11 },
                            callback: function (value) { return unitFormat(spec.unit, value); }
                        }
                    }
                }
            },
            plugins: [crosshair]
        });
    }

    /** Legend row: colour key + series name + current value, as text. */
    function legendHtml(spec) {
        return spec.series.map(function (entry, index) {
            var stats = summarise(entry.data);
            return '<div class="hc-legend__item">'
                + '<span class="hc-legend__key" style="background:' + seriesColour(index) + '"></span>'
                + '<span class="hc-legend__name">' + esc(entry.label) + '</span>'
                + '<span class="hc-legend__value">' + esc(unitFormat(spec.unit, stats.last)) + '</span>'
                + '<span class="hc-legend__meta">' + esc(t('average')) + ' ' + esc(unitFormat(spec.unit, stats.avg))
                + ' · ' + esc(t('peak')) + ' ' + esc(unitFormat(spec.unit, stats.max)) + '</span>'
                + '</div>';
        }).join('');
    }

    /** Scale explainer, the way the Hetzner console labels its CPU graph. */
    function chartNote(spec) {
        return spec.unit === 'percent'
            ? '<span class="hc-chartcard__note">' + esc(t('cpu_scale_hint')) + '</span>'
            : '';
    }

    /**
     * Table twin. Downsampled to a readable row count so every value stays
     * reachable without hovering.
     */
    function tableHtml(spec) {
        var rows = spec.series[0].data.length;
        var stride = Math.max(1, Math.ceil(rows / 60));
        var body = '';

        for (var i = 0; i < rows; i += stride) {
            var cells = spec.series.map(function (entry) {
                var point = entry.data[i];
                return '<td>' + esc(point ? unitFormat(spec.unit, point.y) : '-') + '</td>';
            }).join('');

            body += '<tr><td>' + esc(new Date(spec.series[0].data[i].x).toLocaleString()) + '</td>' + cells + '</tr>';
        }

        return '<div class="hc-table-wrap" style="max-height:260px;overflow:auto"><table class="hc-table"><thead><tr>'
            + '<th>' + esc(t('timestamp')) + '</th>'
            + spec.series.map(function (entry) { return '<th>' + esc(entry.label) + '</th>'; }).join('')
            + '</tr></thead><tbody>' + body + '</tbody></table></div>';
    }

    function renderMetrics() {
        var wrap = document.getElementById('hc-charts');
        if (!wrap || !metricPayload) { return; }

        var specs = metricPayload.charts || [];

        Object.keys(charts).forEach(function (key) { charts[key].destroy(); });
        charts = {};

        if (!specs.length) {
            wrap.innerHTML = '<div class="hc-card">' + empty(t('no_data')) + '</div>';
            return;
        }

        wrap.innerHTML = specs.map(function (spec) {
            return '<figure class="hc-card hc-chartcard" data-chart="' + esc(spec.key) + '">'
                + '<figcaption class="hc-chartcard__head">'
                + '<h3>' + esc(spec.title) + chartNote(spec) + '</h3>'
                + '<div class="hc-legend">' + legendHtml(spec) + '</div>'
                + '</figcaption>'
                + (metricTable
                    ? tableHtml(spec)
                    : '<div class="hc-chart"><canvas></canvas></div>')
                + '</figure>';
        }).join('');

        if (metricTable || typeof Chart === 'undefined') {
            if (!metricTable && typeof Chart === 'undefined') {
                wrap.insertAdjacentHTML('afterbegin',
                    '<div class="hc-note hc-note--warn">Chart.js could not be loaded — showing the table view.</div>');
                metricTable = true;
                renderMetrics();
            }
            return;
        }

        specs.forEach(function (spec) {
            var canvas = wrap.querySelector('[data-chart="' + spec.key + '"] canvas');
            if (canvas) { charts[spec.key] = buildChart(canvas, spec); }
        });
    }

    function loadMetrics(silent) {
        var wrap = document.getElementById('hc-charts');
        if (!wrap) { return; }

        // Refetch keeps the frame: dim the existing render, never flash a skeleton.
        if (metricPayload && silent !== false) {
            wrap.classList.add('is-refreshing');
        } else {
            wrap.innerHTML = '<div class="hc-card">' + skeleton(4) + '</div>';
        }

        api('metrics', null, { minutes: metricMinutes }).then(function (result) {
            wrap.classList.remove('is-refreshing');

            if (!result.success) {
                wrap.innerHTML = '<div class="hc-note hc-note--bad">' + esc(result.message || t('request_failed')) + '</div>';
                return;
            }

            metricPayload = result.data;
            renderMetrics();

            var stamp = document.getElementById('hc-metric-stamp');
            if (stamp) {
                stamp.textContent = t('last_updated') + ' ' + new Date().toLocaleTimeString();
            }
        });
    }

    $$('#hc-metric-range button').forEach(function (button) {
        button.addEventListener('click', function () {
            $$('#hc-metric-range button').forEach(function (other) { other.classList.remove('is-active'); });
            button.classList.add('is-active');
            metricMinutes = Number(button.dataset.minutes);
            metricPayload = null;
            loadMetrics(false);
        });
    });

    var tableToggle = document.getElementById('hc-metric-table');
    if (tableToggle) {
        tableToggle.addEventListener('click', function () {
            metricTable = !metricTable;
            tableToggle.setAttribute('aria-pressed', metricTable ? 'true' : 'false');
            tableToggle.classList.toggle('hc-btn--accent', metricTable);
            renderMetrics();
        });
    }

    var metricRefresh = document.getElementById('hc-metric-refresh');
    if (metricRefresh) {
        metricRefresh.addEventListener('click', function () { loadMetrics(true); });
    }

    loaders.graphs = function () {
        loadMetrics(false);

        clearInterval(metricTimer);
        metricTimer = setInterval(function () {
            var visible = !document.hidden && $('.hc-panel[data-panel="graphs"]').classList.contains('is-active');
            if (visible) { loadMetrics(true); }
        }, 60000);
    };

    /* --------------------------------------------------------------- backups */

    function loadBackups() {
        var toggleBox = document.getElementById('hc-backup-toggle');
        var listBox = document.getElementById('hc-backup-list');
        if (!listBox) { return; }

        listBox.innerHTML = skeleton(2);

        api('summary').then(function (result) {
            var on = result.success && result.data && result.data.backups;

            // Backups add 20% to the Hetzner bill, so switching them on is only
            // offered when the product lets the client do it for free.
            if (toggleBox && !OPT.allow_backups_manage) {
                toggleBox.innerHTML = '<span class="hc-chip ' + (on ? 'hc-chip--ok' : '') + '">'
                    + esc(on ? t('backups_enabled') : t('backups_disabled')) + '</span>';
            } else if (toggleBox) {
                toggleBox.innerHTML = '<button type="button" class="hc-btn ' + (on ? 'hc-btn--bad' : 'hc-btn--accent') + '" id="hc-backup-btn">'
                    + esc(on ? t('disable_backups') : t('enable_backups')) + '</button>';

                document.getElementById('hc-backup-btn').addEventListener('click', function () {
                    var go = function () {
                        api('backup_toggle', { enable: on ? '0' : '1' }).then(function (response) {
                            report(response);
                            loadBackups();
                            refreshSummary();
                        });
                    };

                    if (on) {
                        confirmDialog({ title: t('disable_backups'), body: esc(t('confirm_disable_backups')), danger: true })
                            .then(function (ok) { if (ok) { go(); } });
                    } else {
                        go();
                    }
                });
            }

            if (!on) {
                listBox.innerHTML = '<div class="hc-note">' + esc(t('backups_disabled')) + '</div>';
                return;
            }

            api('backups').then(function (backups) {
                var items = (backups.success && backups.data) || [];

                if (!items.length) {
                    listBox.innerHTML = empty(t('no_backups'));
                    return;
                }

                listBox.innerHTML = table(
                    [t('name'), t('created'), t('size'), t('status'), t('actions')],
                    items.map(function (image) {
                        return [
                            esc(image.description),
                            date(image.created),
                            esc(image.size) + ' GB',
                            '<span class="hc-chip ' + (image.status === 'available' ? 'hc-chip--ok' : 'hc-chip--warn') + '">' + esc(image.status) + '</span>',
                            '<button type="button" class="hc-btn hc-btn--sm" data-restore="' + esc(image.id) + '">' + esc(t('restore')) + '</button>'
                        ];
                    })
                );

                bindRestore(listBox);
            });
        });
    }

    function bindRestore(scope) {
        $$('[data-restore]', scope).forEach(function (button) {
            button.addEventListener('click', function () {
                confirmDialog({ title: t('restore'), body: esc(t('confirm_restore')), danger: true, requireWord: 'RESTORE' })
                    .then(function (ok) {
                        if (!ok) { return; }
                        busy(button, true);
                        api('image_restore', { id: button.dataset.restore }).then(function (result) {
                            busy(button, false);
                            report(result);
                            pollStatus(5);
                        });
                    });
            });
        });
    }

    loaders.backups = loadBackups;

    /* ------------------------------------------------------------- snapshots */

    function loadSnapshots() {
        var box = document.getElementById('hc-snapshot-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('snapshots').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_snapshots'));
                return;
            }

            box.innerHTML = table(
                [t('name'), t('created'), t('size'), t('status'), t('actions')],
                items.map(function (image) {
                    return [
                        esc(image.description),
                        date(image.created),
                        esc(image.size) + ' GB',
                        '<span class="hc-chip ' + (image.status === 'available' ? 'hc-chip--ok' : 'hc-chip--warn') + '">' + esc(image.status) + '</span>',
                        '<button type="button" class="hc-btn hc-btn--sm" data-restore="' + esc(image.id) + '">' + esc(t('restore')) + '</button> '
                        + '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-snapdel="' + esc(image.id)
                        + '" data-name="' + esc(image.description) + '">' + esc(t('delete')) + '</button>'
                    ];
                })
            );

            bindRestore(box);

            $$('[data-snapdel]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({
                        title: t('delete'),
                        body: esc(t('confirm_delete', { name: button.dataset.name })),
                        danger: true
                    }).then(function (ok) {
                        if (!ok) { return; }
                        api('snapshot_delete', { id: button.dataset.snapdel }).then(function (result) {
                            report(result);
                            loadSnapshots();
                        });
                    });
                });
            });
        });
    }

    var snapshotCreate = document.getElementById('hc-snapshot-create');
    if (snapshotCreate) {
        snapshotCreate.addEventListener('click', function () {
            var box = openModal('<h3>' + esc(t('create_snapshot')) + '</h3>'
                + '<div class="hc-field"><label>' + esc(t('snapshot_name')) + '</label>'
                + '<input type="text" class="hc-input" id="hc-snap-name" maxlength="100" placeholder="backup-' + new Date().toISOString().slice(0, 10) + '"></div>'
                + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn hc-btn--accent" id="hc-snap-go">' + esc(t('create')) + '</button></div>');

            box.querySelector('#hc-snap-go').addEventListener('click', function () {
                var name = box.querySelector('#hc-snap-name').value;
                closeModal();
                api('snapshot_create', { name: name }).then(function (result) {
                    report(result);
                    loadSnapshots();
                });
            });
        });
    }

    loaders.snapshots = loadSnapshots;

    /* ---------------------------------------------------------- floating IPs */

    function loadFloatingIps() {
        var box = document.getElementById('hc-fip-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('floating_ips').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_floating_ips'));
                return;
            }

            box.innerHTML = table(
                [t('ip_address'), t('protocol'), t('status'), t('reverse_dns'), t('actions')],
                items.map(function (ip) {
                    return [
                        '<code>' + esc(ip.ip) + '</code>',
                        esc(ip.type),
                        ip.server
                            ? '<span class="hc-chip hc-chip--ok">' + esc(t('assigned')) + '</span>'
                            : '<span class="hc-chip">' + esc(t('unassigned')) + '</span>',
                        '<code>' + esc(ip.dns_ptr || '-') + '</code>',
                        '<button type="button" class="hc-btn hc-btn--sm" data-fip-assign="' + esc(ip.id) + '" data-on="' + (ip.server ? '0' : '1') + '">'
                        + esc(ip.server ? t('unassign') : t('assign')) + '</button> '
                        + '<button type="button" class="hc-btn hc-btn--sm" data-fip-rdns="' + esc(ip.id) + '" data-ip="' + esc(ip.ip)
                        + '" data-ptr="' + esc(ip.dns_ptr || '') + '">PTR</button> '
                        + '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-fip-del="' + esc(ip.id)
                        + '" data-name="' + esc(ip.ip) + '">' + esc(t('delete')) + '</button>'
                    ];
                })
            );

            $$('[data-fip-assign]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    busy(button, true);
                    api('fip_assign', { id: button.dataset.fipAssign, assign: button.dataset.on }).then(function (result) {
                        report(result);
                        loadFloatingIps();
                    });
                });
            });

            $$('[data-fip-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({
                        title: t('delete'),
                        body: esc(t('confirm_delete', { name: button.dataset.name })),
                        danger: true
                    }).then(function (ok) {
                        if (!ok) { return; }
                        api('fip_delete', { id: button.dataset.fipDel }).then(function (result) {
                            report(result);
                            loadFloatingIps();
                        });
                    });
                });
            });

            $$('[data-fip-rdns]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    var modal = openModal('<h3>' + esc(t('reverse_dns')) + ' — ' + esc(button.dataset.ip) + '</h3>'
                        + '<div class="hc-field"><label>' + esc(t('hostname')) + '</label>'
                        + '<input type="text" class="hc-input" id="hc-ptr" value="' + esc(button.dataset.ptr) + '" placeholder="host.example.com"></div>'
                        + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                        + '<button type="button" class="hc-btn hc-btn--accent" id="hc-ptr-go">' + esc(t('set_rdns')) + '</button></div>');

                    modal.querySelector('#hc-ptr-go').addEventListener('click', function () {
                        var hostname = modal.querySelector('#hc-ptr').value;
                        closeModal();
                        api('fip_rdns', { id: button.dataset.fipRdns, ip: button.dataset.ip, hostname: hostname })
                            .then(function (result) { report(result); loadFloatingIps(); });
                    });
                });
            });
        });
    }

    var fipCreate = document.getElementById('hc-fip-create');
    if (fipCreate) {
        fipCreate.addEventListener('click', function () {
            var protocol = OPT.floating_protocol || 'ipv4';
            var picker = protocol === 'both'
                ? '<div class="hc-field"><label>' + esc(t('protocol')) + '</label><select class="hc-select" id="hc-fip-proto">'
                    + '<option value="ipv4">IPv4</option><option value="ipv6">IPv6</option></select></div>'
                : '';
            var price = Number(CFG.floatingPrice) > 0
                ? '<div class="hc-note">' + esc(t('price')) + ': ' + esc(CFG.floatingPrice) + ' ' + esc(CFG.currency) + '</div>'
                : '';

            var modal = openModal('<h3>' + esc(t('create_floating_ip')) + '</h3>' + price + picker
                + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn hc-btn--accent" id="hc-fip-go">' + esc(t('create')) + '</button></div>');

            modal.querySelector('#hc-fip-go').addEventListener('click', function () {
                var chosen = modal.querySelector('#hc-fip-proto');
                closeModal();
                api('fip_order', { protocol: chosen ? chosen.value : protocol }).then(function (result) {
                    report(result);
                    loadFloatingIps();
                });
            });
        });
    }

    /* ------------------------------------------------------------ server PTR */

    function loadRdns() {
        var box = document.getElementById('hc-rdns');
        if (!box) { return; }

        api('summary').then(function (result) {
            if (!result.success || !result.data || !result.data.available) {
                box.innerHTML = empty(t('server_missing'));
                return;
            }

            var data = result.data;
            var rows = [];

            if (data.ipv4) {
                rows.push({ ip: data.ipv4, ptr: data.ptr, label: 'IPv4' });
            }
            if (data.ipv6) {
                rows.push({ ip: data.ipv6, ptr: data.ptr6, label: 'IPv6' });
            }

            box.innerHTML = rows.map(function (row, index) {
                return '<div class="hc-field"><label>' + esc(row.label) + ' — ' + esc(row.ip) + '</label>'
                    + '<div class="hc-copy"><input type="text" class="hc-input" id="hc-srv-ptr-' + index + '" value="' + esc(row.ptr || '') + '" placeholder="host.example.com">'
                    + '<button type="button" class="hc-btn hc-btn--accent" data-srv-ptr="' + index + '" data-ip="' + esc(row.ip) + '">' + esc(t('set_rdns')) + '</button></div></div>';
            }).join('');

            $$('[data-srv-ptr]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById('hc-srv-ptr-' + button.dataset.srvPtr);
                    busy(button, true);
                    api('rdns', { ip: button.dataset.ip, hostname: input.value }).then(function (response) {
                        busy(button, false);
                        report(response);
                    });
                });
            });
        });
    }

    /* -------------------------------------------------------------- networks */

    function loadNetworks() {
        var box = document.getElementById('hc-net-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('networks').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_networks'));
                return;
            }

            box.innerHTML = items.map(function (network) {
                var subnets = network.subnets.map(function (subnet) {
                    return '<tr><td><code>' + esc(subnet.ip_range) + '</code></td><td>' + esc(subnet.network_zone) + '</td>'
                        + '<td>' + esc(subnet.gateway) + '</td><td class="hc-right">'
                        + '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-sub-del="' + esc(network.id)
                        + '" data-range="' + esc(subnet.ip_range) + '">' + esc(t('delete')) + '</button></td></tr>';
                }).join('');

                var routes = network.routes.map(function (route) {
                    return '<tr><td><code>' + esc(route.destination) + '</code></td><td><code>' + esc(route.gateway) + '</code></td>'
                        + '<td class="hc-right"><button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-route-del="' + esc(network.id)
                        + '" data-dest="' + esc(route.destination) + '" data-gw="' + esc(route.gateway) + '">' + esc(t('delete')) + '</button></td></tr>';
                }).join('');

                return '<div class="hc-note" style="display:grid;gap:10px">'
                    + '<div class="hc-card__head"><strong>' + esc(network.name) + ' <code>' + esc(network.ip_range) + '</code></strong>'
                    + '<span>' + (network.attached
                        ? '<span class="hc-chip hc-chip--ok">' + esc(t('attached')) + '</span>'
                        : '<span class="hc-chip">' + esc(t('not_attached')) + '</span>') + '</span></div>'
                    + '<div class="hc-row">'
                    + '<button type="button" class="hc-btn hc-btn--sm" data-net-attach="' + esc(network.id) + '" data-on="' + (network.attached ? '0' : '1') + '">'
                    + esc(network.attached ? t('detach') : t('attach')) + '</button>'
                    + '<button type="button" class="hc-btn hc-btn--sm" data-sub-add="' + esc(network.id) + '">' + esc(t('add_subnet')) + '</button>'
                    + '<button type="button" class="hc-btn hc-btn--sm" data-route-add="' + esc(network.id) + '">' + esc(t('add_route')) + '</button>'
                    + '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-net-del="' + esc(network.id) + '" data-name="' + esc(network.name) + '">'
                    + esc(t('delete')) + '</button>'
                    + '</div>'
                    + (subnets ? '<table class="hc-table"><thead><tr><th>' + esc(t('subnets')) + '</th><th>' + esc(t('network_zone'))
                        + '</th><th>' + esc(t('gateway')) + '</th><th class="hc-right"></th></tr></thead><tbody>' + subnets + '</tbody></table>' : '')
                    + (routes ? '<table class="hc-table"><thead><tr><th>' + esc(t('destination')) + '</th><th>' + esc(t('gateway'))
                        + '</th><th class="hc-right"></th></tr></thead><tbody>' + routes + '</tbody></table>' : '')
                    + '</div>';
            }).join('');

            $$('[data-net-attach]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    busy(button, true);
                    api('net_attach', { id: button.dataset.netAttach, attach: button.dataset.on })
                        .then(function (response) { report(response); loadNetworks(); });
                });
            });

            $$('[data-net-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({ title: t('delete'), body: esc(t('confirm_delete', { name: button.dataset.name })), danger: true })
                        .then(function (ok) {
                            if (!ok) { return; }
                            api('net_delete', { id: button.dataset.netDel }).then(function (response) {
                                report(response); loadNetworks();
                            });
                        });
                });
            });

            $$('[data-sub-add]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    var modal = openModal('<h3>' + esc(t('add_subnet')) + '</h3>'
                        + '<div class="hc-field"><label>' + esc(t('ip_range')) + ' (' + esc(t('optional')) + ')</label>'
                        + '<input type="text" class="hc-input" id="hc-sub-range" placeholder="10.0.1.0/24"></div>'
                        + '<div class="hc-field"><label>' + esc(t('network_zone')) + '</label>'
                        + '<input type="text" class="hc-input" id="hc-sub-zone" value="' + esc(CFG.networkZone || 'eu-central') + '"></div>'
                        + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                        + '<button type="button" class="hc-btn hc-btn--accent" id="hc-sub-go">' + esc(t('save')) + '</button></div>');

                    modal.querySelector('#hc-sub-go').addEventListener('click', function () {
                        var payload = {
                            id: button.dataset.subAdd,
                            add: '1',
                            ip_range: modal.querySelector('#hc-sub-range').value,
                            network_zone: modal.querySelector('#hc-sub-zone').value
                        };
                        closeModal();
                        api('net_subnet', payload).then(function (response) { report(response); loadNetworks(); });
                    });
                });
            });

            $$('[data-sub-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({ title: t('delete'), body: esc(t('confirm_delete', { name: button.dataset.range })), danger: true })
                        .then(function (ok) {
                            if (!ok) { return; }
                            api('net_subnet', { id: button.dataset.subDel, add: '0', ip_range: button.dataset.range })
                                .then(function (response) { report(response); loadNetworks(); });
                        });
                });
            });

            $$('[data-route-add]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    var modal = openModal('<h3>' + esc(t('add_route')) + '</h3>'
                        + '<div class="hc-field"><label>' + esc(t('destination')) + '</label>'
                        + '<input type="text" class="hc-input" id="hc-route-dest" placeholder="10.100.1.0/24"></div>'
                        + '<div class="hc-field"><label>' + esc(t('gateway')) + '</label>'
                        + '<input type="text" class="hc-input" id="hc-route-gw" placeholder="10.0.1.6"></div>'
                        + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                        + '<button type="button" class="hc-btn hc-btn--accent" id="hc-route-go">' + esc(t('save')) + '</button></div>');

                    modal.querySelector('#hc-route-go').addEventListener('click', function () {
                        var payload = {
                            id: button.dataset.routeAdd,
                            add: '1',
                            destination: modal.querySelector('#hc-route-dest').value,
                            gateway: modal.querySelector('#hc-route-gw').value
                        };
                        closeModal();
                        api('net_route', payload).then(function (response) { report(response); loadNetworks(); });
                    });
                });
            });

            $$('[data-route-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    api('net_route', {
                        id: button.dataset.routeDel,
                        add: '0',
                        destination: button.dataset.dest,
                        gateway: button.dataset.gw
                    }).then(function (response) { report(response); loadNetworks(); });
                });
            });
        });
    }

    var netCreate = document.getElementById('hc-net-create');
    if (netCreate) {
        netCreate.addEventListener('click', function () {
            var modal = openModal('<h3>' + esc(t('create_network')) + '</h3>'
                + '<div class="hc-field"><label>' + esc(t('network_name')) + '</label>'
                + '<input type="text" class="hc-input" id="hc-net-name" placeholder="internal"></div>'
                + '<div class="hc-field"><label>' + esc(t('ip_range')) + '</label>'
                + '<input type="text" class="hc-input" id="hc-net-range" value="10.0.0.0/16"></div>'
                + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn hc-btn--accent" id="hc-net-go">' + esc(t('create')) + '</button></div>');

            modal.querySelector('#hc-net-go').addEventListener('click', function () {
                var payload = {
                    name: modal.querySelector('#hc-net-name').value,
                    ip_range: modal.querySelector('#hc-net-range').value
                };
                closeModal();
                api('net_create', payload).then(function (response) { report(response); loadNetworks(); });
            });
        });
    }

    /* --------------------------------------------------------------- volumes */

    function loadVolumes() {
        var box = document.getElementById('hc-volume-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('volumes').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('none'));
                return;
            }

            box.innerHTML = table(
                [t('name'), t('size'), t('type'), t('location'), t('status')],
                items.map(function (volume) {
                    return [
                        esc(volume.name),
                        esc(volume.size) + ' GB',
                        esc(volume.format || '-'),
                        esc(volume.location),
                        volume.linux_device
                            ? '<code>' + esc(volume.linux_device) + '</code>'
                            : '<span class="hc-chip">' + esc(t('not_attached')) + '</span>'
                    ];
                })
            );
        });
    }

    loaders.network = function () {
        loadFloatingIps();
        loadRdns();
        loadVolumes();
        loadNetworks();
    };

    /* -------------------------------------------------------------- firewall */

    var PROTOCOLS = ['tcp', 'udp', 'icmp', 'esp', 'gre'];

    function ruleRow(rule) {
        rule = rule || { direction: 'in', protocol: 'tcp', port: '', ips: '', description: '' };
        var ips = rule.source_ips || rule.destination_ips || [];

        return '<tr>'
            + '<td><select class="hc-select" data-rule="direction">'
            + '<option value="in"' + (rule.direction === 'in' ? ' selected' : '') + '>' + esc(t('inbound')) + '</option>'
            + '<option value="out"' + (rule.direction === 'out' ? ' selected' : '') + '>' + esc(t('outbound')) + '</option>'
            + '</select></td>'
            + '<td><select class="hc-select" data-rule="protocol">'
            + PROTOCOLS.map(function (protocol) {
                return '<option value="' + protocol + '"' + (rule.protocol === protocol ? ' selected' : '') + '>' + protocol.toUpperCase() + '</option>';
            }).join('')
            + '</select></td>'
            + '<td><input type="text" class="hc-input" data-rule="port" value="' + esc(rule.port || '') + '" placeholder="80 / 8000-9000 / any"></td>'
            + '<td><input type="text" class="hc-input" data-rule="ips" value="' + esc(Array.isArray(ips) ? ips.join(', ') : ips) + '" placeholder="0.0.0.0/0, ::/0"></td>'
            + '<td><input type="text" class="hc-input" data-rule="description" value="' + esc(rule.description || '') + '"></td>'
            + '<td class="hc-right"><button type="button" class="hc-icon-btn" data-rule-remove><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button></td>'
            + '</tr>';
    }

    function openRuleEditor(firewall) {
        var head = '<tr><th>' + esc(t('direction')) + '</th><th>' + esc(t('protocol')) + '</th><th>' + esc(t('port'))
            + '</th><th>' + esc(t('source_ips')) + ' / ' + esc(t('destination_ips')) + '</th><th>' + esc(t('description')) + '</th><th></th></tr>';

        var modal = openModal('<h3>' + esc(t('rules')) + ' — ' + esc(firewall.name) + '</h3>'
            + '<div class="hc-table-wrap"><table class="hc-table"><thead>' + head + '</thead>'
            + '<tbody id="hc-rule-body">' + (firewall.rules.length ? firewall.rules.map(ruleRow).join('') : ruleRow()) + '</tbody></table></div>'
            + '<button type="button" class="hc-btn hc-btn--sm" id="hc-rule-add">' + esc(t('add_rule')) + '</button>'
            + '<div class="hc-muted">' + esc(t('any_port')) + ': "any" — ' + esc(t('inbound')) + ' ' + OPT.fw_in_limit
            + ' / ' + esc(t('outbound')) + ' ' + OPT.fw_out_limit + '</div>'
            + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
            + '<button type="button" class="hc-btn hc-btn--accent" id="hc-rule-save">' + esc(t('save')) + '</button></div>');

        var body = modal.querySelector('#hc-rule-body');

        modal.querySelector('#hc-rule-add').addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', ruleRow());
        });

        body.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-rule-remove]');
            if (remove) { remove.closest('tr').remove(); }
        });

        modal.querySelector('#hc-rule-save').addEventListener('click', function () {
            var rules = Array.prototype.map.call(body.querySelectorAll('tr'), function (row) {
                var read = function (field) {
                    var node = row.querySelector('[data-rule="' + field + '"]');
                    return node ? node.value.trim() : '';
                };
                return {
                    direction: read('direction'),
                    protocol: read('protocol'),
                    port: read('port'),
                    ips: read('ips').split(',').map(function (value) { return value.trim(); }).filter(Boolean),
                    description: read('description')
                };
            });

            closeModal();
            api('fw_rules', { id: firewall.id, rules: JSON.stringify(rules) }).then(function (result) {
                report(result);
                loadFirewalls();
            });
        });
    }

    function loadFirewalls() {
        var box = document.getElementById('hc-fw-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('firewalls').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_firewalls'));
                return;
            }

            box.innerHTML = table(
                [t('firewall_name'), t('rules'), t('status'), t('actions')],
                items.map(function (firewall) {
                    return [
                        esc(firewall.name),
                        String(firewall.rules.length),
                        firewall.applied
                            ? '<span class="hc-chip hc-chip--ok">' + esc(t('applied')) + '</span>'
                            : '<span class="hc-chip">' + esc(t('not_applied')) + '</span>',
                        '<button type="button" class="hc-btn hc-btn--sm" data-fw-rules="' + esc(firewall.id) + '">' + esc(t('rules')) + '</button> '
                        + '<button type="button" class="hc-btn hc-btn--sm" data-fw-apply="' + esc(firewall.id) + '" data-on="' + (firewall.applied ? '0' : '1') + '">'
                        + esc(firewall.applied ? t('remove_from_server') : t('apply_to_server')) + '</button> '
                        + '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-fw-del="' + esc(firewall.id)
                        + '" data-name="' + esc(firewall.name) + '">' + esc(t('delete')) + '</button>'
                    ];
                })
            );

            $$('[data-fw-rules]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    var firewall = items.filter(function (entry) { return String(entry.id) === button.dataset.fwRules; })[0];
                    if (firewall) { openRuleEditor(firewall); }
                });
            });

            $$('[data-fw-apply]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    busy(button, true);
                    api('fw_apply', { id: button.dataset.fwApply, apply: button.dataset.on })
                        .then(function (response) { report(response); loadFirewalls(); });
                });
            });

            $$('[data-fw-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({ title: t('delete'), body: esc(t('confirm_delete', { name: button.dataset.name })), danger: true })
                        .then(function (ok) {
                            if (!ok) { return; }
                            api('fw_delete', { id: button.dataset.fwDel }).then(function (response) {
                                report(response); loadFirewalls();
                            });
                        });
                });
            });
        });
    }

    var fwCreate = document.getElementById('hc-fw-create');
    if (fwCreate) {
        fwCreate.addEventListener('click', function () {
            var modal = openModal('<h3>' + esc(t('create_firewall')) + '</h3>'
                + '<div class="hc-field"><label>' + esc(t('firewall_name')) + '</label>'
                + '<input type="text" class="hc-input" id="hc-fw-name" placeholder="web"></div>'
                + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn hc-btn--accent" id="hc-fw-go">' + esc(t('create')) + '</button></div>');

            modal.querySelector('#hc-fw-go').addEventListener('click', function () {
                var name = modal.querySelector('#hc-fw-name').value;
                closeModal();
                api('fw_create', { name: name }).then(function (response) { report(response); loadFirewalls(); });
            });
        });
    }

    loaders.firewall = loadFirewalls;

    /* ------------------------------------------------------------------- ISO */

    var isoCache = [];

    function paintIsoList(filter) {
        var box = document.getElementById('hc-iso-list');
        if (!box) { return; }

        var needle = (filter || '').toLowerCase();
        var items = isoCache.filter(function (iso) {
            return !needle || (iso.description + ' ' + iso.name).toLowerCase().indexOf(needle) !== -1;
        }).slice(0, 200);

        if (!items.length) {
            box.innerHTML = empty(t('no_data'));
            return;
        }

        box.innerHTML = items.map(function (iso) {
            return '<button type="button" class="hc-option" data-iso="' + esc(iso.name) + '">'
                + '<span>' + esc(iso.description || iso.name) + '</span>'
                + '<small>' + esc(iso.architecture || '') + '</small></button>';
        }).join('');

        $$('[data-iso]', box).forEach(function (button) {
            button.addEventListener('click', function () {
                busy(button, true);
                api('iso_attach', { iso: button.dataset.iso }).then(function (result) {
                    busy(button, false);
                    report(result);
                    loadIso();
                    refreshSummary();
                });
            });
        });
    }

    function loadIso() {
        var current = document.getElementById('hc-iso-current');
        if (!current) { return; }

        api('summary').then(function (result) {
            var mounted = result.success && result.data ? result.data.iso : '';

            current.innerHTML = mounted
                ? '<div class="hc-note hc-note--warn"><strong>' + esc(t('mounted_iso')) + ':</strong> ' + esc(mounted)
                    + ' <button type="button" class="hc-btn hc-btn--sm hc-btn--bad" id="hc-iso-detach">' + esc(t('unmount_iso')) + '</button></div>'
                : '<div class="hc-note">' + esc(t('no_iso')) + '</div>';

            var detach = document.getElementById('hc-iso-detach');
            if (detach) {
                detach.addEventListener('click', function () {
                    busy(detach, true);
                    api('iso_detach', {}).then(function (response) {
                        report(response);
                        loadIso();
                        refreshSummary();
                    });
                });
            }
        });

        if (isoCache.length) {
            paintIsoList(document.getElementById('hc-iso-search').value);
            return;
        }

        var listBox = document.getElementById('hc-iso-list');
        listBox.innerHTML = skeleton(4);

        api('isos').then(function (result) {
            isoCache = (result.success && result.data) || [];
            paintIsoList('');
        });
    }

    var isoSearch = document.getElementById('hc-iso-search');
    if (isoSearch) {
        isoSearch.addEventListener('input', function () { paintIsoList(isoSearch.value); });
    }

    loaders.iso = loadIso;

    /* ---------------------------------------------------------------- rescue */

    function loadRescue() {
        var box = document.getElementById('hc-rescue');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('summary').then(function (result) {
            var on = result.success && result.data && result.data.rescue;

            box.innerHTML = (on
                ? '<div class="hc-note hc-note--warn">' + esc(t('rescue_enabled')) + '. ' + esc(t('rescue_reboot_hint')) + '</div>'
                : '<div class="hc-note">' + esc(t('disabled')) + '</div>')
                + '<div class="hc-row" style="margin-top:10px">'
                + (on
                    ? '<button type="button" class="hc-btn hc-btn--bad" id="hc-rescue-off">' + esc(t('disable_rescue')) + '</button>'
                    : '<select class="hc-select" id="hc-rescue-type" style="max-width:180px"><option value="linux64">linux64</option><option value="linux32">linux32</option></select>'
                        + '<button type="button" class="hc-btn hc-btn--accent" id="hc-rescue-on">' + esc(t('enable_rescue')) + '</button>')
                + '</div>';

            var enable = document.getElementById('hc-rescue-on');
            if (enable) {
                enable.addEventListener('click', function () {
                    busy(enable, true);
                    api('rescue', { enable: '1', type: document.getElementById('hc-rescue-type').value }).then(function (response) {
                        busy(enable, false);
                        report(response);
                        if (response.success && response.data && response.data.password) {
                            openModal('<h3>' + esc(t('rescue_password')) + '</h3>'
                                + '<div class="hc-note hc-note--ok"><code>' + esc(response.data.password) + '</code></div>'
                                + '<p class="hc-muted">' + esc(t('rescue_reboot_hint')) + '</p>'
                                + '<div class="hc-modal__foot"><button type="button" class="hc-btn hc-btn--accent" data-modal-close>' + esc(t('close')) + '</button></div>');
                        }
                        loadRescue();
                        refreshSummary();
                    });
                });
            }

            var disable = document.getElementById('hc-rescue-off');
            if (disable) {
                disable.addEventListener('click', function () {
                    busy(disable, true);
                    api('rescue', { enable: '0' }).then(function (response) {
                        busy(disable, false);
                        report(response);
                        loadRescue();
                        refreshSummary();
                    });
                });
            }
        });
    }

    loaders.rescue = loadRescue;

    /* --------------------------------------------------------------- rebuild */

    var imageCache = [];
    var chosenImage = null;

    function paintImages(filter) {
        var box = document.getElementById('hc-image-list');
        if (!box) { return; }

        var needle = (filter || '').toLowerCase();
        var items = imageCache.filter(function (image) {
            return !needle || (image.description + ' ' + image.name).toLowerCase().indexOf(needle) !== -1;
        });

        if (!items.length) {
            box.innerHTML = empty(t('no_data'));
            return;
        }

        box.innerHTML = items.map(function (image) {
            return '<button type="button" class="hc-option' + (chosenImage === image.name ? ' is-selected' : '')
                + '" data-image="' + esc(image.name) + '"><span>' + esc(image.description) + '</span>'
                + '<small>' + esc(image.type === 'app' ? 'app' : image.architecture) + '</small></button>';
        }).join('');

        $$('[data-image]', box).forEach(function (button) {
            button.addEventListener('click', function () {
                chosenImage = button.dataset.image;
                $$('[data-image]', box).forEach(function (other) { other.classList.remove('is-selected'); });
                button.classList.add('is-selected');
                document.getElementById('hc-rebuild-go').disabled = false;
            });
        });
    }

    function loadImages() {
        var box = document.getElementById('hc-image-list');
        if (!box) { return; }

        box.innerHTML = skeleton(4);

        api('os_images').then(function (result) {
            imageCache = (result.success && result.data) || [];
            paintImages('');
        });
    }

    var imageSearch = document.getElementById('hc-image-search');
    if (imageSearch) {
        imageSearch.addEventListener('input', function () { paintImages(imageSearch.value); });
    }

    var rebuildGo = document.getElementById('hc-rebuild-go');
    if (rebuildGo) {
        rebuildGo.addEventListener('click', function () {
            if (!chosenImage) { return; }

            confirmDialog({
                title: t('rebuild'),
                body: esc(t('rebuild_warning')) + '<br><br><strong>' + esc(chosenImage) + '</strong>',
                danger: true,
                requireWord: 'REBUILD'
            }).then(function (ok) {
                if (!ok) { return; }
                busy(rebuildGo, true);
                api('rebuild', { image: chosenImage }).then(function (result) {
                    busy(rebuildGo, false);
                    report(result);
                    if (result.success && result.data && result.data.password) {
                        if (passField) { passField.value = result.data.password; }
                        openModal('<h3>' + esc(t('password')) + '</h3>'
                            + '<div class="hc-note hc-note--ok"><code>' + esc(result.data.password) + '</code></div>'
                            + '<div class="hc-modal__foot"><button type="button" class="hc-btn hc-btn--accent" data-modal-close>' + esc(t('close')) + '</button></div>');
                    }
                    pollStatus(6);
                });
            });
        });
    }

    loaders.rebuild = loadImages;

    /* ------------------------------------------------------------- ssh keys */

    function loadSshKeys() {
        var box = document.getElementById('hc-ssh-list');
        if (!box) { return; }

        box.innerHTML = skeleton(2);

        api('ssh_keys').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_ssh_keys'));
                return;
            }

            box.innerHTML = table(
                [t('key_name'), t('fingerprint'), t('created'), t('actions')],
                items.map(function (key) {
                    return [
                        esc(key.name),
                        '<code>' + esc(key.fingerprint) + '</code>',
                        date(key.created),
                        '<button type="button" class="hc-btn hc-btn--sm hc-btn--bad" data-ssh-del="' + esc(key.id)
                        + '" data-name="' + esc(key.name) + '">' + esc(t('delete')) + '</button>'
                    ];
                })
            );

            $$('[data-ssh-del]', box).forEach(function (button) {
                button.addEventListener('click', function () {
                    confirmDialog({ title: t('delete'), body: esc(t('confirm_delete', { name: button.dataset.name })), danger: true })
                        .then(function (ok) {
                            if (!ok) { return; }
                            api('ssh_delete', { id: button.dataset.sshDel }).then(function (response) {
                                report(response); loadSshKeys();
                            });
                        });
                });
            });
        });
    }

    var sshAdd = document.getElementById('hc-ssh-add');
    if (sshAdd) {
        sshAdd.addEventListener('click', function () {
            var modal = openModal('<h3>' + esc(t('add_ssh_key')) + '</h3>'
                + '<div class="hc-field"><label>' + esc(t('key_name')) + '</label>'
                + '<input type="text" class="hc-input" id="hc-key-name" placeholder="laptop"></div>'
                + '<div class="hc-field"><label>' + esc(t('public_key')) + '</label>'
                + '<textarea class="hc-textarea" id="hc-key-body" placeholder="ssh-ed25519 AAAA..."></textarea></div>'
                + '<div class="hc-modal__foot"><button type="button" class="hc-btn" data-modal-close>' + esc(t('cancel')) + '</button>'
                + '<button type="button" class="hc-btn hc-btn--accent" id="hc-key-go">' + esc(t('save')) + '</button></div>');

            modal.querySelector('#hc-key-go').addEventListener('click', function () {
                var payload = {
                    name: modal.querySelector('#hc-key-name').value,
                    public_key: modal.querySelector('#hc-key-body').value
                };
                closeModal();
                api('ssh_add', payload).then(function (response) { report(response); loadSshKeys(); });
            });
        });
    }

    loaders.ssh = loadSshKeys;

    /* ---------------------------------------------------------------- addons */

    function loadAddons() {
        var box = document.getElementById('hc-addons');
        if (!box) { return; }

        box.innerHTML = skeleton(3);

        api('addons').then(function (result) {
            var data = (result.success && result.data) || {};
            var items = data.items || [];

            if (!items.length) {
                box.innerHTML = empty(t('addons_none'));
                return;
            }

            box.innerHTML = table(
                [t('name'), t('addons_current'), t('addons_options'), t('per_month')],
                items.map(function (item) {
                    var current = item.current !== '' ? item.current : t('none');

                    var choices = item.choices.map(function (choice) {
                        return esc(choice.label);
                    }).join(', ');

                    var prices = item.choices
                        .filter(function (choice) { return choice.monthly > 0; })
                        .map(function (choice) {
                            return esc(choice.label) + ': ' + (data.prefix || '')
                                + choice.monthly.toFixed(2) + (data.suffix || '');
                        }).join('<br>');

                    return [
                        esc(item.name) + (item.quantity ? ' <span class="hc-chip">' + item.min + '–' + item.max + '</span>' : ''),
                        '<strong>' + esc(current) + '</strong>',
                        '<span class="hc-muted">' + (item.quantity ? esc(t('addons_per_unit')) : choices) + '</span>',
                        prices || '<span class="hc-muted">' + esc(t('addons_included')) + '</span>'
                    ];
                })
            );
        });
    }

    loaders.addons = loadAddons;

    /* --------------------------------------------------------------- history */

    function loadHistory() {
        var box = document.getElementById('hc-history');
        if (!box) { return; }

        box.innerHTML = skeleton(3);

        api('history').then(function (result) {
            var items = (result.success && result.data) || [];

            if (!items.length) {
                box.innerHTML = empty(t('no_history'));
                return;
            }

            var chipFor = function (status) {
                if (status === 'success') { return 'hc-chip--ok'; }
                if (status === 'error') { return 'hc-chip--bad'; }
                return 'hc-chip--warn';
            };

            box.innerHTML = table(
                [t('command'), t('status'), t('started'), t('finished'), t('progress')],
                items.map(function (action) {
                    return [
                        '<code>' + esc(action.command) + '</code>'
                        + (action.error ? '<br><small class="hc-muted">' + esc(action.error) + '</small>' : ''),
                        '<span class="hc-chip ' + chipFor(action.status) + '">' + esc(action.status) + '</span>',
                        date(action.started),
                        date(action.finished),
                        esc(action.progress) + '%'
                    ];
                })
            );
        });
    }

    var historyRefresh = document.getElementById('hc-history-refresh');
    if (historyRefresh) {
        historyRefresh.addEventListener('click', loadHistory);
    }

    loaders.history = loadHistory;

    /* ------------------------------------------------------------------ boot */

    if (CFG.poll > 0) {
        pollStatus(1);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            clearTimeout(pollTimer);
        } else if (CFG.poll > 0) {
            pollStatus(1);
        }
    });
}());
