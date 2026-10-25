<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$rec = find_owned_record('growth_records', post_int('id'));
if (!$rec) {
    not_found();
}
$st = db()->prepare('DELETE g FROM growth_records g JOIN children c ON c.id = g.child_id WHERE g.id = ? AND c.user_id = ?');
$st->execute([$rec['id'], current_user_id()]);
set_flash('success', 'Pengukuran tanggal ' . tgl_indo($rec['measured_at']) . ' telah dihapus.');
redirect('growth/index.php?child_id=' . (int) $rec['child_id']);
