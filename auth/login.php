<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if (is_post()) {
    csrf_verify();
    $email = mb_strtolower(input('email'));
    $password = $_POST['password'] ?? '';

    if ($email === '' || !is_string($password) || $password === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } elseif (login_is_throttled($email)) {
        $error = 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . LOGIN_WINDOW_MINUTES . ' menit.';
    } else {
        $st = db()->prepare('SELECT id, full_name, password_hash FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            login_clear_failures($email);
            login_user($user);
            set_flash('success', 'Selamat datang kembali, ' . $user['full_name'] . '!');
            redirect('dashboard.php');
        }
        login_record_failure($email);
        // Pesan umum agar tidak membocorkan apakah email terdaftar
        $error = 'Email atau kata sandi salah.';
    }
}

$pageTitle = 'Masuk';
$layout = 'auth';
require APP_ROOT . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="" class="auth-logo mb-2">
            <h1 class="h4 mb-1"><?= e(APP_NAME) ?></h1>
            <p class="text-muted mb-0">Catatan tumbuh kembang anak 0–5 tahun.</p>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert"><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email" autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Kata sandi</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="fa-solid fa-right-to-bracket me-1"></i> Masuk</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-3 mb-0">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>" class="fw-bold">Daftar gratis</a></p>
        <p class="text-center small text-muted mt-2 mb-0">Alat bantu pemantauan, bukan diagnosis medis. <a href="<?= e(url('disclaimer.php')) ?>" class="text-muted fw-bold">Disclaimer</a></p>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
