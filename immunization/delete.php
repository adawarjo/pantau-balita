<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$rec = find_owned_record('immunization_records', post_int('id'));
if (!$rec) {
    not_found();
}
$st = db()->prepare('DELETE i FROM immunization_records i JOIN children c ON c.id = i.child_id WHERE i.id = ? AND c.user_id = ?');
$st->execute([$rec['id'], current_user_id()]);
set_flash('success', 'Catatan imunisasi ' . $rec['vaccine_name'] . ' dihapus.');
redirect('immunization/index.php?child_id=' . (int) $rec['child_id']);
