<?php
/**
 * Translation loader.
 *
 * Language files live in lang/<whmcs-language-name>.php and use the plain
 * WHMCS format:  $_LANG['key'] = 'Value';
 * Missing keys automatically fall back to English, so a translation file only
 * has to contain the strings it actually translates.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Right-to-left languages (the client area container gets dir="rtl").
 */
function hetznercloud_RtlLanguages()
{
    return ['arabic', 'farsi', 'persian', 'hebrew', 'urdu'];
}

/**
 * Every shipped translation, as file name => display name.
 */
function hetznercloud_AvailableLanguages()
{
    $languages = [];

    foreach (glob(__DIR__ . '/lang/*.php') ?: [] as $file) {
        $name = basename($file, '.php');
        $languages[$name] = ucfirst($name);
    }

    return $languages;
}

/**
 * Work out which language to render in.
 */
function hetznercloud_CurrentLanguage($params = [])
{
    $candidates = [
        $params['clientsdetails']['language'] ?? '',
        $_SESSION['Language'] ?? '',
        function_exists('hetznercloud_Setting') ? hetznercloud_Setting('default_language', '') : '',
    ];

    $available = hetznercloud_AvailableLanguages();

    foreach ($candidates as $candidate) {
        $candidate = strtolower(trim((string) $candidate));

        if ($candidate === 'persian') {
            $candidate = 'farsi';
        }

        if ($candidate && isset($available[$candidate])) {
            return $candidate;
        }
    }

    return 'english';
}

/**
 * Load (and memoise) the active translation table.
 */
function hetznercloud_LoadLang($params = [])
{
    static $loaded = null;
    static $loadedFor = null;

    $language = hetznercloud_CurrentLanguage($params);

    if ($loaded !== null && $loadedFor === $language) {
        return $loaded;
    }

    $strings = [];

    foreach (array_unique(['english', $language]) as $file) {
        $path = __DIR__ . '/lang/' . $file . '.php';

        if (!is_readable($path)) {
            continue;
        }

        $_LANG = [];
        require $path;

        if (is_array($_LANG)) {
            $strings = array_merge($strings, array_filter($_LANG, function ($v) { return is_string($v) && $v !== ''; }));
        }
    }

    $loaded = $strings;
    $loadedFor = $language;

    return $loaded;
}

/**
 * Translate a key.
 *
 * @param string $key
 * @param array  $replace {placeholder} => value
 */
function hetznercloud_L($key, $replace = [])
{
    $strings = hetznercloud_LoadLang();
    $value = $strings[$key] ?? $key;

    foreach ($replace as $placeholder => $replacement) {
        $value = str_replace('{' . $placeholder . '}', (string) $replacement, $value);
    }

    return $value;
}

/**
 * The whole translation table, for handing to the front-end JavaScript.
 */
function hetznercloud_LangArray($params = [])
{
    return hetznercloud_LoadLang($params);
}

/**
 * Is the active language right-to-left?
 */
function hetznercloud_IsRtl($params = [])
{
    return in_array(hetznercloud_CurrentLanguage($params), hetznercloud_RtlLanguages(), true);
}
