<?php
/**
 * Inisialisasi aplikasi: dimuat pertama kali oleh setiap halaman.
 */
define('APP_INIT', true);
define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/config/config.php';
require APP_ROOT . '/config/database.php';

date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// --- Sesi yang aman ---
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('PANTAUBALITA');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Header keamanan dasar ---
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

require APP_ROOT . '/includes/functions.php';
require APP_ROOT . '/includes/csrf.php';
require APP_ROOT . '/includes/auth.php';
require APP_ROOT . '/includes/disclaimer.php';

// Versi aplikasi = rilis teratas di includes/changelog.php
define('APP_VERSION', app_changelog()[0]['version'] ?? '1.0.0');

// Tangani exception yang lolos tanpa membocorkan detail
set_exception_handler(function (Throwable $e) {
    error_log('[Pantau Balita] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (APP_DEBUG) {
        echo '<pre>' . e((string) $e) . '</pre>';
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><title>Terjadi kesalahan</title>'
        . '<div style="font-family:sans-serif;max-width:520px;margin:60px auto;padding:24px;border:1px solid #eee;border-radius:12px">'
        . '<h2>Terjadi kesalahan</h2><p>Permintaan tidak dapat diproses. Silakan coba lagi.</p>'
        . '<p><a href="' . e(url('dashboard.php')) . '">Kembali ke dashboard</a></p></div>';
});
