<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$rec = find_owned_record('growth_records', is_post() ? post_int('id') : query_int('id'));
if (!$rec) {
    not_found();
}
$children = user_children();
$data = array_merge(growth_defaults(), array_map(fn($v) => $v ?? '', $rec));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean] = growth_validate();
    if (!$errors) {
        $st = db()->prepare(
            'UPDATE growth_records g JOIN children c ON c.id = g.child_id
             SET g.child_id = ?, g.measured_at = ?, g.weight = ?, g.height = ?, g.measure_method = ?, g.head_circ = ?, g.muac = ?, g.notes = ?
             WHERE g.id = ? AND c.user_id = ?'
        );
        $st->execute([$clean['child_id'], $clean['measured_at'], $clean['weight'], $clean['height'], $clean['measure_method'],
            $clean['head_circ'], $clean['muac'], $clean['notes'], $rec['id'], current_user_id()]);
        set_flash('success', 'Pengukuran berhasil diperbarui.');
        redirect('growth/index.php?child_id=' . $clean['child_id']);
    }
}

$pageTitle = 'Edit Pengukuran';
$activeMenu = 'growth';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php growth_form($data, $errors, $children, (int) $rec['id']); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
