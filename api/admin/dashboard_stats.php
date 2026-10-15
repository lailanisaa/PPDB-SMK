<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
api_require_roles(['ketua_panitia','super_admin']);
$stats = $pdo->query("SELECT COUNT(*) AS total, SUM(status_verifikasi = 'Menunggu') AS menunggu, SUM(status_verifikasi = 'Diterima') AS diterima, SUM(status_verifikasi = 'Ditolak/Revisi') AS revisi FROM pendaftar")->fetch();
$departments = $pdo->query('SELECT j.id, j.kode_jurusan, j.nama_jurusan, j.kuota, j.terisi, COUNT(CASE WHEN p.status_verifikasi = \'Diterima\' THEN 1 END) AS diterima FROM jurusan j LEFT JOIN jurusan_pilihan jp ON jp.jurusan_utama_id = j.id LEFT JOIN pendaftar p ON p.id = jp.pendaftar_id GROUP BY j.id ORDER BY j.nama_jurusan')->fetchAll();
$daily = $pdo->query('SELECT DATE(created_at) AS tanggal, COUNT(*) AS total FROM pendaftar WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(created_at) ORDER BY tanggal')->fetchAll();
api_json(true, 'Statistik berhasil diambil.', ['statistik' => $stats, 'per_jurusan' => $departments, 'tren_harian' => $daily]);