-- =====================================================================
-- Migrasi v1.3.0: persetujuan disclaimer
-- Jalankan SEKALI pada database yang dibuat sebelum versi 1.3.0.
-- Instalasi baru tidak perlu menjalankan file ini (sudah ada di database.sql).
-- Akun lama akan diminta menyetujui disclaimer saat login berikutnya.
-- =====================================================================
ALTER TABLE users
    ADD COLUMN disclaimer_version VARCHAR(10) NULL COMMENT 'versi disclaimer yang disetujui' AFTER password_hash,
    ADD COLUMN disclaimer_accepted_at DATETIME NULL AFTER disclaimer_version;
