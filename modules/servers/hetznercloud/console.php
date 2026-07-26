<?php
/**
 * noVNC console page.
 *
 * The WebSocket URL and console password never reach the browser as query
 * parameters: hetznercloud_Console() puts them in the PHP session and this page
 * reads them back after WHMCS has authenticated the request.
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/storage.php';

$serviceId = (int) ($_GET['service'] ?? 0);

function hetznercloud_ConsoleDeny($message)
{
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Console</title>'
        . '<body style="font:15px/1.5 system-ui,sans-serif;background:#0d1017;color:#e7eaf0;display:grid;place-items:center;height:100vh;margin:0">'
        . '<div style="text-align:center;max-width:420px;padding:24px">'
        . '<h1 style="font-size:18px;margin:0 0 8px">Console unavailable</h1>'
        . '<p style="margin:0;color:#98a2b3">' . htmlspecialchars($message) . '</p></div></body>';
    exit;
}

if (!$serviceId) {
    hetznercloud_ConsoleDeny('No service specified.');
}

$session = $_SESSION['hetznercloud_console'][$serviceId] ?? null;

if (!$session || ($session['expires'] ?? 0) < time()) {
    unset($_SESSION['hetznercloud_console'][$serviceId]);
    hetznercloud_ConsoleDeny('This console session has expired. Open it again from the service page.');
}

// The session entry is only created for a logged-in owner, but re-check that a
// client is still signed in so a stale browser tab cannot be replayed.
$loggedInClient = hetznercloud_CurrentClientId();
$adminId = hetznercloud_CurrentAdminId();

if (!$loggedInClient && !$adminId) {
    hetznercloud_ConsoleDeny('Please log in first.');
}

if ($loggedInClient) {
    try {
        $ownerId = (int) \WHMCS\Database\Capsule::table('tblhosting')->where('id', $serviceId)->value('userid');
    } catch (\Exception $e) {
        $ownerId = 0;
    }

    if ($ownerId !== $loggedInClient) {
        hetznercloud_ConsoleDeny('This service does not belong to your account.');
    }
}

$host = (string) $session['host'];
$password = (string) $session['password'];
$name = (string) ($session['name'] ?? 'server');

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= htmlspecialchars($name) ?> — Console</title>
<style>
    :root { color-scheme: dark; }
    * { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; background: #0d1017; color: #e7eaf0;
        font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    #bar { display: flex; align-items: center; gap: 10px; padding: 8px 14px;
        background: #151a23; border-bottom: 1px solid #262d3a; }
    #bar strong { font-size: 13px; }
    #state { color: #98a2b3; font-size: 12px; margin-inline-end: auto; }
    button { padding: 6px 12px; border: 1px solid #262d3a; border-radius: 8px; background: #1c222d;
        color: #e7eaf0; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
    button:hover:not(:disabled) { background: #262d3a; }
    button:disabled { opacity: .45; cursor: default; }
    button.accent { background: #d50c2d; border-color: #d50c2d; }
    #screen { position: absolute; inset: 45px 0 0; background: #000; }
    #dot { width: 9px; height: 9px; border-radius: 50%; background: #f79009; }
    #dot.ok { background: #12b76a; } #dot.bad { background: #f04438; }

    #pastebox { position: absolute; inset: 45px 0 0; display: none; place-items: center;
        background: rgba(13, 16, 23, .8); backdrop-filter: blur(3px); z-index: 5; }
    #pastebox.open { display: grid; }
    #pastebox > div { width: min(560px, calc(100% - 40px)); display: grid; gap: 10px; padding: 20px;
        background: #151a23; border: 1px solid #262d3a; border-radius: 12px; }
    #pastebox h2 { margin: 0; font-size: 15px; }
    #pastebox p { margin: 0; font-size: 12px; color: #98a2b3; }
    #pastetext { width: 100%; min-height: 130px; padding: 10px; border-radius: 8px;
        border: 1px solid #262d3a; background: #0d1017; color: #e7eaf0;
        font: 13px/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; resize: vertical; }
    #pastebox .row { display: flex; justify-content: flex-end; gap: 8px; }
</style>
</head>
<body>

<div id="bar">
    <span id="dot"></span>
    <strong><?= htmlspecialchars($name) ?></strong>
    <span id="state">Connecting…</span>
    <button id="cad" disabled>Ctrl+Alt+Del</button>
    <button id="clip" disabled>Paste</button>
    <button id="full">Fullscreen</button>
    <button id="again" class="accent" hidden>Reconnect</button>
</div>
<div id="screen"></div>

<div id="pastebox">
    <div>
        <h2>Send text to the console</h2>
        <p>Paste with Ctrl+V (Cmd+V) below and press Send — the text is typed into the console one
           keystroke at a time, which is the only thing a bare VM console understands.</p>
        <textarea id="pastetext" spellcheck="false" autocomplete="off"></textarea>
        <div class="row">
            <button id="pastecancel">Cancel</button>
            <button id="pastesend" class="accent">Send</button>
        </div>
    </div>
</div>

<script type="module">
import RFB from './noVNC/core/rfb.js';

const url = <?= json_encode($host, JSON_UNESCAPED_SLASHES) ?>;
const password = <?= json_encode($password) ?>;

const screen = document.getElementById('screen');
const state = document.getElementById('state');
const dot = document.getElementById('dot');
const cad = document.getElementById('cad');
const clip = document.getElementById('clip');
let rfb = null;

function status(text, tone) {
    state.textContent = text;
    dot.className = tone || '';
}

function connect() {
    document.getElementById('again').hidden = true;
    status('Connecting…');

    rfb = new RFB(screen, url, { credentials: { password: password } });
    rfb.scaleViewport = true;
    rfb.resizeSession = false;
    rfb.clipViewport = true;

    rfb.addEventListener('connect', () => {
        status('Connected', 'ok');
        cad.disabled = false;
        clip.disabled = false;
    });

    rfb.addEventListener('disconnect', (event) => {
        status(event.detail.clean ? 'Disconnected' : 'Connection lost', 'bad');
        cad.disabled = true;
        clip.disabled = true;
        document.getElementById('again').hidden = false;
    });

    rfb.addEventListener('securityfailure', (event) => {
        status('Authentication failed: ' + (event.detail.reason || ''), 'bad');
    });
}

cad.addEventListener('click', () => rfb && rfb.sendCtrlAltDel());

/* ---- paste ------------------------------------------------------------
   clipboardPasteFrom() only hands the text to the guest's clipboard, and a bare
   VM console has no clipboard manager to read it — so nothing appears. Type the
   characters as key events instead, which is what the console actually accepts.
------------------------------------------------------------------------- */

