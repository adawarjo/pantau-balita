<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$child = require_child(is_post() ? post_int('id') : query_int('id'));
$data = array_merge(child_form_defaults(), array_map(fn($v) => $v ?? '', $child));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean] = child_validate();

    // Pastikan tanggal lahir baru tidak setelah catatan yang sudah ada
    if (!$errors) {
        $st = db()->prepare('SELECT MIN(measured_at) FROM growth_records WHERE child_id = ?');
        $st->execute([$child['id']]);
        $first = $st->fetchColumn();
        if ($first && $clean['birth_date'] > $first) {
            $errors['birth_date'] = 'Tanggal lahir tidak boleh setelah pengukuran pertama (' . tgl_indo($first) . ').';
        }
    }

    $photo = $child['photo'];
    if (!$errors) {
        [$newPhoto, $photoErr] = handle_photo_upload('photo');
        if ($photoErr) {
            $errors['photo'] = $photoErr;
        } elseif ($newPhoto) {
            delete_child_photo($child['photo']);
            $photo = $newPhoto;
        } elseif (input('remove_photo') === '1') {
            delete_child_photo($child['photo']);
            $photo = null;
        }
    }
    if (!$errors) {
        $st = db()->prepare(
            'UPDATE children SET full_name = ?, photo = ?, gender = ?, birth_place = ?, birth_date = ?, birth_weight = ?, birth_length = ?, birth_history = ?, notes = ?
             WHERE id = ? AND user_id = ?'
        );
        $st->execute([$clean['full_name'], $photo, $clean['gender'], $clean['birth_place'], $clean['birth_date'], $clean['birth_weight'],
            $clean['birth_length'], $clean['birth_history'], $clean['notes'], $child['id'], current_user_id()]);
        set_flash('success', 'Profil anak berhasil diperbarui.');
        redirect('children/detail.php?id=' . $child['id']);
    }
}

$pageTitle = 'Edit Profil Anak';
$activeMenu = 'children';
require APP_ROOT . '/includes/header.php';
?>
<div class="card"><div class="card-body">
    <?php child_form($data, $errors, $child); ?>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
