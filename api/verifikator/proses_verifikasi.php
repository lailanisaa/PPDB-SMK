<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$user = api_require_roles(['verifikator','ketua_panitia','super_admin']);
$input = api_input();
$documentId = filter_var($input['dokumen_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($input['status_dokumen'] ?? '');
$note = trim((string) ($input['catatan_revisi'] ?? ''));
if (!$documentId || !in_array($status, ['Disetujui','Revisi','Ditolak'], true) || (in_array($status, ['Revisi','Ditolak'], true) && $note === '') || mb_strlen($note) > 1000) {
    api_json(false, 'Data verifikasi tidak valid; catatan wajib untuk revisi atau penolakan.', [], 422);
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT pendaftar_id FROM dokumen_pendaftar WHERE id = ? FOR UPDATE');
    $stmt->execute([$documentId]);
    $applicantId = (int) $stmt->fetchColumn();
    if (!$applicantId) {
        $pdo->rollBack();
        api_json(false, 'Dokumen tidak ditemukan.', [], 404);
    }
    $pdo->prepare('UPDATE dokumen_pendaftar SET status_dokumen = ?, catatan_revisi = ?, verified_by = ?, verified_at = NOW() WHERE id = ?')
        ->execute([$status, $note !== '' ? $note : null, $user['id'], $documentId]);
    if (in_array($status, ['Revisi','Ditolak'], true)) {
        $pdo->prepare("UPDATE pendaftar SET status_verifikasi = 'Ditolak/Revisi' WHERE id = ?")->execute([$applicantId]);
    }
    api_audit($pdo, $user['id'], 'verifikator.dokumen.' . strtolower($status));
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Document verification failed: ' . $exception->getMessage());
    api_json(false, 'Verifikasi gagal disimpan.', [], 500);
}
api_json(true, 'Hasil verifikasi berhasil disimpan.');