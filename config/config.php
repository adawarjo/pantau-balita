<?php
/**
 * Pengaturan umum aplikasi.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Akses ditolak.');
}

define('APP_NAME', 'Pantau Balita');
define('APP_TIMEZONE', 'Asia/Jakarta');

// Kosongkan untuk deteksi otomatis. Isi bila aplikasi berada di sub-folder
// dan deteksi otomatis keliru, contoh: '/pantau-balita'
define('BASE_URL_OVERRIDE', '');

// Unggahan foto anak
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/children');
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024); // 2 MB

// Mode debug: JANGAN aktifkan di hosting publik
define('APP_DEBUG', false);

// Pembatasan login: maksimal percobaan gagal dalam jendela waktu (menit)
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_MINUTES', 15);
