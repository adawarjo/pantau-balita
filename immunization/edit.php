<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$rec = find_owned_record('immunization_records', is_post() ? post_int('id') : query_int('id'));
if (!$rec) {
    not_found();
}
$children = user_children();
$data = array_merge(imm_defaults(), array_map(fn($v) => $v ?? '', $rec));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $clean, $child] = imm_validate();
    if (!$errors) {
        $st = db()->prepare(
            'UPDATE immunization_records i JOIN children c ON c.id = i.child_id
             SET i.child_id = ?, i.vaccine_name = ?, i.dose = ?, i.status = ?, i.given_at = ?, i.location = ?, i.batch_number = ?,
                 i.reaction_notes = ?, i.next_date = ?, i.record_source = ?
             WHERE i.id = ? AND c.user_id = ?'
        );
        $st->execute([$clean['child_id'], $clean['vaccine_name'], $clean['dose'], $clean['status'], $clean['given_at'], $clean['location'],
            $clean['batch_number'], $clean['reaction_notes'], $clean['next_date'], $clean['record_source'], $rec['id'], current_user_id()]);
        if ($data['make_reminder']) {
            imm_create_reminder($clean, $child);
        }
        set_flash('success', 'Catatan imunisasi diperbarui.');
        redirect('immunization/index.php?child_id=' . $clean['child_id']);
    }
}

$pageTitle = 'Edit Imunisasi';
$activeMenu = 'immunization';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php imm_form($data, $errors, $children, (int) $rec['id']); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
