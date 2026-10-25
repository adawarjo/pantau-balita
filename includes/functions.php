<?php
/**
 * Fungsi bantu umum.
 */
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

/* =========================================================
 * Output & URL
 * ========================================================= */

/** Escape output HTML */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (BASE_URL_OVERRIDE !== '') {
        return $base = rtrim(BASE_URL_OVERRIDE, '/');
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
    $docRoot = str_replace('\\', '/', $docRoot);
    $appRoot = str_replace('\\', '/', $appRoot);
    if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
        $base = substr($appRoot, strlen($docRoot));
    } else {
        $base = '';
    }
    $base = '/' . trim($base, '/');
    return $base = ($base === '/') ? '' : $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }
    csrf_verify();
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function not_found(string $message = 'Data tidak ditemukan atau Anda tidak memiliki akses.'): void
{
    http_response_code(404);
    set_flash('error', $message);
    redirect('dashboard.php');
}

/* =========================================================
 * Flash message
 * ========================================================= */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* =========================================================
 * Input & validasi
 * ========================================================= */

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function query(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function query_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? null;
    return (is_string($v) && ctype_digit($v)) ? (int) $v : $default;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? null;
    return (is_string($v) && ctype_digit($v)) ? (int) $v : $default;
}

/** Validasi tanggal format Y-m-d */
function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

function valid_time(string $time): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time);
}

/**
 * Parse angka desimal (menerima koma atau titik). Mengembalikan null bila kosong,
 * false bila tidak valid / di luar rentang.
 */
function parse_decimal(string $raw, float $min, float $max)
{
    $raw = str_replace(',', '.', trim($raw));
    if ($raw === '') {
        return null;
    }
    if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $raw)) {
        return false;
    }
    $n = (float) $raw;
    return ($n < $min || $n > $max) ? false : $n;
}

function str_len_ok(string $s, int $max): bool
{
    return mb_strlen($s) <= $max;
}

function null_if_empty(string $s): ?string
{
    return $s === '' ? null : $s;
}

/** Nilai lama formulir untuk ditampilkan kembali */
function old(array $data, string $key, $default = '')
{
    return $data[$key] ?? $default;
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>' : '';
}

function invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

/* =========================================================
 * Tanggal & usia (format Indonesia)
 * ========================================================= */

const BULAN_INDO = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BULAN_SINGKAT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const HARI_INDO = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function today(): string
{
    return date('Y-m-d');
}

/** 2026-10-25 -> 25 Oktober 2026 */
function tgl_indo(?string $date, bool $withDay = false, bool $short = false): string
{
    if (!$date || $date === '0000-00-00') {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    $bulan = $short ? BULAN_SINGKAT[(int) date('n', $ts)] : BULAN_INDO[(int) date('n', $ts)];
    $out = date('j', $ts) . ' ' . $bulan . ' ' . date('Y', $ts);
    if ($withDay) {
        $out = HARI_INDO[(int) date('w', $ts)] . ', ' . $out;
    }
    return $out;
}

function datetime_indo(?string $dt): string
{
    if (!$dt) {
        return '-';
    }
    return tgl_indo($dt) . ' ' . date('H:i', strtotime($dt));
}

/**
 * Komponen usia kalender yang tepat antara tanggal lahir dan tanggal acuan.
 * Mengembalikan null bila tanggal acuan sebelum tanggal lahir.
 */
function age_parts(string $birthDate, ?string $refDate = null): ?array
{
    $birth = new DateTimeImmutable($birthDate);
    $ref = new DateTimeImmutable($refDate ?: today());
    if ($ref < $birth) {
        return null;
    }
    $diff = $birth->diff($ref);
    return [
        'years'        => $diff->y,
        'months'       => $diff->m,
        'days'         => $diff->d,
        'total_months' => $diff->y * 12 + $diff->m,
        'total_days'   => (int) $diff->days,
    ];
}

/**
 * Label usia: < 24 bulan ditampilkan dalam bulan (& hari), >= 24 bulan dalam tahun & bulan.
 */
function age_label(string $birthDate, ?string $refDate = null): string
{
    $a = age_parts($birthDate, $refDate);
    if ($a === null) {
        return '-';
    }
    if ($a['total_months'] < 1) {
        return $a['total_days'] . ' hari';
    }
    if ($a['total_months'] < 24) {
        return $a['total_months'] . ' bulan' . ($a['days'] > 0 ? ' ' . $a['days'] . ' hari' : '');
    }
    return $a['years'] . ' tahun' . ($a['months'] > 0 ? ' ' . $a['months'] . ' bulan' : '');
}

/** Usia dalam bulan desimal (hari / 30,4375) untuk interpolasi standar WHO */
function age_months_decimal(string $birthDate, ?string $refDate = null): ?float
{
    $a = age_parts($birthDate, $refDate);
    return $a === null ? null : $a['total_days'] / 30.4375;
}

function days_until(string $date): int
{
    $t = new DateTimeImmutable(today());
    $d = new DateTimeImmutable($date);
    return (int) $t->diff($d)->format('%r%a');
}

function relative_day_label(string $date): string
{
    $n = days_until($date);
    if ($n === 0) return 'Hari ini';
    if ($n === 1) return 'Besok';
    if ($n === -1) return 'Kemarin';
    return $n > 0 ? "$n hari lagi" : abs($n) . ' hari lalu';
}

/* =========================================================
 * Angka
 * ========================================================= */

function num($value, int $dec = 1): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    $s = number_format((float) $value, $dec, ',', '.');
    // Buang nol di belakang koma (3,50 -> 3,5)
    if ($dec > 0) {
        $s = rtrim(rtrim($s, '0'), ',');
    }
    return $s;
}

