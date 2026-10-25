<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$rec = find_owned_reminder(post_int('id'));
if (!$rec) {
    not_found();
}
db()->prepare('DELETE FROM reminders WHERE id = ? AND user_id = ?')->execute([$rec['id'], current_user_id()]);
set_flash('success', 'Pengingat "' . $rec['title'] . '" dihapus.');
redirect('reminders/index.php');
