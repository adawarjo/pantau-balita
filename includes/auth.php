<?php
/**
 * Autentikasi & sesi pengguna (level tunggal: Orangtua).
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

const SESSION_IDLE_SECONDS = 7200; // 2 jam tanpa aktivitas -> logout otomatis

function is_logged_in(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    $last = $_SESSION['last_activity'] ?? 0;
    if (time() - $last > SESSION_IDLE_SECONDS) {
        logout_user();
        set_flash('info', 'Sesi berakhir karena tidak ada aktivitas. Silakan login kembali.');
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_user(): ?array
{
    static $user = null;
    if ($user === null && current_user_id() > 0) {
        // SELECT * agar tetap berjalan sebelum/sesudah migrasi kolom baru
        $st = db()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([current_user_id()]);
        $user = $st->fetch() ?: null;
        if ($user) {
            unset($user['password_hash']);
        }
    }
    return $user;
}

/** Wajibkan login untuk halaman privat */
function require_login(): void
{
    // Halaman berisi data pribadi anak: jangan disimpan di cache browser/proxy
    if (!headers_sent()) {
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
    }
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
    // Akun dihapus saat sesi masih aktif
    if (current_user() === null) {
        logout_user();
        redirect('auth/login.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf_token']); // token baru setelah login
}

function logout_user(): void
{
    $flash = $_SESSION['flash'] ?? null;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    if ($flash) {
        $_SESSION['flash'] = $flash;
    }
}

// --- Pembatasan percobaan login ---
function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function login_is_throttled(string $email): bool
{
    $st = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE (email = ? OR ip_address = ?) AND attempted_at > (NOW() - INTERVAL ? MINUTE)'
    );
    $st->execute([$email, client_ip(), LOGIN_WINDOW_MINUTES]);
    return (int) $st->fetchColumn() >= LOGIN_MAX_ATTEMPTS * 2
        || login_failures_for_email($email) >= LOGIN_MAX_ATTEMPTS;
}

function login_failures_for_email(string $email): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)');
    $st->execute([$email, LOGIN_WINDOW_MINUTES]);
    return (int) $st->fetchColumn();
}

function login_record_failure(string $email): void
{
    db()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)')->execute([$email, client_ip()]);
    // Bersihkan catatan lama
    db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
}

function login_clear_failures(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
}
