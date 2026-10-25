<?php
/**
 * Pengambilan data laporan satu anak untuk periode tertentu.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

/** Baca & validasi parameter laporan dari $_GET */
function report_params(): array
{
    $children = user_children();
    if (!$children) {
        set_flash('info', 'Tambahkan data anak terlebih dahulu.');
        redirect('children/create.php');
    }
    $childId = query_int('child_id') ?: (int) $children[0]['id'];
    $child = require_child($childId);
    $from = query('from');
    $to = query('to');
    if (!valid_date($from)) $from = $child['birth_date'];
    if (!valid_date($to) || $to > today()) $to = today();
    if ($from > $to) [$from, $to] = [$to, $from];
    return [$children, $child, $from, $to];
}

function report_data(array $child, string $from, string $to): array
{
    $cid = (int) $child['id'];
    $pdo = db();
    $q = function (string $sql, array $p) use ($pdo) {
        $st = $pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    };

    $data = [];
    $data['growth'] = $q('SELECT * FROM growth_records WHERE child_id = ? AND measured_at BETWEEN ? AND ? ORDER BY measured_at, id', [$cid, $from, $to]);
    $data['imm'] = $q('SELECT * FROM immunization_records WHERE child_id = ? AND (given_at BETWEEN ? AND ? OR (given_at IS NULL AND next_date BETWEEN ? AND ?))
                       ORDER BY COALESCE(given_at, next_date), id', [$cid, $from, $to, $from, $to]);
    $data['health'] = $q('SELECT * FROM health_records WHERE child_id = ? AND event_date BETWEEN ? AND ? ORDER BY event_date, id', [$cid, $from, $to]);
    $data['reminders'] = $q('SELECT * FROM reminders WHERE child_id = ? AND user_id = ? AND (is_done = 0 OR due_date BETWEEN ? AND ?) ORDER BY is_done, due_date',
        [$cid, current_user_id(), $from, $to]);
    $data['allergies'] = array_values(array_unique(array_column(
        $q("SELECT allergies FROM health_records WHERE child_id = ? AND allergies IS NOT NULL AND allergies <> '' ORDER BY event_date", [$cid]), 'allergies')));

    // Ringkasan perkembangan: status terakhir dalam periode per kelompok usia & bidang
    $data['dev'] = $q(
        "SELECT i.age_group_label, i.age_min_months, i.domain,
                SUM(o.status = 'sudah') AS sudah, SUM(o.status = 'belum') AS belum,
                SUM(o.status = 'belum_diamati') AS belum_diamati, COUNT(*) AS total, MAX(o.observed_at) AS last_date
         FROM developmental_observations o
         JOIN developmental_indicators i ON i.id = o.indicator_id
         JOIN (SELECT indicator_id, MAX(observed_at) mx FROM developmental_observations
               WHERE child_id = ? AND observed_at BETWEEN ? AND ? GROUP BY indicator_id) t
           ON t.indicator_id = o.indicator_id AND t.mx = o.observed_at
         WHERE o.child_id = ?
         GROUP BY i.age_group_label, i.age_min_months, i.domain
         ORDER BY i.age_min_months, FIELD(i.domain, 'motorik_kasar','motorik_halus','bahasa','sosial')",
        [$cid, $from, $to, $cid]
    );
    $data['dev_belum'] = $q(
        "SELECT i.description, i.age_group_label, o.observed_at FROM developmental_observations o
         JOIN developmental_indicators i ON i.id = o.indicator_id
         JOIN (SELECT indicator_id, MAX(observed_at) mx FROM developmental_observations
               WHERE child_id = ? AND observed_at BETWEEN ? AND ? GROUP BY indicator_id) t
           ON t.indicator_id = o.indicator_id AND t.mx = o.observed_at
         WHERE o.child_id = ? AND o.status = 'belum' ORDER BY i.age_min_months, i.sort_order",
        [$cid, $from, $to, $cid]
    );
    return $data;
}
