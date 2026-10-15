<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
api_require_roles(['verifikator','staf_admin','ketua_panitia','super_admin']);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));
$search = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.nama LIKE ? OR u.email LIKE ? OR p.no_pendaftaran LIKE ? OR p.nisn LIKE ?)';
    $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
    array_push($params, $term, $term, $term, $term);
}
if ($status !== '') {
    if (!in_array($status, ['Menunggu','Diterima','Ditolak/Revisi'], true)) {
        api_json(false, 'Filter status tidak valid.', [], 422);
    }
    $where[] = 'p.status_verifikasi = ?';
    $params[] = $status;
}
$clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$count = $pdo->prepare('SELECT COUNT(*) FROM pendaftar p JOIN users u ON u.id = p.user_id' . $clause);
$count->execute($params);
$total = (int) $count->fetchColumn();
$sql = 'SELECT p.id, p.no_pendaftaran, p.nisn, p.status_verifikasi, p.status_pendaftaran, p.status_pembayaran, p.created_at, u.nama, u.email, j.nama_jurusan FROM pendaftar p JOIN users u ON u.id = p.user_id LEFT JOIN jurusan_pilihan jp ON jp.pendaftar_id = p.id LEFT JOIN jurusan j ON j.id = jp.jurusan_utama_id' . $clause . ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
$stmt = $pdo->prepare($sql);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
api_json(true, 'Data siswa berhasil diambil.', ['items' => $stmt->fetchAll(), 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'pages' => (int) ceil($total / $perPage)]]);