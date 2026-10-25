-- =====================================================================
-- Pantau Balita - Struktur Database
-- MySQL 5.7+ / MariaDB 10.3+ (InnoDB, utf8mb4)
-- Semua waktu pencatatan (created_at/updated_at) disimpan dalam waktu
-- server; aplikasi mengatur zona waktu PHP & sesi MySQL ke Asia/Jakarta
-- (lihat config/config.php) agar konsisten.
-- Tidak ada akun bawaan: setiap orangtua mendaftar sendiri.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS health_records;
DROP TABLE IF EXISTS immunization_records;
DROP TABLE IF EXISTS immunization_schedule;
DROP TABLE IF EXISTS developmental_observations;
DROP TABLE IF EXISTS developmental_indicators;
DROP TABLE IF EXISTS growth_records;
DROP TABLE IF EXISTS who_growth_reference;
DROP TABLE IF EXISTS children;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. users : akun orangtua (satu-satunya level pengguna)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    phone         VARCHAR(20)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    disclaimer_version     VARCHAR(10) NULL COMMENT 'versi disclaimer yang disetujui',
    disclaimer_accepted_at DATETIME    NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pembatasan percobaan login (anti brute force)
CREATE TABLE login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(150) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL,
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_email_time (email, attempted_at),
    KEY idx_login_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. children : profil anak, dimiliki oleh satu user
