<?php
/**
 * Konfigurasi koneksi database (PDO).
 * Ubah nilai DB_* sesuai server Anda. Untuk XAMPP/Laragon standar:
 * host 127.0.0.1, user root, password kosong.
 *
 * File ini dilindungi dari akses langsung (lihat config/.htaccess
 * dan pemeriksaan konstanta APP_INIT di bawah).
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Akses ditolak.');
}

define('DB_HOST', getenv('PB_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('PB_DB_PORT') ?: '3306');
define('DB_NAME', getenv('PB_DB_NAME') ?: 'pantau_balita');
define('DB_USER', getenv('PB_DB_USER') ?: 'root');
define('DB_PASS', getenv('PB_DB_PASS') !== false ? getenv('PB_DB_PASS') : '');

/**
 * Mengembalikan instance PDO tunggal.
 * Pesan kesalahan ke pengguna dibuat umum agar tidak membocorkan kredensial.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Samakan zona waktu sesi MySQL dengan PHP agar created_at konsisten
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        error_log('[Pantau Balita] Koneksi database gagal: ' . $e->getMessage());
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><title>Gangguan</title>'
            . '<div style="font-family:sans-serif;max-width:520px;margin:60px auto;padding:24px;border:1px solid #eee;border-radius:12px">'
            . '<h2>Layanan sedang tidak tersedia</h2><p>Aplikasi tidak dapat terhubung ke database. '
            . 'Silakan periksa konfigurasi atau hubungi pengelola aplikasi.</p></div>');
    }
    return $pdo;
}
