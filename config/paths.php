<?php
/**
 * Application base path (works in subfolders, PHP built-in server, Apache/XAMPP).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Web path prefix for this app (e.g. "" or "/empty").
 */
function appBasePath(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(APP_ROOT);

    if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
        $base = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $base = rtrim($base, '/');
    } else {
        $base = '';
    }

    return $base;
}

/**
 * Build an absolute path URL within this application.
 */
function url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = appBasePath();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

/**
 * Redirect to an app URL and stop execution.
 */
function redirectTo(string $path): void
{
    header('Location: ' . url($path));
    exit();
}

/**
 * Redirect back when referer is same app; otherwise use fallback path.
 */
function redirectBack(string $fallback = 'index.php'): void
{
    $fallbackUrl = url($fallback);

    if (empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $fallbackUrl);
        exit();
    }

    $refPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    if (!$refPath) {
        header('Location: ' . $fallbackUrl);
        exit();
    }

    $base = appBasePath();
    $allowed = ($base === '' && $refPath[0] === '/')
        || ($base !== '' && str_starts_with($refPath, $base));

    header('Location: ' . ($allowed ? $refPath : $fallbackUrl));
    exit();
}
