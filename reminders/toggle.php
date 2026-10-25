<?php
/** Tandai pengingat selesai / belum selesai */
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$rec = find_owned_reminder(post_int('id'));
if (!$rec) {
    not_found();
}
$done = $rec['is_done'] ? 0 : 1;
db()->prepare('UPDATE reminders SET is_done = ?, done_at = ? WHERE id = ? AND user_id = ?')
    ->execute([$done, $done ? date('Y-m-d H:i:s') : null, $rec['id'], current_user_id()]);
set_flash('success', $done ? 'Pengingat ditandai selesai.' : 'Pengingat diaktifkan kembali.');
redirect(input('back') === 'dashboard' ? 'dashboard.php' : 'reminders/index.php');
