<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$siswaId = (int) ($_GET['siswa_id'] ?? 0);
if ($siswaId < 1) {
    respond(false, 'ID siswa tidak valid.', [], 422);
}

$statement = $pdo->prepare('SELECT document_id, nama_file, nama_file_server, mime_type, ukuran, status_dokumen FROM dokumen_siswa WHERE siswa_id = ? ORDER BY document_id');
$statement->execute([$siswaId]);
$documents = array_map(static function (array $document): array {
    return [
        'id' => $document['document_id'],
        'title' => $document['nama_file'],
        'fileName' => $document['nama_file'],
        'mimeType' => $document['mime_type'],
        'size' => (int) $document['ukuran'],
        'status' => strtolower($document['status_dokumen']),
        'url' => '../storage/uploads/' . rawurlencode($document['nama_file_server']),
    ];
}, $statement->fetchAll());
respond(true, 'Dokumen berhasil diambil.', ['documents' => $documents]);
