<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
$user = api_require_roles(['siswa','verifikator','staf_admin','ketua_panitia','super_admin']);
$id = $user['role'] === 'siswa' ? null : filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$sql = 'SELECT p.*, u.nama, u.email, ot.nama_ayah, ot.hp_ayah, ot.kerja_ayah, ot.hasil_ayah, ot.nama_ibu, ot.hp_ibu, ot.kerja_ibu, ot.hasil_ibu, sk.asal_sekolah, sk.tahun_lulus, sk.rencana_karir, jp.jurusan_utama_id, jp.jurusan_cadangan_id FROM pendaftar p JOIN users u ON u.id = p.user_id LEFT JOIN orang_tua ot ON ot.pendaftar_id = p.id LEFT JOIN sekolah_karir sk ON sk.pendaftar_id = p.id LEFT JOIN jurusan_pilihan jp ON jp.pendaftar_id = p.id WHERE ';
if ($user['role'] === 'siswa') {
    $stmt = $pdo->prepare($sql . 'p.user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
} else {
    if (!$id) {
        api_json(false, 'Parameter id wajib dan harus valid.', [], 422);
    }
    $stmt = $pdo->prepare($sql . 'p.id = ? LIMIT 1');
    $stmt->execute([$id]);
}
$applicant = $stmt->fetch();
if (!$applicant) {
    api_json(false, 'Pendaftar tidak ditemukan.', [], 404);
}
$docs = $pdo->prepare('SELECT id, jenis_dokumen, status_dokumen, catatan_revisi, uploaded_at FROM dokumen_pendaftar WHERE pendaftar_id = ? ORDER BY uploaded_at');
$docs->execute([$applicant['id']]);
$payments = $pdo->prepare('SELECT id, jenis_jalur, jumlah_bayar, status_bayar, created_at, validated_at FROM transaksi_pembayaran WHERE pendaftar_id = ? ORDER BY created_at DESC');
$payments->execute([$applicant['id']]);
api_json(true, 'Detail pendaftar berhasil diambil.', ['pendaftar' => $applicant, 'dokumen' => $docs->fetchAll(), 'pembayaran' => $payments->fetchAll()]);