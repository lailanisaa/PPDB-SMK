<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$statement = $pdo->query("SELECT id, nama, sekolah_asal AS sekolah, jurusan, DATE_FORMAT(tanggal_daftar, '%d %b %Y') AS tanggal, '-' AS nilai, status FROM siswa ORDER BY tanggal_daftar DESC");
respond(true, 'Data pendaftar berhasil diambil.', ['pendaftar' => $statement->fetchAll()]);
