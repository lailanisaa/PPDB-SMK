<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$user = api_require_roles(['staf_admin','ketua_panitia','super_admin']);
$input = api_input();
$paymentId = filter_var($input['transaksi_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($input['status_bayar'] ?? '');
if (!$paymentId || !in_array($status, ['lunas','ditolak'], true)) {
    api_json(false, 'Transaksi atau status tidak valid.', [], 422);
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT pendaftar_id FROM transaksi_pembayaran WHERE id = ? FOR UPDATE');
    $stmt->execute([$paymentId]);
    $applicantId = (int) $stmt->fetchColumn();
    if (!$applicantId) {
        $pdo->rollBack();
        api_json(false, 'Transaksi tidak ditemukan.', [], 404);
    }
    $pdo->prepare('UPDATE transaksi_pembayaran SET status_bayar = ?, validated_by = ?, validated_at = NOW() WHERE id = ?')
        ->execute([$status, $user['id'], $paymentId]);
    $pdo->prepare(
        "UPDATE pendaftar SET status_pembayaran = CASE
            WHEN EXISTS (SELECT 1 FROM transaksi_pembayaran WHERE pendaftar_id = ? AND status_bayar = 'lunas') THEN 'Lunas'
            WHEN EXISTS (SELECT 1 FROM transaksi_pembayaran WHERE pendaftar_id = ? AND status_bayar = 'menunggu') THEN 'Menunggu'
            ELSE 'Ditolak' END WHERE id = ?"
    )->execute([$applicantId, $applicantId, $applicantId]);
    api_audit($pdo, $user['id'], 'keuangan.pembayaran.' . $status);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Payment validation failed: ' . $exception->getMessage());
    api_json(false, 'Validasi pembayaran gagal.', [], 500);
}
api_json(true, 'Status pembayaran berhasil diperbarui.');