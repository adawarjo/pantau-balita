<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$uid = current_user_id();
$user = current_user();
$errors = [];
$pwErrors = [];
$form = $user;

if (is_post()) {
    csrf_verify();
    $action = input('action');

    if ($action === 'profile') {
        $form['full_name'] = input('full_name');
        $form['email'] = mb_strtolower(input('email'));
        $form['phone'] = input('phone');

        if (mb_strlen($form['full_name']) < 3 || !str_len_ok($form['full_name'], 100)) {
            $errors['full_name'] = 'Nama lengkap 3-100 karakter.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || !str_len_ok($form['email'], 150)) {
            $errors['email'] = 'Format email tidak valid.';
        } else {
            $st = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $st->execute([$form['email'], $uid]);
            if ($st->fetchColumn()) {
                $errors['email'] = 'Email sudah digunakan akun lain.';
            }
        }
        if ($form['phone'] !== '' && !preg_match('/^\+?[0-9\s\-]{8,20}$/', $form['phone'])) {
            $errors['phone'] = 'Nomor telepon hanya berisi angka (8-20 digit).';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?')
                ->execute([$form['full_name'], $form['email'], null_if_empty($form['phone']), $uid]);
            $_SESSION['user_name'] = $form['full_name'];
            set_flash('success', 'Profil berhasil diperbarui.');
            redirect('profile.php');
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$uid]);
        $hash = (string) $st->fetchColumn();

        if (!is_string($current) || !password_verify($current, $hash)) {
            $pwErrors['current_password'] = 'Kata sandi saat ini salah.';
        }
        if (!is_string($new) || strlen($new) < 8 || strlen($new) > 72 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $pwErrors['new_password'] = 'Kata sandi baru 8-72 karakter, berisi huruf dan angka.';
        }
        if ($new !== $confirm) {
            $pwErrors['new_password_confirm'] = 'Konfirmasi kata sandi tidak sama.';
        }
        if (!$pwErrors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            set_flash('success', 'Kata sandi berhasil diubah.');
            redirect('profile.php');
        }
    } elseif ($action === 'delete_account') {
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$uid]);
        $pw = $_POST['delete_password'] ?? '';
        if (is_string($pw) && password_verify($pw, (string) $st->fetchColumn())) {
            // Hapus berkas foto anak terlebih dahulu
            $ph = db()->prepare('SELECT photo FROM children WHERE user_id = ? AND photo IS NOT NULL');
            $ph->execute([$uid]);
            foreach ($ph->fetchAll(PDO::FETCH_COLUMN) as $file) {
                $path = child_photo_path($file);
                if ($path && is_file($path)) {
                    @unlink($path);
                }
            }
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]); // CASCADE ke seluruh data
            logout_user();
            set_flash('success', 'Akun dan seluruh data Anda telah dihapus.');
            redirect('auth/login.php');
        }
        set_flash('error', 'Kata sandi salah. Akun tidak dihapus.');
        redirect('profile.php');
    }
}


$st = db()->prepare('SELECT COUNT(*) FROM children WHERE user_id = ?');
$st->execute([$uid]);
$childCount = (int) $st->fetchColumn();

$pageTitle = 'Profil Saya';
$activeMenu = 'profile';
require APP_ROOT . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <span class="avatar avatar-lg bg-soft-primary mb-3"><i class="fa-solid fa-user"></i></span>
                <h2 class="h5 mb-1"><?= e($user['full_name']) ?></h2>
                <div class="text-muted small mb-3"><?= e($user['email']) ?></div>
                <div class="d-flex justify-content-center gap-3 small">
                    <div><div class="fw-bold fs-5"><?= $childCount ?></div><div class="text-muted">Anak</div></div>
                    <div><div class="fw-bold"><?= e(tgl_indo($user['created_at'])) ?></div><div class="text-muted">Bergabung</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Data Profil</div>
            <div class="card-body">
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required" for="full_name">Nama lengkap</label>
                            <input class="form-control<?= invalid($errors, 'full_name') ?>" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" maxlength="100" required>
                            <?= field_error($errors, 'full_name') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required" for="email">Email</label>
                            <input type="email" class="form-control<?= invalid($errors, 'email') ?>" id="email" name="email" value="<?= e($form['email']) ?>" maxlength="150" required>
                            <?= field_error($errors, 'email') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Nomor telepon</label>
                            <input type="tel" class="form-control<?= invalid($errors, 'phone') ?>" id="phone" name="phone" value="<?= e($form['phone']) ?>" maxlength="20">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal pembuatan akun</label>
                            <input class="form-control" value="<?= e(datetime_indo($user['created_at'])) ?>" disabled>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Profil</button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="fa-solid fa-key me-2 text-primary"></i>Ubah Kata Sandi</div>
            <div class="card-body">
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required" for="current_password">Kata sandi saat ini</label>
                            <input type="password" class="form-control<?= invalid($pwErrors, 'current_password') ?>" id="current_password" name="current_password" autocomplete="current-password" required>
                            <?= field_error($pwErrors, 'current_password') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required" for="new_password">Kata sandi baru</label>
                            <input type="password" class="form-control<?= invalid($pwErrors, 'new_password') ?>" id="new_password" name="new_password" autocomplete="new-password" required>
                            <?= field_error($pwErrors, 'new_password') ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required" for="new_password_confirm">Ulangi kata sandi baru</label>
                            <input type="password" class="form-control<?= invalid($pwErrors, 'new_password_confirm') ?>" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
                            <?= field_error($pwErrors, 'new_password_confirm') ?>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit"><i class="fa-solid fa-lock me-1"></i> Ubah Kata Sandi</button>
                </form>
            </div>
        </div>

        <div class="card border-danger-subtle">
            <div class="card-header text-danger"><i class="fa-solid fa-user-xmark me-2"></i>Hapus Akun</div>
            <div class="card-body">
                <p class="small text-muted">Menghapus akun akan menghapus permanen seluruh data anak, pengukuran, imunisasi, catatan kesehatan, dan pengingat Anda.</p>
                <form method="post" class="js-confirm-delete row g-2 align-items-end" data-message="Akun dan SELURUH data anak akan dihapus permanen.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_account">
                    <div class="col-sm-8">
                        <label class="form-label" for="delete_password">Konfirmasi dengan kata sandi</label>
                        <input type="password" class="form-control" id="delete_password" name="delete_password" required autocomplete="current-password">
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-outline-danger w-100" type="submit">Hapus Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
