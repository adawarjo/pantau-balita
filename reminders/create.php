<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$children = user_children();
if (!$children) {
    set_flash('info', 'Tambahkan data anak terlebih dahulu.');
    redirect('children/create.php');
}
$pre = query_int('child_id');
$data = reminder_defaults(($pre && find_child($pre)) ? $pre : (count($children) === 1 ? (int) $children[0]['id'] : 0));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $c] = reminder_validate();
    if (!$errors) {
        db()->prepare('INSERT INTO reminders (user_id, child_id, title, category, due_date, due_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([current_user_id(), $c['child_id'], $c['title'], $c['category'], $c['due_date'], $c['due_time'], $c['notes']]);
        set_flash('success', 'Pengingat tersimpan.');
        redirect('reminders/index.php');
    }
}

$pageTitle = 'Tambah Pengingat';
$activeMenu = 'reminders';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-8">
    <div class="card"><div class="card-body"><?php reminder_form($data, $errors, $children); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
