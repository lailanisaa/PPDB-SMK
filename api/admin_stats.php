<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$total = (int) $pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
$waiting = (int) $pdo->query("SELECT COUNT(*) FROM siswa WHERE status IN ('Menunggu', 'Diproses')")->fetchColumn();
$accepted = (int) $pdo->query("SELECT COUNT(*) FROM siswa WHERE status = 'Diterima'")->fetchColumn();
$rejected = (int) $pdo->query("SELECT COUNT(*) FROM siswa WHERE status = 'Ditolak'")->fetchColumn();
$distribution = $pdo->query('SELECT jurusan AS label, COUNT(*) AS total FROM siswa GROUP BY jurusan ORDER BY total DESC')->fetchAll();
$dailyRows = $pdo->query("SELECT DATE(tanggal_daftar) AS day, COUNT(*) AS total FROM siswa WHERE tanggal_daftar >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(tanggal_daftar) ORDER BY day")->fetchAll();
$daily = array_map(static function (array $row): array {
    return ['label' => date('d M', strtotime($row['day'])), 'total' => (int) $row['total']];
}, $dailyRows);

respond(true, 'Statistik admin berhasil diambil.', [
    'stats' => ['total' => $total, 'waiting' => $waiting, 'accepted' => $accepted, 'rejected' => $rejected],
    'distribution' => $distribution,
    'daily' => $daily,
]);
