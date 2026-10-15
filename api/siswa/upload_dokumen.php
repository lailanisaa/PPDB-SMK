<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$user = api_require_roles(['siswa']);
$kind = (string) ($_POST['jenis_dokumen'] ?? '');
$allowedKinds = ['kartu_keluarga','akta_kelahiran','ijazah','skl','rapor','foto','ktp_ortu','kip','surat_lain'];
$file = $_FILES['file'] ?? null;
if (!in_array($kind, $allowedKinds, true) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
    api_json(false, 'Jenis dokumen atau file tidak valid.', [], 422);
}
if ((int) $file['size'] < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
    api_json(false, 'Ukuran file maksimal 2 MB.', [], 422);
}
$mimeByExtension = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
if (!isset($mimeByExtension[$extension]) || $mime !== $mimeByExtension[$extension]) {
    api_json(false, 'File harus PDF, JPG, PNG, atau WEBP dengan MIME yang sesuai.', [], 422);
}
$stmt = $pdo->prepare('SELECT id FROM pendaftar WHERE user_id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$applicantId = (int) $stmt->fetchColumn();
if (!$applicantId) {
    api_json(false, 'Profil pendaftar tidak ditemukan.', [], 404);
}
$baseDirectory = getenv('UPLOAD_DIR');
if (!$baseDirectory || !str_starts_with($baseDirectory, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $baseDirectory)) {
    api_json(false, 'Penyimpanan privat belum dikonfigurasi.', [], 500);
}
$directory = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $applicantId;
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    api_json(false, 'Penyimpanan dokumen tidak tersedia.', [], 500);
}
$filename = bin2hex(random_bytes(16)) . '.' . $extension;
$target = $directory . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    api_json(false, 'File gagal disimpan.', [], 500);
}
$relativePath = $applicantId . '/' . $filename;
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "INSERT INTO dokumen_pendaftar (pendaftar_id, jenis_dokumen, file_path, status_dokumen)
         VALUES (?, ?, ?, 'Menunggu') ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), status_dokumen = 'Menunggu', catatan_revisi = NULL, verified_by = NULL, verified_at = NULL, uploaded_at = NOW()"
    );
    $stmt->execute([$applicantId, $kind, $relativePath]);
    api_audit($pdo, $user['id'], 'siswa.dokumen.upload.' . $kind);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    @unlink($target);
    error_log('Document upload metadata failed: ' . $exception->getMessage());
    api_json(false, 'Dokumen gagal dicatat.', [], 500);
}
api_json(true, 'Dokumen berhasil diunggah dan menunggu verifikasi.', ['jenis_dokumen' => $kind], 201);