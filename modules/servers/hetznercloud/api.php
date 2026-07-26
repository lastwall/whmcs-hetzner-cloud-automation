<?php
/**
 * Hetzner Cloud API client for WHMCS.
 *
 * Thin, generic REST wrapper. Everything below this file talks to Hetzner
 * through hetznercloud_Request() / hetznercloud_Action() only.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/storage.php';

if (!defined('HETZNERCLOUD_API_BASE')) {
    define('HETZNERCLOUD_API_BASE', 'https://api.hetzner.cloud/v1');
}

/* -------------------------------------------------------------------------
 * Credentials
 * ---------------------------------------------------------------------- */

/**
 * Resolve the Hetzner API token.
 *
 * WHMCS decrypts serveraccesshash before handing $params to module functions.
 * Outside of that flow (addon page, cron, console) we fall back to the first
 * enabled hetznercloud server entry.
 */
function hetznercloud_Token($params = [])
{
    if (!empty($params['serveraccesshash'])) {
        return hetznercloud_CleanToken($params['serveraccesshash']);
    }

    static $fallback = null;

    if ($fallback === null) {
        $fallback = '';

        foreach (hetznercloud_ServerEntries() as $server) {
            $token = hetznercloud_UnlockToken($server->accesshash);
            if ($token !== '') {
                $fallback = $token;
                break;
            }
        }
    }

    return $fallback;
}

/**
 * Every WHMCS server entry using this module, active ones first.
 */
