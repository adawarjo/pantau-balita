<?php
/**
 * Perhitungan z-score berdasarkan WHO Child Growth Standards (2006)
 * menggunakan metode LMS, dengan kategori ambang batas sesuai
 * Peraturan Menteri Kesehatan RI No. 2 Tahun 2020 tentang Standar Antropometri Anak.
 *
 * Catatan:
 * - Data LMS dibaca dari tabel who_growth_reference (lihat database.sql).
 *   Bila tabel kosong, aplikasi hanya menampilkan grafik aktual tanpa klasifikasi.
 * - Tabel referensi berinterval bulanan (umur) / 0,5 cm (panjang-tinggi);
 *   nilai di antaranya diinterpolasi linear.
 * - Hasil adalah alat bantu pemantauan, BUKAN diagnosis medis.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

const WHO_SOURCE_NOTE = 'WHO Child Growth Standards 2006 (metode LMS); kategori menurut Permenkes RI No. 2 Tahun 2020 tentang Standar Antropometri Anak.';
const MEDICAL_DISCLAIMER = 'Hasil aplikasi ini merupakan alat bantu pemantauan, bukan diagnosis medis. Status gizi dan pertumbuhan anak ditetapkan oleh tenaga kesehatan berdasarkan beberapa indikator dan pemeriksaan langsung.';

function who_reference_available(): bool
{
    static $ok = null;
    if ($ok === null) {
        $ok = (int) db()->query('SELECT COUNT(*) FROM who_growth_reference')->fetchColumn() > 0;
    }
    return $ok;
}

/** Semua baris LMS untuk satu indikator & jenis kelamin (cache per request) */
function who_rows(string $indicator, string $sex): array
{
    static $cache = [];
    $key = $indicator . $sex;
    if (!isset($cache[$key])) {
        $st = db()->prepare('SELECT x, l, m, s FROM who_growth_reference WHERE indicator = ? AND sex = ? ORDER BY x');
        $st->execute([$indicator, $sex]);
        $cache[$key] = array_map(fn($r) => array_map('floatval', $r), $st->fetchAll());
    }
    return $cache[$key];
}

/** Parameter LMS pada x (diinterpolasi). null bila x di luar rentang tabel. */
function who_lms(string $indicator, string $sex, float $x): ?array
{
    $rows = who_rows($indicator, $sex);
    $n = count($rows);
    if ($n === 0 || $x < $rows[0]['x'] - 1e-9 || $x > $rows[$n - 1]['x'] + 1e-9) {
        return null;
    }
    for ($i = 0; $i < $n; $i++) {
        if (abs($rows[$i]['x'] - $x) < 1e-9) {
            return $rows[$i];
        }
        if ($i < $n - 1 && $x > $rows[$i]['x'] && $x < $rows[$i + 1]['x']) {
            $a = $rows[$i];
            $b = $rows[$i + 1];
            $t = ($x - $a['x']) / ($b['x'] - $a['x']);
            return [
                'x' => $x,
                'l' => $a['l'] + $t * ($b['l'] - $a['l']),
                'm' => $a['m'] + $t * ($b['m'] - $a['m']),
                's' => $a['s'] + $t * ($b['s'] - $a['s']),
            ];
        }
    }
    return null;
}

/** Nilai pengukuran pada z-score tertentu */
function lms_value(array $lms, float $z): float
{
    if (abs($lms['l']) < 1e-9) {
        return $lms['m'] * exp($lms['s'] * $z);
    }
    return $lms['m'] * pow(1 + $lms['l'] * $lms['s'] * $z, 1 / $lms['l']);
}

/**
 * Z-score dari LMS. $restricted = true untuk indikator berbasis berat
 * (BB/U, BB/PB, BB/TB, IMT/U) memakai penyesuaian WHO untuk |z| > 3.
 */
function lms_zscore(float $y, array $lms, bool $restricted): float
{
    $L = $lms['l'];
    $M = $lms['m'];
    $S = $lms['s'];
    $z = abs($L) < 1e-9 ? log($y / $M) / $S : (pow($y / $M, $L) - 1) / ($L * $S);

    if ($restricted) {
        if ($z > 3) {
            $sd3 = lms_value($lms, 3);
            $sd23 = $sd3 - lms_value($lms, 2);
            $z = 3 + ($y - $sd3) / $sd23;
        } elseif ($z < -3) {
            $sd3n = lms_value($lms, -3);
            $sd23n = lms_value($lms, -2) - $sd3n;
            $z = -3 + ($y - $sd3n) / $sd23n;
        }
    }
    return $z;
}