/* =========================================================
 * Kepemilikan data
 * ========================================================= */

/** Daftar anak milik user yang login */
function user_children(): array
{
    $st = db()->prepare('SELECT id, full_name, gender, birth_date, photo FROM children WHERE user_id = ? ORDER BY birth_date DESC, full_name');
    $st->execute([current_user_id()]);
    return $st->fetchAll();
}

/** Ambil anak hanya jika milik user yang login */
function find_child(int $childId): ?array
{
    if ($childId <= 0) {
        return null;
    }
    $st = db()->prepare('SELECT * FROM children WHERE id = ? AND user_id = ?');
    $st->execute([$childId, current_user_id()]);
    return $st->fetch() ?: null;
}

function require_child(int $childId): array
{
    $child = find_child($childId);
    if (!$child) {
        not_found('Data anak tidak ditemukan atau bukan milik akun Anda.');
    }
    return $child;
}

/**
 * Ambil satu baris catatan (tabel berelasi child_id) yang dimiliki user login.
 * Nama tabel dibatasi whitelist agar aman dari injeksi.
 */
function find_owned_record(string $table, int $id): ?array
{
    $allowed = ['growth_records', 'developmental_observations', 'immunization_records', 'health_records'];
    if (!in_array($table, $allowed, true) || $id <= 0) {
        return null;
    }
    $sql = "SELECT r.*, c.full_name AS child_name, c.birth_date, c.gender
            FROM {$table} r
            INNER JOIN children c ON c.id = r.child_id
            WHERE r.id = ? AND c.user_id = ?";
    $st = db()->prepare($sql);
    $st->execute([$id, current_user_id()]);
    return $st->fetch() ?: null;
}

function find_owned_reminder(int $id): ?array
{
    $st = db()->prepare(
        'SELECT r.*, c.full_name AS child_name FROM reminders r
         INNER JOIN children c ON c.id = r.child_id
         WHERE r.id = ? AND r.user_id = ? AND c.user_id = ?'
    );
    $st->execute([$id, current_user_id(), current_user_id()]);
    return $st->fetch() ?: null;
}

/* =========================================================
 * Tampilan bantu
 * ========================================================= */

function child_avatar(array $child, string $size = 'md'): string
{
    $cls = 'avatar avatar-' . $size . ' ' . (($child['gender'] ?? 'L') === 'P' ? 'avatar-girl' : 'avatar-boy');
    if (!empty($child['photo'])) {
        return '<img src="' . e(url('children/photo.php?id=' . (int) $child['id'])) . '" alt="Foto ' . e($child['full_name']) . '" class="' . $cls . ' object-fit-cover" loading="lazy">';
    }
    $parts = preg_split('/\s+/', trim($child['full_name'] ?? '?'));
    $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
    return '<span class="' . $cls . '" aria-hidden="true">' . e($initials) . '</span>';
}

