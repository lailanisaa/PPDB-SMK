<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$statement = $pdo->query("SELECT id, DATE_FORMAT(tanggal, '%d %b %Y') AS tanggal, sesi, jam, jenis, kuota FROM jadwal_tes WHERE status = 'Aktif' ORDER BY tanggal, jam");
respond(true, 'Jadwal tes berhasil diambil.', ['jadwal' => $statement->fetchAll()]);
