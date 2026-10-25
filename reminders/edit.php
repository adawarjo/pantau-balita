<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$rec = find_owned_reminder(is_post() ? post_int('id') : query_int('id'));
if (!$rec) {
    not_found();
}
$children = user_children();
$data = array_merge(reminder_defaults(), array_map(fn($v) => $v ?? '', $rec));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $c] = reminder_validate();
    if (!$errors) {
        db()->prepare('UPDATE reminders SET child_id = ?, title = ?, category = ?, due_date = ?, due_time = ?, notes = ? WHERE id = ? AND user_id = ?')
            ->execute([$c['child_id'], $c['title'], $c['category'], $c['due_date'], $c['due_time'], $c['notes'], $rec['id'], current_user_id()]);
        set_flash('success', 'Pengingat diperbarui.');
        redirect('reminders/index.php');
    }
}

$pageTitle = 'Edit Pengingat';
$activeMenu = 'reminders';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-8">
    <div class="card"><div class="card-body"><?php reminder_form($data, $errors, $children, (int) $rec['id']); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