function hetznercloud_ServerEntries()
{
    try {
        return Capsule::table('tblservers')
            ->where('type', 'hetznercloud')
            ->where('disabled', 0)
            ->orderBy('active', 'desc')
            ->orderBy('id')
            ->get(['id', 'name', 'accesshash', 'active']);
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Turn a stored access hash into a usable token.
 *
 * WHMCS normally stores it encrypted, but imported or hand-edited rows can hold
 * the plain token, and decrypt() hands back junk in that case. A Hetzner token
 * is 64 alphanumeric characters, which makes the two cases easy to tell apart.
 */
function hetznercloud_UnlockToken($accessHash)
{
    $raw = hetznercloud_CleanToken($accessHash);

    if ($raw === '') {
        return '';
    }

    if (hetznercloud_LooksLikeToken($raw)) {
        return $raw;
    }

    if (function_exists('hetznercloud_Decrypt')) {
        $decrypted = hetznercloud_CleanToken(hetznercloud_Decrypt($accessHash));

        if ($decrypted !== '') {
            return $decrypted;
        }
    }

    return $raw;
}

/**
 * Strip anything that cannot legally sit in an Authorization header.
 * A stray newline pasted with the token makes cURL emit a malformed request,
 * which comes back as an opaque HTTP 400.
 */
function hetznercloud_CleanToken($value)
{
    return trim(preg_replace('/[^\x21-\x7E]/', '', (string) $value));
}

/**
 * Hetzner Cloud API tokens are 64 alphanumeric characters.
 */
function hetznercloud_LooksLikeToken($value)
{
    return (bool) preg_match('/^[A-Za-z0-9]{40,128}$/', (string) $value);
}

/* -------------------------------------------------------------------------
 * Core request
 * ---------------------------------------------------------------------- */

/**
 * How long a single API call may take.
 *
 * Kept below PHP's own execution limit: a cURL timeout longer than
 * max_execution_time means a slow Hetzner response kills the whole page
 * instead of returning an error, which shows up as a blank product page.
 */
function hetznercloud_ApiTimeout()
{
    $configured = function_exists('hetznercloud_Setting')
        ? (int) hetznercloud_Setting('api_timeout', 20)
        : 20;

    $timeout = max(5, min(60, $configured ?: 20));

    $limit = (int) ini_get('max_execution_time');

    if ($limit > 0) {
        // Leave room for the rest of the page to render.
        $timeout = min($timeout, max(5, $limit - 5));
    }

    return $timeout;
}

/**
 * Perform a request against the Hetzner Cloud API.
 *
 * @param array  $params WHMCS module params (used for the token)
 * @param string $method GET|POST|PUT|DELETE
 * @param string $path   e.g. "servers/123/actions/poweron"
 * @param array|null $body JSON body for write requests
 * @param array  $query  Query string parameters
 *
 * @return array{success:bool,code:int,data:array,message:string}
 */
function hetznercloud_Request($params, $method, $path, $body = null, $query = [])
{
    $token = hetznercloud_Token($params);

    if (!$token) {
        return [
            'success' => false,
            'code' => 0,
            'data' => [],
            'message' => 'Hetzner API token is not configured. Set the Access Hash on the server entry.',
        ];
    }

    $method = strtoupper($method);
    $url = HETZNERCLOUD_API_BASE . '/' . ltrim($path, '/');

    if ($query) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: WHMCS-HetznerCloud/' . (defined('HETZNERCLOUD_VERSION') ? HETZNERCLOUD_VERSION : '3.0.0'),
        ],
        CURLOPT_TIMEOUT => hetznercloud_ApiTimeout(),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_NOSIGNAL => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        hetznercloud_LogCall("{$method} {$path}", $body, ['curl_error' => $curlError]);
        return [
            'success' => false,
            'code' => 0,
            'data' => [],
            'message' => 'Connection error: ' . $curlError,
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }

    hetznercloud_LogCall("{$method} {$path}", $body, $data + ['http_code' => $code]);

    if ($code >= 200 && $code < 300) {
        return ['success' => true, 'code' => $code, 'data' => $data, 'message' => ''];
    }

    return [
        'success' => false,
        'code' => $code,
        'data' => $data,
        'message' => hetznercloud_ErrorMessage($data, $code, $raw),
    ];
}

/**
 * Turn a Hetzner error payload into a readable sentence.
 */
function hetznercloud_ErrorMessage($data, $code, $raw = '')
{
    if (isset($data['error']['message'])) {
        $message = $data['error']['message'];

        if (!empty($data['error']['details']['fields'])) {
            $fields = [];
            foreach ($data['error']['details']['fields'] as $field) {
                $fields[] = ($field['name'] ?? '?') . ': ' . implode(', ', (array) ($field['messages'] ?? []));
            }
            $message .= ' (' . implode('; ', $fields) . ')';
        }

        return $message;
    }

    $message = 'Hetzner API returned HTTP ' . $code;

    if ($code === 401 || $code === 403) {
        return $message . ' - the API token was rejected. Generate a Read & Write token in the '
            . 'Hetzner Console (Project > Security > API tokens) and paste it again.';
    }

    // No JSON error object means something other than the API answered
    // (proxy, firewall, captive portal). Show what actually came back.
    $snippet = trim(strip_tags((string) $raw));

    if ($snippet !== '') {
        return $message . ' - ' . mb_substr(preg_replace('/\s+/', ' ', $snippet), 0, 200);
    }

    return $message . ' with an empty response body.';
}

/**
 * Write to the WHMCS module log (Utilities > Logs > Module Log).
 */
function hetznercloud_LogCall($action, $request, $response)
{
    if (!function_exists('logModuleCall')) {
        return;
    }

    logModuleCall(
        'hetznercloud',
        $action,
        is_scalar($request) ? (string) $request : json_encode($request),
        is_scalar($response) ? (string) $response : json_encode($response),
        '',
        ['/Bearer\s+\S+/i']
    );
}

/**
 * GET a collection, following pagination.
 *
 * @param string $key Response key holding the collection, e.g. "servers"
 */
function hetznercloud_Collection($params, $path, $key, $query = [])
{
    $items = [];
    $page = 1;

    do {
        $response = hetznercloud_Request($params, 'GET', $path, null, $query + [
            'page' => $page,
            'per_page' => 50,
        ]);

        if (!$response['success']) {
            return ['success' => false, 'items' => [], 'message' => $response['message']];
        }

        foreach ((array) ($response['data'][$key] ?? []) as $item) {
            $items[] = $item;
        }

        $next = $response['data']['meta']['pagination']['next_page'] ?? null;
        $page = $next;
    } while ($next && $page <= 40);

    return ['success' => true, 'items' => $items, 'message' => ''];
}

/**
 * Fire a server action.
 */
function hetznercloud_Action($params, $serverId, $action, $body = [])
{
    if (!$serverId) {
        return ['success' => false, 'code' => 0, 'data' => [], 'message' => 'Server ID is missing.'];
    }

    return hetznercloud_Request($params, 'POST', "servers/{$serverId}/actions/{$action}", $body ?: new stdClass());
}

/**
 * Block until an action finishes (or the timeout expires).
 *
 * @return bool true when the action reported success
 */
function hetznercloud_WaitAction($params, $actionId, $timeout = 60)
{
    if (!$actionId) {
        return false;
    }

    $deadline = time() + $timeout;

    while (time() < $deadline) {
        $response = hetznercloud_Request($params, 'GET', "actions/{$actionId}");

        if (!$response['success']) {
            return false;
        }

        $status = $response['data']['action']['status'] ?? 'running';

        if ($status === 'success') {
            return true;
        }
        if ($status === 'error') {
            return false;
        }

        sleep(2);
    }

    return false;
}

/* -------------------------------------------------------------------------
 * Cached reference data (server types, images, locations, ISOs, datacenters)
 * ---------------------------------------------------------------------- */

/**
 * Cache directory, created on demand.
 */
function hetznercloud_CacheDir()
{
    $dir = __DIR__ . '/cache';

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Keep the cache unreadable over HTTP on hosts that serve the modules
    // directory directly.
    if (!is_file($dir . '/index.html')) {
        @file_put_contents($dir . '/index.html', '');
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }

    return $dir;
}

/**
 * Read-through file cache.
 *
 * @param string   $key      Cache key
 * @param int      $ttl      Seconds
 * @param callable $callback Returns the fresh value
 */
function hetznercloud_Cached($key, $ttl, callable $callback)
{
    $file = hetznercloud_CacheDir() . '/' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';

    if (is_readable($file) && (time() - filemtime($file)) < $ttl) {
        $cached = json_decode(file_get_contents($file), true);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }
    }

    $fresh = $callback();

    if (is_array($fresh) && $fresh !== []) {
        @file_put_contents($file, json_encode($fresh));
    } elseif (is_readable($file)) {
        // API hiccup: keep serving the stale copy rather than an empty list.
        $stale = json_decode(file_get_contents($file), true);
        if (is_array($stale) && $stale !== []) {
            return $stale;
        }
    }

    return is_array($fresh) ? $fresh : [];
}

