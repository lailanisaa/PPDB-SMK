<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST','PATCH');
api_require_csrf();
$actor = api_require_roles(['ketua_panitia','super_admin']);
$input = api_input();
$applicantId = filter_var($input['pendaftar_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($input['status_verifikasi'] ?? '');
if (!$applicantId || !in_array($status, ['Menunggu','Diterima','Ditolak/Revisi'], true)) {
    api_json(false, 'Pendaftar atau status tidak valid.', [], 422);
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT p.status_verifikasi, jp.jurusan_utama_id FROM pendaftar p LEFT JOIN jurusan_pilihan jp ON jp.pendaftar_id = p.id WHERE p.id = ? FOR UPDATE');
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch();
    if (!$applicant) {
        $pdo->rollBack();
        api_json(false, 'Pendaftar tidak ditemukan.', [], 404);
    }
    $wasAccepted = $applicant['status_verifikasi'] === 'Diterima';
    $majorId = $applicant['jurusan_utama_id'] ? (int) $applicant['jurusan_utama_id'] : null;
    if ($status === 'Diterima' && !$wasAccepted) {
        if (!$majorId) {
            throw new DomainException('Pendaftar belum memilih jurusan utama.');
        }
        $major = $pdo->prepare('SELECT kuota, terisi, status_gelombang FROM jurusan WHERE id = ? FOR UPDATE');
        $major->execute([$majorId]);
        $capacity = $major->fetch();
        if (!$capacity || $capacity['status_gelombang'] !== 'buka' || (int) $capacity['terisi'] >= (int) $capacity['kuota']) {
            throw new DomainException('Kuota jurusan penuh atau gelombang ditutup.');
        }
        $pdo->prepare('UPDATE jurusan SET terisi = terisi + 1 WHERE id = ?')->execute([$majorId]);
    } elseif ($wasAccepted && $status !== 'Diterima' && $majorId) {
        $pdo->prepare('UPDATE jurusan SET terisi = GREATEST(terisi - 1, 0) WHERE id = ?')->execute([$majorId]);
    }
    $pdo->prepare('UPDATE pendaftar SET status_verifikasi = ? WHERE id = ?')->execute([$status, $applicantId]);
    api_audit($pdo, $actor['id'], 'admin.pendaftar.status.' . strtolower(str_replace('/', '_', $status)));
    $pdo->commit();
} catch (DomainException $exception) {
    $pdo->rollBack();
    api_json(false, $exception->getMessage(), [], 409);
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Applicant status update failed: ' . $exception->getMessage());
    api_json(false, 'Status pendaftar gagal diperbarui.', [], 500);
}
api_json(true, 'Status pendaftar berhasil diperbarui.');