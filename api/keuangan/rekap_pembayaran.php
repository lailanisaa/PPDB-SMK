<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
api_require_roles(['staf_admin','ketua_panitia','super_admin']);
$status = (string) ($_GET['status'] ?? '');
$sql = 'SELECT t.id, p.no_pendaftaran, u.nama, t.jenis_jalur, t.jumlah_bayar, t.status_bayar, t.created_at, t.validated_at FROM transaksi_pembayaran t JOIN pendaftar p ON p.id = t.pendaftar_id JOIN users u ON u.id = p.user_id';
$params = [];
if ($status !== '') {
    if (!in_array($status, ['menunggu','lunas','ditolak'], true)) {
        api_json(false, 'Filter status tidak valid.', [], 422);
    }
    $sql .= ' WHERE t.status_bayar = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY t.created_at DESC LIMIT 1000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
api_json(true, 'Rekap pembayaran berhasil diambil.', ['items' => $stmt->fetchAll()]);