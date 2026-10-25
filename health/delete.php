<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$rec = find_owned_record('health_records', post_int('id'));
if (!$rec) {
    not_found();
}
$st = db()->prepare('DELETE h FROM health_records h JOIN children c ON c.id = h.child_id WHERE h.id = ? AND c.user_id = ?');
$st->execute([$rec['id'], current_user_id()]);
set_flash('success', 'Catatan kesehatan tanggal ' . tgl_indo($rec['event_date']) . ' dihapus.');
redirect('health/index.php?child_id=' . (int) $rec['child_id']);