/** Kategori menurut Permenkes 2/2020. Mengembalikan [label, warna bootstrap] */
function growth_category(string $code, float $z): array
{
    switch ($code) {
        case 'bbu':
            if ($z < -3) return ['Berat badan sangat kurang', 'danger'];
            if ($z < -2) return ['Berat badan kurang', 'warning'];
            if ($z <= 1) return ['Berat badan normal', 'success'];
            return ['Risiko berat badan lebih', 'warning'];
        case 'pbu':
            if ($z < -3) return ['Sangat pendek', 'danger'];
            if ($z < -2) return ['Pendek', 'warning'];
            if ($z <= 3) return ['Normal', 'success'];
            return ['Tinggi', 'info'];
        case 'bbpb':
        case 'imtu':
            if ($z < -3) return ['Gizi buruk', 'danger'];
            if ($z < -2) return ['Gizi kurang', 'warning'];
            if ($z <= 1) return ['Gizi baik (normal)', 'success'];
            if ($z <= 2) return ['Berisiko gizi lebih', 'warning'];
            if ($z <= 3) return ['Gizi lebih', 'warning'];
            return ['Obesitas', 'danger'];
        case 'lku':
            // Permenkes 2/2020 tidak menetapkan kategori LK/U; tampilkan posisi saja.
            if ($z < -2) return ['Di bawah -2 SD', 'warning'];
            if ($z <= 2) return ['Dalam rentang -2 s.d. +2 SD', 'success'];
            return ['Di atas +2 SD', 'warning'];
    }
    return ['-', 'secondary'];
}

/** Batas nilai tidak wajar menurut WHO (kemungkinan salah input) */
function z_implausible(string $code, float $z): bool
{
    $lim = ['bbu' => [-6, 5], 'pbu' => [-6, 6], 'bbpb' => [-5, 5], 'imtu' => [-5, 5], 'lku' => [-5, 5]];
    [$lo, $hi] = $lim[$code];
    return $z < $lo || $z > $hi;
}

/**
 * Analisis satu pengukuran.
 * @return array ['available'=>bool, 'age_months'=>float, 'note'=>string, 'items'=>[...]]
 */
function assess_growth(array $child, array $rec): array
{
    $result = ['available' => false, 'age_months' => null, 'note' => '', 'items' => [], 'adjusted_height' => null];

    $ageM = age_months_decimal($child['birth_date'], $rec['measured_at']);
    $result['age_months'] = $ageM;

    if (!who_reference_available()) {
        $result['note'] = 'Data referensi standar WHO belum tersedia di database, sehingga klasifikasi tidak ditampilkan.';
        return $result;
    }
    if ($ageM === null) {
        $result['note'] = 'Tanggal pengukuran sebelum tanggal lahir.';
        return $result;
    }
    if ($ageM > 60.0001) {
        $result['note'] = 'Standar WHO 0-5 tahun hanya berlaku sampai usia 60 bulan.';
        return $result;
    }

    $sex = $child['gender'];
    $w = (float) $rec['weight'];
    $h = (float) $rec['height'];
    $under2 = $ageM < 24;

    // Penyesuaian cara ukur (pedoman WHO): < 24 bln diukur berbaring;
    // bila diukur berdiri tambahkan 0,7 cm. >= 24 bln diukur berdiri;
    // bila diukur berbaring kurangi 0,7 cm.
    $method = $rec['measure_method'] ?? ($under2 ? 'length' : 'height');
    $hAdj = $h;
    if ($under2 && $method === 'height') {
        $hAdj = $h + 0.7;
    } elseif (!$under2 && $method === 'length') {
        $hAdj = $h - 0.7;
    }
    $result['adjusted_height'] = $hAdj;

    $items = [];
    $add = function (string $code, string $label, ?array $lms, float $y, bool $restricted, string $unit) use (&$items) {
        if ($lms === null) {
            $items[] = ['code' => $code, 'label' => $label, 'z' => null, 'category' => 'Di luar rentang tabel referensi', 'color' => 'secondary', 'value' => $y, 'unit' => $unit];
            return;
        }
        $z = lms_zscore($y, $lms, $restricted);
        if (z_implausible($code, $z)) {
            $items[] = ['code' => $code, 'label' => $label, 'z' => $z, 'category' => 'Nilai tidak wajar — periksa kembali data', 'color' => 'secondary', 'value' => $y, 'unit' => $unit];
            return;
        }
        [$cat, $color] = growth_category($code, $z);
        $items[] = ['code' => $code, 'label' => $label, 'z' => $z, 'category' => $cat, 'color' => $color, 'value' => $y, 'unit' => $unit];
    };

    // BB/U
    $add('bbu', 'BB/U (berat menurut umur)', who_lms('wfa', $sex, $ageM), $w, true, 'kg');

    // PB/U atau TB/U
    $add('pbu', $under2 ? 'PB/U (panjang menurut umur)' : 'TB/U (tinggi menurut umur)',
        who_lms($under2 ? 'lfa' : 'hfa', $sex, $ageM), $hAdj, false, 'cm');

    // BB/PB atau BB/TB (dibulatkan ke 0,1 cm sesuai praktik WHO)
    $hx = round($hAdj, 1);
    $add('bbpb', $under2 ? 'BB/PB (berat menurut panjang)' : 'BB/TB (berat menurut tinggi)',
        who_lms($under2 ? 'wfl' : 'wfh', $sex, $hx), $w, true, 'kg');

    // IMT/U
    $bmi = $w / pow($hAdj / 100, 2);
    $add('imtu', 'IMT/U (indeks massa tubuh menurut umur)', who_lms($under2 ? 'bmi_l' : 'bmi_h', $sex, $ageM), $bmi, true, 'kg/m²');

    // LK/U
    if (!empty($rec['head_circ'])) {
        $add('lku', 'LK/U (lingkar kepala menurut umur)', who_lms('hcfa', $sex, $ageM), (float) $rec['head_circ'], false, 'cm');
    }

    $result['available'] = true;
    $result['items'] = $items;
    return $result;
}

