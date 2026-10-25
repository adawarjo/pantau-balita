<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$child = require_child(post_int('child_id'));
$date = input('observed_at');
$group = post_int('group');
if (!valid_date($date)) {
    not_found();
}
$st = db()->prepare(
    'DELETE o FROM developmental_observations o
     JOIN developmental_indicators i ON i.id = o.indicator_id
     JOIN children c ON c.id = o.child_id
     WHERE o.child_id = ? AND c.user_id = ? AND o.observed_at = ? AND i.age_min_months = ?'
);
$st->execute([$child['id'], current_user_id(), $date, $group]);
set_flash('success', 'Observasi tanggal ' . tgl_indo($date) . ' dihapus (' . $st->rowCount() . ' indikator).');
redirect('development/history.php?child_id=' . (int) $child['id']);
