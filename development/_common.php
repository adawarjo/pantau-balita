<?php
/**
 * Fungsi bersama modul perkembangan.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

const DEV_DISCLAIMER = 'Daftar ini membantu orangtua mengamati kemampuan anak sesuai usia dan BUKAN instrumen skrining klinis (bukan KPSP/SDIDTK resmi). Setiap anak berkembang dengan kecepatan berbeda. Hasil "Belum Dapat" bukan berarti anak mengalami keterlambatan atau gangguan.';

/** Daftar kelompok usia yang tersedia */
function dev_age_groups(): array
{
    return db()->query(
        'SELECT age_min_months, age_max_months, age_group_label, COUNT(*) AS n
         FROM developmental_indicators GROUP BY age_min_months, age_max_months, age_group_label
         ORDER BY age_min_months'
    )->fetchAll();
}

/** Kelompok usia untuk usia (bulan penuh) anak */
function dev_group_for_age(array $groups, int $months): ?array
{
    foreach ($groups as $g) {
        if ($months >= (int) $g['age_min_months'] && $months < (int) $g['age_max_months']) {
            return $g;
        }
    }
    return $groups ? end($groups) : null;
}

function dev_find_group(array $groups, int $min): ?array
{
    foreach ($groups as $g) {
        if ((int) $g['age_min_months'] === $min) {
            return $g;
        }
    }
    return null;
}

function dev_indicators(int $min, int $max): array
{
    $st = db()->prepare('SELECT * FROM developmental_indicators WHERE age_min_months = ? AND age_max_months = ? ORDER BY sort_order, id');
    $st->execute([$min, $max]);
    return $st->fetchAll();
}

/** Status terakhir per indikator untuk anak */
function dev_latest_status(int $childId, array $indicatorIds): array
{
    if (!$indicatorIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($indicatorIds), '?'));
    $st = db()->prepare(
        "SELECT o.* FROM developmental_observations o
         JOIN (SELECT indicator_id, MAX(observed_at) AS mx FROM developmental_observations
               WHERE child_id = ? AND indicator_id IN ($in) GROUP BY indicator_id) t
           ON t.indicator_id = o.indicator_id AND t.mx = o.observed_at
         WHERE o.child_id = ?"
    );
    $st->execute(array_merge([$childId], $indicatorIds, [$childId]));
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[(int) $r['indicator_id']] = $r;
    }
    return $out;
}

function obs_badge(string $status): string
{
    $cls = ['sudah' => 'success', 'belum' => 'warning', 'belum_diamati' => 'secondary'][$status] ?? 'secondary';
    return '<span class="badge badge-soft-' . $cls . '">' . e(OBS_STATUS[$status] ?? $status) . '</span>';
}
