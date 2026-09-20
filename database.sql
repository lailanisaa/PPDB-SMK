CREATE DATABASE IF NOT EXISTS spmb_kasatrian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spmb_kasatrian;

CREATE TABLE IF NOT EXISTS siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    sekolah_asal VARCHAR(150) NOT NULL,
    jurusan VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Menunggu', 'Diproses', 'Diterima', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
    tanggal_daftar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    jabatan VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
    tanggal_daftar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS formulir_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT UNSIGNED NOT NULL UNIQUE,
    nik CHAR(16) NOT NULL,
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    no_wa VARCHAR(25) NOT NULL,
    alamat TEXT NOT NULL,
    nama_ayah VARCHAR(150) NOT NULL,
    nama_ibu VARCHAR(150) NOT NULL,
    nisn VARCHAR(30) NOT NULL,
    tahun_lulus YEAR NOT NULL,
    jurusan_utama VARCHAR(100) NOT NULL,
    jurusan_cadangan VARCHAR(100) NOT NULL,
    jadwal_id INT UNSIGNED NOT NULL DEFAULT 0,
    status_formulir ENUM('Draft', 'Terkirim', 'Diverifikasi', 'Revisi') NOT NULL DEFAULT 'Draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_formulir_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dokumen_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT UNSIGNED NOT NULL,
    document_id VARCHAR(50) NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    nama_file_server VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    ukuran INT UNSIGNED NOT NULL,
    status_dokumen ENUM('Menunggu', 'Valid', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_siswa_document (siswa_id, document_id),
    CONSTRAINT fk_dokumen_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('siswa', 'admin') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reset_account (account_type, account_id),
    INDEX idx_reset_expiry (expires_at)
);

CREATE TABLE IF NOT EXISTS jadwal_tes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    sesi VARCHAR(50) NOT NULL,
    jam VARCHAR(30) NOT NULL,
    jenis VARCHAR(100) NOT NULL,
    kuota INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO jadwal_tes (tanggal, sesi, jam, jenis, kuota)
SELECT '2026-06-20', 'Pagi', '08:00-10:00', 'Tes Akademik', 50
WHERE NOT EXISTS (SELECT 1 FROM jadwal_tes WHERE tanggal = '2026-06-20' AND sesi = 'Pagi');
INSERT INTO jadwal_tes (tanggal, sesi, jam, jenis, kuota)
SELECT '2026-06-20', 'Siang', '13:00-15:00', 'Tes Akademik', 50
WHERE NOT EXISTS (SELECT 1 FROM jadwal_tes WHERE tanggal = '2026-06-20' AND sesi = 'Siang');
INSERT INTO jadwal_tes (tanggal, sesi, jam, jenis, kuota)
SELECT '2026-06-21', 'Pagi', '08:00-10:00', 'Tes Wawancara', 50
WHERE NOT EXISTS (SELECT 1 FROM jadwal_tes WHERE tanggal = '2026-06-21' AND sesi = 'Pagi');
