<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$siswaId = (int) ($data['siswa_id'] ?? 0);
$status = (string) ($data['status'] ?? '');
$catatan = trim((string) ($data['catatan'] ?? ''));
$allowed = ['Menunggu', 'Diproses', 'Diterima', 'Ditolak'];

if ($siswaId < 1 || !in_array($status, $allowed, true)) {
    respond(false, 'ID siswa atau status tidak valid.', [], 422);
}
$statement = $pdo->prepare('UPDATE siswa SET status = ? WHERE id = ?');
$statement->execute([$status, $siswaId]);
if ($statement->rowCount() === 0) {
    respond(false, 'Siswa tidak ditemukan.', [], 404);
}
respond(true, 'Status pendaftar berhasil diperbarui.', ['siswa_id' => $siswaId, 'status' => $status, 'catatan' => $catatan]);
