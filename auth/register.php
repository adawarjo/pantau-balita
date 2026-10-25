<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$data = ['full_name' => '', 'email' => '', 'phone' => '', 'agree' => false];

if (is_post()) {
    csrf_verify();
    $data['full_name'] = input('full_name');
    $data['email'] = mb_strtolower(input('email'));
    $data['phone'] = input('phone');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($data['full_name'] === '' || mb_strlen($data['full_name']) < 3) {
        $errors['full_name'] = 'Nama lengkap minimal 3 karakter.';
    } elseif (!str_len_ok($data['full_name'], 100)) {
        $errors['full_name'] = 'Nama lengkap maksimal 100 karakter.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !str_len_ok($data['email'], 150)) {
        $errors['email'] = 'Format email tidak valid.';
    }
    if ($data['phone'] !== '' && !preg_match('/^\+?[0-9\s\-]{8,20}$/', $data['phone'])) {
        $errors['phone'] = 'Nomor telepon hanya berisi angka (8-20 digit).';
    }
    if (!is_string($password) || strlen($password) < 8) {
        $errors['password'] = 'Kata sandi minimal 8 karakter.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Kata sandi harus mengandung huruf dan angka.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Kata sandi maksimal 72 karakter.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Konfirmasi kata sandi tidak sama.';
    }
    $data['agree'] = input('agree') === '1';
    if (!$data['agree']) {
        $errors['agree'] = 'Anda perlu membaca dan menyetujui disclaimer untuk mendaftar.';
    }

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$data['email']]);
        if ($st->fetchColumn()) {
            $errors['email'] = 'Email sudah terdaftar. Silakan login.';
        }
    }

    if (!$errors) {
        try {
            $st = db()->prepare('INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
            $st->execute([$data['full_name'], $data['email'], null_if_empty($data['phone']), password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int) db()->lastInsertId();
            try {
                record_disclaimer_acceptance($id);
            } catch (PDOException $e) {
                // Kolom disclaimer belum dimigrasi: akun tetap dibuat
                error_log('[Pantau Balita] Simpan persetujuan disclaimer gagal: ' . $e->getMessage());
            }
            login_user(['id' => $id, 'full_name' => $data['full_name']]);
            set_flash('success', 'Pendaftaran berhasil. Selamat datang, ' . $data['full_name'] . '!');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'Email sudah terdaftar. Silakan login.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Daftar Akun';
$layout = 'auth';
require APP_ROOT . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-4">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="" class="auth-logo mb-2">
            <h1 class="h4 mb-1">Daftar Akun Orangtua</h1>
            <p class="text-muted mb-0">Pantau tumbuh kembang si kecil dengan mudah.</p>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="full_name" class="form-label required">Nama lengkap</label>
                        <input type="text" class="form-control<?= invalid($errors, 'full_name') ?>" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" maxlength="100" required autocomplete="name">
                        <?= field_error($errors, 'full_name') ?>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label required">Email</label>
                        <input type="email" class="form-control<?= invalid($errors, 'email') ?>" id="email" name="email" value="<?= e($data['email']) ?>" maxlength="150" required autocomplete="email">
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor telepon <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="tel" class="form-control<?= invalid($errors, 'phone') ?>" id="phone" name="phone" value="<?= e($data['phone']) ?>" maxlength="20" autocomplete="tel" placeholder="08xxxxxxxxxx">
                        <?= field_error($errors, 'phone') ?>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label required">Kata sandi</label>
                        <input type="password" class="form-control<?= invalid($errors, 'password') ?>" id="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
                        <div class="form-text">Minimal 8 karakter, berisi huruf dan angka.</div>
                        <?= field_error($errors, 'password') ?>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label required">Ulangi kata sandi</label>
                        <input type="password" class="form-control<?= invalid($errors, 'password_confirm') ?>" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                        <?= field_error($errors, 'password_confirm') ?>
                    </div>
                    <div class="note-box small mb-3">
                        <strong><i class="fa-solid fa-file-shield me-1"></i>Disclaimer</strong>
                        <ul class="mb-1 mt-1 ps-3">
                            <?php foreach (disclaimer_summary() as [, $text]): ?><li><?= e($text) ?></li><?php endforeach; ?>
                        </ul>
                        <a href="<?= e(url('disclaimer.php')) ?>" target="_blank" rel="noopener" class="fw-bold" style="color:inherit">Baca disclaimer lengkap <i class="fa-solid fa-arrow-up-right-from-square small"></i></a>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input<?= invalid($errors, 'agree') ?>" type="checkbox" name="agree" value="1" id="agree" <?= $data['agree'] ? 'checked' : '' ?> required>
                        <label class="form-check-label small" for="agree">Saya telah membaca dan menyetujui <strong>disclaimer</strong> Pantau Balita.</label>
                        <?= field_error($errors, 'agree') ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="fa-solid fa-user-plus me-1"></i> Daftar</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-3 mb-0">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>" class="fw-bold">Masuk di sini</a></p>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
