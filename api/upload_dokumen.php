<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$siswaId = (int) ($_POST['siswa_id'] ?? 0);
$documentId = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_POST['document_id'] ?? ''));
$file = $_FILES['file'] ?? null;
$allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

if ($siswaId < 1 || $documentId === '' || !$file || $file['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'File upload tidak valid.', [], 422);
}
if ($file['size'] > 2 * 1024 * 1024) {
    respond(false, 'Ukuran file maksimal 2 MB.', [], 422);
}
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!isset($allowed[$extension]) || $mime !== $allowed[$extension]) {
    respond(false, 'Format file harus PDF, JPG, JPEG, atau PNG.', [], 422);
}

$check = $pdo->prepare('SELECT id FROM siswa WHERE id = ? LIMIT 1');
$check->execute([$siswaId]);
if (!$check->fetchColumn()) {
    respond(false, 'Siswa tidak ditemukan.', [], 404);
}

$directory = dirname(__DIR__) . '/storage/uploads';
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    respond(false, 'Folder penyimpanan tidak dapat dibuat.', [], 500);
}
$storedName = $siswaId . '_' . $documentId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$target = $directory . '/' . $storedName;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    respond(false, 'File gagal disimpan.', [], 500);
}

$statement = $pdo->prepare(
    'INSERT INTO dokumen_siswa (siswa_id, document_id, nama_file, nama_file_server, mime_type, ukuran, status_dokumen)
     VALUES (?, ?, ?, ?, ?, ?, \'Menunggu\')
     ON DUPLICATE KEY UPDATE nama_file = VALUES(nama_file), nama_file_server = VALUES(nama_file_server), mime_type = VALUES(mime_type), ukuran = VALUES(ukuran), status_dokumen = \'Menunggu\', updated_at = CURRENT_TIMESTAMP'
);
$statement->execute([$siswaId, $documentId, basename($file['name']), $storedName, $mime, (int) $file['size']]);
respond(true, 'Dokumen berhasil diunggah.', ['document' => ['id' => $documentId, 'fileName' => basename($file['name']), 'status' => 'menunggu']]);