--    Hapus user  -> seluruh data anak & catatannya ikut terhapus (CASCADE)
-- ---------------------------------------------------------------------
CREATE TABLE children (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    photo          VARCHAR(100) NULL COMMENT 'nama file acak di uploads/children',
    gender         ENUM('L','P') NOT NULL COMMENT 'L = laki-laki, P = perempuan',
    birth_place    VARCHAR(100) NULL,
    birth_date     DATE         NOT NULL,
    birth_weight   DECIMAL(4,2) NULL COMMENT 'kg',
    birth_length   DECIMAL(4,1) NULL COMMENT 'cm',
    birth_history  TEXT         NULL,
    notes          TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_children_user (user_id),
    KEY idx_children_name (full_name),
    CONSTRAINT fk_children_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. growth_records : pengukuran antropometri
-- ---------------------------------------------------------------------
CREATE TABLE growth_records (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id        INT UNSIGNED NOT NULL,
    measured_at     DATE         NOT NULL,
    weight          DECIMAL(5,2) NOT NULL COMMENT 'kg',
    height          DECIMAL(5,1) NOT NULL COMMENT 'cm',
    measure_method  ENUM('length','height') NOT NULL DEFAULT 'length'
                    COMMENT 'length = berbaring (panjang), height = berdiri (tinggi)',
    head_circ       DECIMAL(4,1) NULL COMMENT 'lingkar kepala, cm',
    muac            DECIMAL(4,1) NULL COMMENT 'lingkar lengan atas, cm',
    notes           TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_growth_child_date (child_id, measured_at),
    CONSTRAINT fk_growth_child FOREIGN KEY (child_id)
        REFERENCES children (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. developmental_indicators : daftar indikator perkembangan per usia
--    is_official = 1 hanya boleh diisi indikator yang redaksinya sudah
--    dicocokkan dengan pedoman resmi. Data awal di-seed dengan
--    is_official = 0 (lihat README bagian "Data Referensi").
-- ---------------------------------------------------------------------
CREATE TABLE developmental_indicators (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    age_min_months  TINYINT UNSIGNED NOT NULL,
    age_max_months  TINYINT UNSIGNED NOT NULL,
    age_group_label VARCHAR(40) NOT NULL,
    domain          ENUM('motorik_kasar','motorik_halus','bahasa','sosial') NOT NULL,
    description     VARCHAR(255) NOT NULL,
    source          VARCHAR(255) NOT NULL,
    is_official     TINYINT(1) NOT NULL DEFAULT 0,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_indicator_age (age_min_months, age_max_months),
    KEY idx_indicator_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. developmental_observations : hasil observasi orangtua
-- ---------------------------------------------------------------------
CREATE TABLE developmental_observations (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id      INT UNSIGNED NOT NULL,
    indicator_id  INT UNSIGNED NOT NULL,
    observed_at   DATE NOT NULL,
    status        ENUM('sudah','belum','belum_diamati') NOT NULL DEFAULT 'belum_diamati',
    notes         VARCHAR(500) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_obs_child_indicator_date (child_id, indicator_id, observed_at),
    KEY idx_obs_child_date (child_id, observed_at),
    KEY idx_obs_indicator (indicator_id),
    CONSTRAINT fk_obs_child FOREIGN KEY (child_id)
        REFERENCES children (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_obs_indicator FOREIGN KEY (indicator_id)
        REFERENCES developmental_indicators (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Referensi jadwal imunisasi rutin (hanya sebagai panduan tampilan;
-- aplikasi tidak menyimpulkan kelengkapan imunisasi anak)
-- ---------------------------------------------------------------------
CREATE TABLE immunization_schedule (
    id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    vaccine_name           VARCHAR(60)  NOT NULL,
    dose_label             VARCHAR(30)  NOT NULL,
    recommended_age_months TINYINT UNSIGNED NOT NULL,
    age_label              VARCHAR(40)  NOT NULL,
    notes                  VARCHAR(255) NULL,
    source                 VARCHAR(255) NOT NULL,
    sort_order             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_schedule_age (recommended_age_months)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. immunization_records
-- ---------------------------------------------------------------------
CREATE TABLE immunization_records (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id         INT UNSIGNED NOT NULL,
    vaccine_name     VARCHAR(60)  NOT NULL,
    dose             VARCHAR(30)  NULL,
    status           ENUM('diberikan','dijadwalkan','ditunda') NOT NULL DEFAULT 'diberikan',
    given_at         DATE         NULL COMMENT 'wajib bila status = diberikan',
    location         VARCHAR(120) NULL,
    batch_number     VARCHAR(60)  NULL,
    reaction_notes   TEXT         NULL,
    next_date        DATE         NULL COMMENT 'tanggal imunisasi berikutnya (diisi orangtua)',
    record_source    ENUM('buku_kia','kartu_faskes','ingatan_orangtua','lainnya')
                     NOT NULL DEFAULT 'ingatan_orangtua'
                     COMMENT 'asal data: menandai apakah sudah dicocokkan dengan dokumen',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_imm_child_given (child_id, given_at),
    KEY idx_imm_next (next_date),
    KEY idx_imm_vaccine (vaccine_name),
    CONSTRAINT fk_imm_child FOREIGN KEY (child_id)
        REFERENCES children (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. health_records
-- ---------------------------------------------------------------------
CREATE TABLE health_records (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    child_id        INT UNSIGNED NOT NULL,
    event_date      DATE         NOT NULL,
    record_type     ENUM('sakit','kunjungan','alergi','obat','pemeriksaan','lainnya') NOT NULL,
    symptoms        TEXT         NULL,
    temperature     DECIMAL(3,1) NULL COMMENT 'derajat Celsius',
    medicine_name   VARCHAR(120) NULL,
    medicine_dose   VARCHAR(120) NULL COMMENT 'sesuai resep / petunjuk tenaga kesehatan',
    allergies       VARCHAR(255) NULL,
    facility_visit  TEXT         NULL,
    notes           TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_health_child_date (child_id, event_date),
    KEY idx_health_type (record_type),
    CONSTRAINT fk_health_child FOREIGN KEY (child_id)
        REFERENCES children (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. reminders : pengingat (ditampilkan di dashboard setelah login)
-- ---------------------------------------------------------------------
CREATE TABLE reminders (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    child_id    INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    category    ENUM('pertumbuhan','imunisasi','pemeriksaan','perkembangan','lainnya') NOT NULL,
    due_date    DATE         NOT NULL,
    due_time    TIME         NULL,
    notes       TEXT         NULL,
    is_done     TINYINT(1)   NOT NULL DEFAULT 0,
    done_at     DATETIME     NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rem_user_due (user_id, is_done, due_date),
    KEY idx_rem_child (child_id),
    CONSTRAINT fk_rem_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rem_child FOREIGN KEY (child_id)
        REFERENCES children (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Referensi WHO Child Growth Standards (parameter LMS)
-- indicator:
--   wfa   = berat menurut umur (bulan 0-60)
--   lfa   = panjang menurut umur (bulan 0-24, berbaring)
--   hfa   = tinggi menurut umur (bulan 24-60, berdiri)
--   wfl   = berat menurut panjang (45-110 cm, usia 0-24 bln)
--   wfh   = berat menurut tinggi (65-120 cm, usia 24-60 bln)
--   bmi_l = IMT menurut umur (bulan 0-24)
--   bmi_h = IMT menurut umur (bulan 24-60)
--   hcfa  = lingkar kepala menurut umur (bulan 0-60)
-- x = umur dalam bulan, atau panjang/tinggi dalam cm (wfl/wfh)
-- ---------------------------------------------------------------------
CREATE TABLE who_growth_reference (
    indicator  VARCHAR(8)   NOT NULL,
    sex        ENUM('L','P') NOT NULL,
    x          DECIMAL(5,1) NOT NULL,
    l          DECIMAL(10,6) NOT NULL,
    m          DECIMAL(10,5) NOT NULL,
    s          DECIMAL(10,6) NOT NULL,
    PRIMARY KEY (indicator, sex, x)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
