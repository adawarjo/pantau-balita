<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_login();
require __DIR__ . '/_form.php';

$rec = find_owned_record('health_records', is_post() ? post_int('id') : query_int('id'));
if (!$rec) {
    not_found();
}
$children = user_children();
$data = array_merge(health_defaults(), array_map(fn($v) => $v ?? '', $rec));
$errors = [];

if (is_post()) {
    csrf_verify();
    [$data, $errors, $c] = health_validate();
    if (!$errors) {
        $st = db()->prepare(
            'UPDATE health_records h JOIN children ch ON ch.id = h.child_id
             SET h.child_id = ?, h.event_date = ?, h.record_type = ?, h.symptoms = ?, h.temperature = ?, h.medicine_name = ?,
                 h.medicine_dose = ?, h.allergies = ?, h.facility_visit = ?, h.notes = ?
             WHERE h.id = ? AND ch.user_id = ?'
        );
        $st->execute([$c['child_id'], $c['event_date'], $c['record_type'], $c['symptoms'], $c['temperature'], $c['medicine_name'],
            $c['medicine_dose'], $c['allergies'], $c['facility_visit'], $c['notes'], $rec['id'], current_user_id()]);
        set_flash('success', 'Catatan kesehatan diperbarui.');
        redirect('health/index.php?child_id=' . $c['child_id']);
    }
}

$pageTitle = 'Edit Catatan Kesehatan';
$activeMenu = 'health';
require APP_ROOT . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="card"><div class="card-body"><?php health_form($data, $errors, $children, (int) $rec['id']); ?></div></div>
</div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
