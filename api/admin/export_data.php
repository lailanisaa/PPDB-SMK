<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
$actor = api_require_roles(['ketua_panitia','super_admin']);
$stmt = $pdo->query('SELECT p.no_pendaftaran, u.nama, u.email, p.nisn, p.status_verifikasi, p.status_pendaftaran, p.status_pembayaran, j.nama_jurusan, p.created_at FROM pendaftar p JOIN users u ON u.id = p.user_id LEFT JOIN jurusan_pilihan jp ON jp.pendaftar_id = p.id LEFT JOIN jurusan j ON j.id = jp.jurusan_utama_id ORDER BY p.created_at DESC LIMIT 10000');
$rows = $stmt->fetchAll();
api_audit($pdo, $actor['id'], 'admin.export_data.csv');
header_remove('Content-Type');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="spmb-pendaftar-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");
$columns = ['no_pendaftaran','nama','email','nisn','status_verifikasi','status_pendaftaran','status_pembayaran','nama_jurusan','created_at'];
fputcsv($out, $columns);
foreach ($rows as $row) {
    $values = [];
    foreach ($columns as $column) {
        $value = (string) ($row[$column] ?? '');
        $values[] = preg_match('/^[\x00-\x20]*[=+@\-]/u', $value) ? "'" . $value : $value;
    }
    fputcsv($out, $values);
}
fclose($out);