function gender_label(string $g): string
{
    return $g === 'P' ? 'Perempuan' : 'Laki-laki';
}

/** Elemen <option> anak */
function child_options(array $children, int $selected = 0, bool $withAll = false, string $allLabel = 'Semua anak'): string
{
    $html = $withAll ? '<option value="0">' . e($allLabel) . '</option>' : '';
    foreach ($children as $c) {
        $html .= '<option value="' . (int) $c['id'] . '"' . ((int) $c['id'] === $selected ? ' selected' : '') . '>' . e($c['full_name']) . '</option>';
    }
    return $html;
}

function empty_state(string $icon, string $title, string $text = '', string $btnUrl = '', string $btnLabel = ''): string
{
    $html = '<div class="empty-state"><div class="empty-icon"><i class="fa-solid ' . e($icon) . '"></i></div>'
        . '<h3 class="h6 fw-bold mb-1">' . e($title) . '</h3>';
    if ($text) {
        $html .= '<p class="text-muted small mb-3">' . e($text) . '</p>';
    }
    if ($btnUrl) {
        $html .= '<a href="' . e(url($btnUrl)) . '" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>' . e($btnLabel) . '</a>';
    }
    return $html . '</div>';
}

/** Tombol hapus (POST + CSRF + konfirmasi SweetAlert2) */
function delete_button(string $action, int $id, string $message, string $label = '', string $class = 'btn btn-sm btn-outline-danger'): string
{
    return '<form method="post" action="' . e(url($action)) . '" class="d-inline js-confirm-delete" data-message="' . e($message) . '">'
        . csrf_field()
        . '<input type="hidden" name="id" value="' . (int) $id . '">'
        . '<button type="submit" class="' . e($class) . '" title="Hapus" aria-label="Hapus"><i class="fa-solid fa-trash-can"></i>' . ($label ? ' ' . e($label) : '') . '</button>'
        . '</form>';
}

/* =========================================================
 * Pagination
 * ========================================================= */

function paginate(int $total, int $perPage = 10): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, query_int('page', 1)), $pages);
    return ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

function pagination_links(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $params = $_GET;
    $link = function (int $n) use ($params) {
        $params['page'] = $n;
        return '?' . http_build_query($params);
    };
    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm justify-content-center flex-wrap mb-0">';
    $html .= '<li class="page-item' . ($p['page'] <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(max(1, $p['page'] - 1))) . '">&laquo;</a></li>';
    $start = max(1, $p['page'] - 2);
    $end = min($p['pages'], $p['page'] + 2);
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="' . e($link($i)) . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item' . ($p['page'] >= $p['pages'] ? ' disabled' : '') . '"><a class="page-link" href="' . e($link(min($p['pages'], $p['page'] + 1))) . '">&raquo;</a></li>';
    return $html . '</ul></nav>';
}

/* =========================================================
 * Label enumerasi
 * ========================================================= */

