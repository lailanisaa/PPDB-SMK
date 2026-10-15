<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
api_require_roles(['verifikator','ketua_panitia','super_admin']);
$stmt = $pdo->query(
    "SELECT d.id AS dokumen_id, d.pendaftar_id, p.no_pendaftaran, u.nama, d.jenis_dokumen, d.status_dokumen, d.uploaded_at
     FROM dokumen_pendaftar d JOIN pendaftar p ON p.id = d.pendaftar_id JOIN users u ON u.id = p.user_id
     WHERE d.status_dokumen = 'Menunggu' ORDER BY d.uploaded_at ASC LIMIT 500"
);
api_json(true, 'Antrian berkas berhasil diambil.', ['items' => $stmt->fetchAll()]);