const XK_Return = 0xFF0D;
const XK_Tab = 0xFF09;

function keysymFor(char) {
    const code = char.codePointAt(0);

    if (char === '\n' || char === '\r') { return XK_Return; }
    if (char === '\t') { return XK_Tab; }
    if (code < 0x20) { return null; }          // other control characters: skip

    // Latin-1 maps straight onto keysyms; everything else uses the Unicode range.
    return code < 0x100 ? code : 0x01000000 + code;
}

async function typeText(text) {
    if (!rfb || !text) { return; }

    rfb.focus();

    const chars = Array.from(text.replace(/\r\n/g, '\n'));
    let sent = 0;

    for (const char of chars) {
        const keysym = keysymFor(char);
        if (keysym === null) { continue; }

        rfb.sendKey(keysym, null);
        sent++;

        // Let the guest's tty keep up; a burst of hundreds gets dropped.
        if (sent % 16 === 0) {
            status('Typing… ' + sent + '/' + chars.length, 'normal');
            await new Promise(resolve => setTimeout(resolve, 16));
        }
    }

    status('Connected', 'ok');
}

const pastebox = document.getElementById('pastebox');
const pastetext = document.getElementById('pastetext');

async function openPaste() {
    pastetext.value = '';
    pastebox.classList.add('open');
    pastetext.focus();

    // Pre-fill when the browser allows it; otherwise the user just pastes in.
    try {
        const text = await navigator.clipboard.readText();
        if (text) { pastetext.value = text; pastetext.select(); }
    } catch (e) { /* no permission, or Firefox: the textarea still works */ }
}

function closePaste() {
    pastebox.classList.remove('open');
    if (rfb) { rfb.focus(); }
}

clip.addEventListener('click', openPaste);
document.getElementById('pastecancel').addEventListener('click', closePaste);

document.getElementById('pastesend').addEventListener('click', () => {
    const text = pastetext.value;
    closePaste();
    typeText(text);
});

pastetext.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { closePaste(); }
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        const text = pastetext.value;
        closePaste();
        typeText(text);
    }
});

document.getElementById('full').addEventListener('click', () => {
    if (document.fullscreenElement) { document.exitFullscreen(); }
    else { document.documentElement.requestFullscreen(); }
});

document.getElementById('again').addEventListener('click', () => {
    // Hetzner keeps the session alive for ~10 minutes; after that reopen the
    // console from the service page to get fresh credentials.
    connect();
});

connect();
</script>
</body>
</html>
