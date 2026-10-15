CREATE DATABASE IF NOT EXISTS spmb_kasatrian CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spmb_kasatrian;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','ketua_panitia','verifikator','staf_admin','siswa') NOT NULL,
    status_akun ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status_akun)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jurusan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_jurusan VARCHAR(20) NOT NULL UNIQUE,
    nama_jurusan VARCHAR(120) NOT NULL UNIQUE,
    kuota INT UNSIGNED NOT NULL DEFAULT 0,
    terisi INT UNSIGNED NOT NULL DEFAULT 0,
    status_gelombang ENUM('buka','tutup') NOT NULL DEFAULT 'buka',
    CHECK (terisi <= kuota),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pendaftar (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    no_pendaftaran VARCHAR(32) NOT NULL UNIQUE,
    nisn VARCHAR(20) NULL UNIQUE,
    tempat_lahir VARCHAR(100) NULL,
    tgl_lahir DATE NULL,
    jk ENUM('L','P') NULL,
    agama VARCHAR(30) NULL,
    alamat TEXT NULL,
    rt_rw VARCHAR(12) NULL,
    kelurahan VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kota_kab VARCHAR(100) NULL,
    moda_transportasi VARCHAR(60) NULL,
    tinggi_badan SMALLINT UNSIGNED NULL,
    berat_badan DECIMAL(5,2) UNSIGNED NULL,
    status_verifikasi ENUM('Menunggu','Diterima','Ditolak/Revisi') NOT NULL DEFAULT 'Menunggu',
    status_pendaftaran ENUM('Draft','Terkirim','Diproses','Selesai') NOT NULL DEFAULT 'Draft',
    status_pembayaran ENUM('Belum Bayar','Menunggu','Lunas','Ditolak') NOT NULL DEFAULT 'Belum Bayar',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pendaftar_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pendaftar_status (status_verifikasi, status_pendaftaran),
    INDEX idx_pendaftar_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orang_tua (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nama_ayah VARCHAR(150) NULL,
    hp_ayah VARCHAR(25) NULL,
    kerja_ayah VARCHAR(100) NULL,
    hasil_ayah DECIMAL(14,2) UNSIGNED NULL,
    nama_ibu VARCHAR(150) NULL,
    hp_ibu VARCHAR(25) NULL,
    kerja_ibu VARCHAR(100) NULL,
    hasil_ibu DECIMAL(14,2) UNSIGNED NULL,
    CONSTRAINT fk_orang_tua_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sekolah_karir (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL UNIQUE,
    asal_sekolah VARCHAR(180) NULL,
    tahun_lulus YEAR NULL,
    rencana_karir VARCHAR(255) NULL,
    CONSTRAINT fk_sekolah_karir_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jurusan_pilihan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL UNIQUE,
    jurusan_utama_id BIGINT UNSIGNED NULL,
    jurusan_cadangan_id BIGINT UNSIGNED NULL,
    CONSTRAINT fk_pilihan_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE,
    CONSTRAINT fk_pilihan_utama FOREIGN KEY (jurusan_utama_id) REFERENCES jurusan(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pilihan_cadangan FOREIGN KEY (jurusan_cadangan_id) REFERENCES jurusan(id) ON DELETE RESTRICT,
    CHECK (jurusan_cadangan_id IS NULL OR jurusan_cadangan_id <> jurusan_utama_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dokumen_pendaftar (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL,
    jenis_dokumen ENUM('kartu_keluarga','akta_kelahiran','ijazah','skl','rapor','foto','ktp_ortu','kip','surat_lain') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    status_dokumen ENUM('Menunggu','Disetujui','Revisi','Ditolak') NOT NULL DEFAULT 'Menunggu',
    catatan_revisi VARCHAR(1000) NULL,
    verified_by BIGINT UNSIGNED NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    UNIQUE KEY uq_dokumen_pendaftar_jenis (pendaftar_id, jenis_dokumen),
    CONSTRAINT fk_dokumen_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE,
    CONSTRAINT fk_dokumen_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_dokumen_status (status_dokumen, uploaded_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi_pembayaran (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL,
    jenis_jalur ENUM('kuota_khusus','reguler') NOT NULL,
    jumlah_bayar DECIMAL(14,2) UNSIGNED NOT NULL,
    bukti_bayar VARCHAR(500) NULL,
    status_bayar ENUM('menunggu','lunas','ditolak') NOT NULL DEFAULT 'menunggu',
    validated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validated_at TIMESTAMP NULL,
    CONSTRAINT fk_pembayaran_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE,
    CONSTRAINT fk_pembayaran_validator FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pembayaran_status (status_bayar, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_tes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftar_id BIGINT UNSIGNED NOT NULL UNIQUE,
    tanggal_tes DATETIME NOT NULL,
    sesi VARCHAR(60) NOT NULL,
    lokasi VARCHAR(180) NOT NULL,
    CONSTRAINT fk_jadwal_pendaftar FOREIGN KEY (pendaftar_id) REFERENCES pendaftar(id) ON DELETE CASCADE,
    INDEX idx_jadwal_tanggal (tanggal_tes)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengumuman (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    target ENUM('semua','siswa','panitia') NOT NULL DEFAULT 'semua',
    isi TEXT NOT NULL,
    status_penting TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('draft','terbit') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengumuman_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pengumuman_publish (status, target, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_user_time (user_id, timestamp),
    INDEX idx_audit_action_time (action, timestamp)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    ip_hash CHAR(64) NOT NULL,
    email_hash CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    window_started DATETIME NOT NULL,
    PRIMARY KEY (ip_hash, email_hash),
    INDEX idx_login_window (window_started)
) ENGINE=InnoDB;