const DOMAIN_LABELS = [
    'motorik_kasar' => 'Motorik Kasar',
    'motorik_halus' => 'Motorik Halus',
    'bahasa'        => 'Bahasa & Komunikasi',
    'sosial'        => 'Sosial & Kemandirian',
];
const DOMAIN_ICONS = [
    'motorik_kasar' => 'fa-person-running',
    'motorik_halus' => 'fa-hand',
    'bahasa'        => 'fa-comments',
    'sosial'        => 'fa-people-group',
];
const OBS_STATUS = [
    'sudah'         => 'Sudah Dapat',
    'belum'         => 'Belum Dapat',
    'belum_diamati' => 'Belum Diamati',
];
const IMM_STATUS = [
    'diberikan'   => 'Sudah diberikan',
    'dijadwalkan' => 'Dijadwalkan',
    'ditunda'     => 'Ditunda',
];
const IMM_SOURCE = [
    'buku_kia'         => 'Dicocokkan dengan Buku KIA',
    'kartu_faskes'     => 'Kartu/catatan fasilitas kesehatan',
    'ingatan_orangtua' => 'Ingatan orangtua (belum diverifikasi)',
    'lainnya'          => 'Lainnya',
];
const HEALTH_TYPES = [
    'sakit'       => 'Sakit / keluhan',
    'kunjungan'   => 'Kunjungan faskes',
    'pemeriksaan' => 'Pemeriksaan rutin',
    'obat'        => 'Pemberian obat',
    'alergi'      => 'Alergi',
    'lainnya'     => 'Lainnya',
];
const HEALTH_ICONS = [
    'sakit'       => 'fa-head-side-cough',
    'kunjungan'   => 'fa-hospital',
    'pemeriksaan' => 'fa-stethoscope',
    'obat'        => 'fa-pills',
    'alergi'      => 'fa-triangle-exclamation',
    'lainnya'     => 'fa-notes-medical',
];
const REMINDER_CATEGORIES = [
    'pertumbuhan'  => 'Pengukuran pertumbuhan',
    'imunisasi'    => 'Imunisasi',
    'pemeriksaan'  => 'Pemeriksaan kesehatan',
    'perkembangan' => 'Tinjau perkembangan',
    'lainnya'      => 'Lainnya',
];
const REMINDER_ICONS = [
    'pertumbuhan'  => 'fa-weight-scale',
    'imunisasi'    => 'fa-syringe',
    'pemeriksaan'  => 'fa-stethoscope',
    'perkembangan' => 'fa-puzzle-piece',
    'lainnya'      => 'fa-bell',
];

/* =========================================================
 * Unggah foto anak
 * ========================================================= */

/** Path aman ke file foto (hanya nama file yang dihasilkan aplikasi) */
function child_photo_path(?string $file): ?string
{
    if (!$file || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $file)) {
        return null;
    }
    return UPLOAD_DIR . '/' . $file;
}

/**
 * Validasi & simpan foto dari $_FILES[$field].
 * @return array [string|null $filename, string|null $error]  (null,null bila tidak ada unggahan)
 */
function handle_photo_upload(string $field): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $f = $_FILES[$field];
    if (!is_int($f['error']) || $f['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Unggahan foto gagal. Pastikan ukuran maksimal 2 MB.'];
    }
    if ($f['size'] <= 0 || $f['size'] > UPLOAD_MAX_BYTES) {
        return [null, 'Ukuran foto maksimal 2 MB.'];
    }
    $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    if (!isset($allowedExt[$ext])) {
        return [null, 'Format foto harus JPG, PNG, atau WEBP.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($mimeMap[$mime]) || $mimeMap[$mime] !== $allowedExt[$ext]) {
        return [null, 'Isi berkas bukan gambar yang valid.'];
    }
    if (@getimagesize($f['tmp_name']) === false) {
        return [null, 'Berkas gambar rusak atau tidak valid.'];
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0750, true)) {
        return [null, 'Folder unggahan tidak dapat dibuat.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $mimeMap[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        return [null, 'Gagal menyimpan foto.'];
    }
    @chmod(UPLOAD_DIR . '/' . $name, 0640);
    return [$name, null];
}

function delete_child_photo(?string $file): void
{
    $path = child_photo_path($file);
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

/* =========================================================
 * Change Log
 * ========================================================= */

const CHANGE_TYPES = [
    'baru'        => ['label' => 'Baru', 'icon' => 'fa-star', 'color' => 'success'],
    'peningkatan' => ['label' => 'Peningkatan', 'icon' => 'fa-arrow-trend-up', 'color' => 'info'],
    'perbaikan'   => ['label' => 'Perbaikan', 'icon' => 'fa-wrench', 'color' => 'warning'],
    'keamanan'    => ['label' => 'Keamanan', 'icon' => 'fa-shield-halved', 'color' => 'secondary'],
];

/** Daftar rilis, terbaru di atas */
function app_changelog(): array
{
    static $log = null;
    if ($log === null) {
        $log = require APP_ROOT . '/includes/changelog.php';
    }
    return $log;
}
