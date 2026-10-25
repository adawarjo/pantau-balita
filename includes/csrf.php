<?php
/**
 * Perlindungan CSRF berbasis token sesi.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Hentikan permintaan bila token tidak cocok.
 */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        set_flash('error', 'Sesi formulir kedaluwarsa atau tidak valid. Silakan ulangi.');
        $back = $_SERVER['HTTP_REFERER'] ?? '';
        // Hanya kembali ke halaman internal
        if ($back && parse_url($back, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
            header('Location: ' . $back);
        } else {
            header('Location: ' . url('index.php'));
        }
        exit;
    }
}
