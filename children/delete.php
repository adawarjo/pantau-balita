<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require_post();

$child = require_child(post_int('id'));

// Seluruh catatan terkait ikut terhapus melalui ON DELETE CASCADE
$st = db()->prepare('DELETE FROM children WHERE id = ? AND user_id = ?');
$st->execute([$child['id'], current_user_id()]);
delete_child_photo($child['photo']);

set_flash('success', 'Data ' . $child['full_name'] . ' beserta seluruh catatannya telah dihapus.');
redirect('children/index.php');
