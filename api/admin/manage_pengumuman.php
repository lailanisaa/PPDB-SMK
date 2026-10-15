<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET','POST','PATCH','DELETE');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    $user = api_require_roles(['siswa','verifikator','staf_admin','ketua_panitia','super_admin']);
    $stmt = $pdo->prepare("SELECT id, judul, kategori, target, isi, status_penting, status, created_at FROM pengumuman WHERE status = 'terbit' AND (target = 'semua' OR target = ?) ORDER BY status_penting DESC, created_at DESC LIMIT 200");
    $target = $user['role'] === 'siswa' ? 'siswa' : 'panitia';
    $stmt->execute([$target]);
    api_json(true, 'Pengumuman berhasil diambil.', ['items' => $stmt->fetchAll()]);
}
api_require_csrf();
$actor = api_require_roles(['ketua_panitia','super_admin']);
$input = api_input();
if ($method === 'POST') {
    $title = trim((string) ($input['judul'] ?? ''));
    $category = trim((string) ($input['kategori'] ?? ''));
    $target = (string) ($input['target'] ?? 'semua');
    $content = trim((string) ($input['isi'] ?? ''));
    $important = filter_var($input['status_penting'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $status = (string) ($input['status'] ?? 'draft');
    if ($title === '' || mb_strlen($title) > 200 || $category === '' || !in_array($target, ['semua','siswa','panitia'], true) || $content === '' || !in_array($status, ['draft','terbit'], true)) {
        api_json(false, 'Data pengumuman tidak valid.', [], 422);
    }
    $stmt = $pdo->prepare('INSERT INTO pengumuman (judul, kategori, target, isi, status_penting, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $category, $target, $content, $important ? 1 : 0, $status, $actor['id']]);
    $id = (int) $pdo->lastInsertId();
    api_audit($pdo, $actor['id'], 'admin.pengumuman.create');
    api_json(true, 'Pengumuman berhasil dibuat.', ['id' => $id], 201);
}
$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    api_json(false, 'ID pengumuman tidak valid.', [], 422);
}
if ($method === 'DELETE') {
    $delete = $pdo->prepare('DELETE FROM pengumuman WHERE id = ?');
    $delete->execute([$id]);
    if (!$delete->rowCount()) {
        api_json(false, 'Pengumuman tidak ditemukan.', [], 404);
    }
    api_audit($pdo, $actor['id'], 'admin.pengumuman.delete.' . $id);
    api_json(true, 'Pengumuman berhasil dihapus.');
}
$allowed = ['judul','kategori','target','isi','status_penting','status'];
$updates = [];
$values = [];
foreach ($allowed as $field) {
    if (!array_key_exists($field, $input)) {
        continue;
    }
    $value = $input[$field];
    if (in_array($field, ['target','status'], true) && !in_array($value, $field === 'target' ? ['semua','siswa','panitia'] : ['draft','terbit'], true)) {
        api_json(false, 'Nilai pengumuman tidak valid.', [], 422);
    }
    if ($field === 'status_penting') {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
    $updates[] = "`$field` = ?";
    $values[] = $value;
}
if (!$updates) {
    api_json(false, 'Tidak ada perubahan yang dikirim.', [], 422);
}
$stmt = $pdo->prepare('UPDATE pengumuman SET ' . implode(', ', $updates) . ' WHERE id = ?');
$stmt->execute([...$values, $id]);
if (!$stmt->rowCount()) {
    $exists = $pdo->prepare('SELECT 1 FROM pengumuman WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetchColumn()) {
        api_json(false, 'Pengumuman tidak ditemukan.', [], 404);
    }
}
api_audit($pdo, $actor['id'], 'admin.pengumuman.update.' . $id);
api_json(true, 'Pengumuman berhasil diperbarui.');