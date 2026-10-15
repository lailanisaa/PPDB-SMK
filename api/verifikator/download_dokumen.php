<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
$user = api_require_roles(['verifikator','ketua_panitia','super_admin']);
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId) {
    api_json(false, 'ID dokumen tidak valid.', [], 422);
}
$stmt = $pdo->prepare('SELECT file_path FROM dokumen_pendaftar WHERE id = ? LIMIT 1');
$stmt->execute([$documentId]);
$relative = $stmt->fetchColumn();
if (!is_string($relative) || !preg_match('#^[0-9]+/[a-f0-9]{32}\.(pdf|jpg|jpeg|png|webp)$#', $relative)) {
    api_json(false, 'Dokumen tidak ditemukan.', [], 404);
}
$base = getenv('UPLOAD_DIR');
$root = $base ? realpath($base) : false;
$filePath = $root ? realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative)) : false;
$rootPrefix = $root ? rtrim($root, '/\\') . DIRECTORY_SEPARATOR : '';
if (!$root || !$filePath || !str_starts_with(strtolower($filePath), strtolower($rootPrefix)) || !is_file($filePath) || !is_readable($filePath)) {
    api_json(false, 'File dokumen tidak tersedia.', [], 404);
}
$types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
api_audit($pdo, $user['id'], 'verifikator.dokumen.download');
header_remove('Content-Type');
header('Content-Type: ' . $types[$extension]);
header('Content-Disposition: attachment; filename="dokumen-' . $documentId . '.' . $extension . '"');
header('Content-Length: ' . (string) filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
readfile($filePath);