/**
 * Drop every cached reference list.
 */
function hetznercloud_ClearCache()
{
    $files = glob(hetznercloud_CacheDir() . '/*.json') ?: [];

    foreach ($files as $file) {
        @unlink($file);
    }

    return count($files);
}

/**
 * All server types, sorted by price.
 */
function hetznercloud_ServerTypes($params = [])
{
    return hetznercloud_Cached('server_types', 86400, function () use ($params) {
        $result = hetznercloud_Collection($params, 'server_types', 'server_types');

        if (!$result['success']) {
            return [];
        }

        $types = [];
        foreach ($result['items'] as $type) {
            if (!empty($type['deprecated']) || !empty($type['deprecation'])) {
                continue;
            }

            $price = 0.0;
            foreach ((array) ($type['prices'] ?? []) as $entry) {
                $monthly = (float) ($entry['price_monthly']['gross'] ?? 0);
                if ($monthly && (!$price || $monthly < $price)) {
                    $price = $monthly;
                }
            }

            $types[] = [
                'id' => $type['id'],
                'name' => $type['name'],
                'description' => $type['description'] ?? $type['name'],
                'cores' => $type['cores'] ?? 0,
                'memory' => $type['memory'] ?? 0,
                'disk' => $type['disk'] ?? 0,
                'cpu_type' => $type['cpu_type'] ?? '',
                'architecture' => $type['architecture'] ?? 'x86',
                'price' => $price,
            ];
        }

        usort($types, function ($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        return $types;
    });
}

/**
 * Locations with city/country metadata.
 */
function hetznercloud_Locations($params = [])
{
    return hetznercloud_Cached('locations', 86400, function () use ($params) {
        $result = hetznercloud_Collection($params, 'locations', 'locations');

        if (!$result['success']) {
            return [];
        }

        $locations = [];
        foreach ($result['items'] as $location) {
            $locations[] = [
                'id' => $location['id'],
                'name' => $location['name'],
                'description' => $location['description'] ?? $location['name'],
                'city' => $location['city'] ?? '',
                'country' => $location['country'] ?? '',
                'network_zone' => $location['network_zone'] ?? '',
            ];
        }

        return $locations;
    });
}

/**
 * Datacenters (needed to know which server types a location can actually run).
 */
function hetznercloud_Datacenters($params = [])
{
    return hetznercloud_Cached('datacenters', 86400, function () use ($params) {
        $result = hetznercloud_Collection($params, 'datacenters', 'datacenters');

        if (!$result['success']) {
            return [];
        }

        $datacenters = [];
        foreach ($result['items'] as $dc) {
            $datacenters[] = [
                'id' => $dc['id'],
                'name' => $dc['name'],
                'location' => $dc['location']['name'] ?? '',
                'available' => $dc['server_types']['available'] ?? [],
            ];
        }

        return $datacenters;
    });
}

/**
 * Public OS / app images.
 */
function hetznercloud_Images($params = [])
{
    return hetznercloud_Cached('images', 43200, function () use ($params) {
        $images = [];

        foreach (['system', 'app'] as $type) {
            $result = hetznercloud_Collection($params, 'images', 'images', [
                'type' => $type,
                'status' => 'available',
                'include_deprecated' => 'false',
                'sort' => 'name:asc',
            ]);

            if (!$result['success']) {
                continue;
            }

            foreach ($result['items'] as $image) {
                if (!empty($image['deprecated']) || !empty($image['deprecation'])) {
                    continue;
                }

                $images[] = [
                    'id' => $image['id'],
                    'name' => $image['name'] ?: (string) $image['id'],
                    'description' => $image['description'] ?? $image['name'],
                    'type' => $image['type'],
                    'os_flavor' => $image['os_flavor'] ?? '',
                    'os_version' => $image['os_version'] ?? '',
                    'architecture' => $image['architecture'] ?? 'x86',
                    'rapid_deploy' => !empty($image['rapid_deploy']),
                ];
            }
        }

        return $images;
    });
}

/**
 * Bootable ISO images.
 */
function hetznercloud_Isos($params = [])
{
    return hetznercloud_Cached('isos', 86400, function () use ($params) {
        $result = hetznercloud_Collection($params, 'isos', 'isos');

        if (!$result['success']) {
            return [];
        }

        $isos = [];
        foreach ($result['items'] as $iso) {
            $isos[] = [
                'id' => $iso['id'],
                'name' => $iso['name'] ?: (string) $iso['id'],
                'description' => $iso['description'] ?? '',
                'type' => $iso['type'] ?? 'public',
                'architecture' => $iso['architecture'] ?? null,
            ];
        }

        usort($isos, function ($a, $b) {
            return strcasecmp($a['description'], $b['description']);
        });

        return $isos;
    });
}

/* -------------------------------------------------------------------------
 * Dropdown helpers
 *
 * Product settings, the configurable option generator and the product creator
 * all build their option strings here, so a value written by one is always
 * selectable in the others.
 * ---------------------------------------------------------------------- */

/**
 * Build a WHMCS dropdown option list ("value|Label", comma separated).
 * Commas are stripped from labels because WHMCS splits the list on them.
 */
function hetznercloud_DropdownOptions(array $pairs)
{
    $entries = [];

    foreach ($pairs as $value => $label) {
        $entries[] = hetznercloud_DropdownValue($value, $label);
    }

    return implode(',', $entries);
}

/**
 * A single "value|Label" entry.
 */
function hetznercloud_DropdownValue($value, $label)
{
    return $value . '|' . trim(str_replace(',', ' ', (string) $label));
}

/**
 * Server types as value => label.
 */
function hetznercloud_TypePairs($params = [])
{
    $pairs = [];

    foreach (hetznercloud_ServerTypes($params) as $type) {
        $pairs[$type['name']] = strtoupper($type['name']) . ' - ' . $type['cores'] . ' vCPU / '
            . $type['memory'] . ' GB RAM / ' . $type['disk'] . ' GB';
    }

    return $pairs;
}

/**
 * Locations as value => label.
 */
function hetznercloud_LocationPairs($params = [])
{
    $pairs = [];

    foreach (hetznercloud_Locations($params) as $location) {
        $pairs[$location['name']] = ($location['city'] ?: $location['description'])
            . ' ' . strtoupper($location['country']) . ' (' . $location['name'] . ')';
    }

    return $pairs;
}

/**
 * System images as value => label.
 */
function hetznercloud_ImagePairs($params = [])
{
    $pairs = [];

    foreach (hetznercloud_Images($params) as $image) {
        if ($image['type'] !== 'system') {
            continue;
        }
        $pairs[$image['name']] = $image['description'];
    }

    return $pairs;
}

/**
 * Hetzner price list (used for the floating IP surcharge display).
 */
function hetznercloud_Pricing($params = [])
{
    return hetznercloud_Cached('pricing', 86400, function () use ($params) {
        $response = hetznercloud_Request($params, 'GET', 'pricing');

        return $response['success'] ? (array) ($response['data']['pricing'] ?? []) : [];
    });
}
