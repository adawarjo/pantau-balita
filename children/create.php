<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$data = child_form_defaults();
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean] = child_validate();
    $photo = null;
    if (!$errors) {
        [$photo, $photoErr] = handle_photo_upload('photo');
        if ($photoErr) {
            $errors['photo'] = $photoErr;
        }
    }
    if (!$errors) {
        $st = db()->prepare(
            'INSERT INTO children (user_id, full_name, photo, gender, birth_place, birth_date, birth_weight, birth_length, birth_history, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([current_user_id(), $clean['full_name'], $photo, $clean['gender'], $clean['birth_place'], $clean['birth_date'],
            $clean['birth_weight'], $clean['birth_length'], $clean['birth_history'], $clean['notes']]);
        $id = (int) db()->lastInsertId();
        set_flash('success', 'Data ' . $clean['full_name'] . ' berhasil ditambahkan.');
        redirect('children/detail.php?id=' . $id);
    }
}

$pageTitle = 'Tambah Anak';
$activeMenu = 'children';
require APP_ROOT . '/includes/header.php';
?>
<div class="card"><div class="card-body"><?php child_form($data, $errors); ?></div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