/**
 * Kurva referensi untuk grafik: garis -3, -2, 0, +2, +3 SD per bulan.
 */
function who_curves(string $indicator, string $sex, float $fromMonth, float $toMonth): array
{
    $out = ['x' => [], '-3' => [], '-2' => [], '0' => [], '2' => [], '3' => []];
    $rows = who_rows($indicator, $sex);
    foreach ($rows as $r) {
        if ($r['x'] < $fromMonth - 1e-9 || $r['x'] > $toMonth + 1e-9) {
            continue;
        }
        $out['x'][] = $r['x'];
        foreach ([-3, -2, 0, 2, 3] as $z) {
            $out[(string) $z][] = round(lms_value($r, $z), 2);
        }
    }
    return $out;
}

function z_format(?float $z): string
{
    if ($z === null) {
        return '-';
    }
    return ($z > 0 ? '+' : '') . number_format($z, 2, ',', '.');
}

/**
 * Data grafik untuk Chart.js (dipakai halaman grafik & laporan).
 * $records diurutkan menaik berdasarkan tanggal.
 */
function growth_chart_payload(array $child, array $records): array
{
    $byDate = ['labels' => [], 'weight' => [], 'height' => [], 'head' => []];
    $byAge = ['weight' => [], 'height' => [], 'head' => []];
    $maxAge = 0;

    foreach ($records as $r) {
        $byDate['labels'][] = tgl_indo($r['measured_at'], false, true);
        $byDate['weight'][] = (float) $r['weight'];
        $byDate['height'][] = (float) $r['height'];
        $byDate['head'][] = $r['head_circ'] !== null ? (float) $r['head_circ'] : null;

        $age = age_months_decimal($child['birth_date'], $r['measured_at']);
        if ($age === null || $age > 60) {
            continue;
        }
        $maxAge = max($maxAge, $age);
        $h = (float) $r['height'];
        if ($age < 24 && $r['measure_method'] === 'height') $h += 0.7;
        if ($age >= 24 && $r['measure_method'] === 'length') $h -= 0.7;

        $byAge['weight'][] = ['x' => round($age, 2), 'y' => (float) $r['weight']];
        $byAge['height'][] = ['x' => round($age, 2), 'y' => round($h, 1)];
        if ($r['head_circ'] !== null) {
            $byAge['head'][] = ['x' => round($age, 2), 'y' => (float) $r['head_circ']];
        }
    }

    $who = null;
    if (who_reference_available()) {
        $range = $maxAge <= 22 ? 24 : 60;
        $sex = $child['gender'];
        $lfa = who_curves('lfa', $sex, 0, min(24, $range));
        $hfa = $range > 24 ? who_curves('hfa', $sex, 24, 60) : null;
        $who = [
            'range'  => $range,
            'weight' => who_curves('wfa', $sex, 0, $range),
            'height' => $lfa,
            'height2'=> $hfa,
            'head'   => who_curves('hcfa', $sex, 0, $range),
        ];
    }
    return ['byDate' => $byDate, 'byAge' => $byAge, 'who' => $who];
}
