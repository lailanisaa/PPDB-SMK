<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET','POST','PATCH','DELETE');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    api_require_roles(['ketua_panitia','super_admin']);
    api_json(true, 'Data jurusan berhasil diambil.', ['items' => $pdo->query('SELECT id, kode_jurusan, nama_jurusan, kuota, terisi, status_gelombang FROM jurusan ORDER BY nama_jurusan')->fetchAll()]);
}
api_require_csrf();
$actor = api_require_roles(['super_admin']);
$input = api_input();
if ($method === 'POST') {
    $code = strtoupper(trim((string) ($input['kode_jurusan'] ?? '')));
    $name = trim((string) ($input['nama_jurusan'] ?? ''));
    $quota = filter_var($input['kuota'] ?? null, FILTER_VALIDATE_INT);
    $status = (string) ($input['status_gelombang'] ?? 'buka');
    if ($code === '' || $name === '' || $quota === false || $quota < 0 || !in_array($status, ['buka','tutup'], true)) {
        api_json(false, 'Data jurusan tidak valid.', [], 422);
    }
    try {
        $pdo->prepare('INSERT INTO jurusan (kode_jurusan, nama_jurusan, kuota, status_gelombang) VALUES (?, ?, ?, ?)')->execute([$code, $name, $quota, $status]);
        api_audit($pdo, $actor['id'], 'admin.jurusan.create');
    } catch (PDOException $exception) {
        api_json(false, $exception->getCode() === '23000' ? 'Kode atau nama jurusan sudah digunakan.' : 'Jurusan gagal dibuat.', [], $exception->getCode() === '23000' ? 409 : 500);
    }
    api_json(true, 'Jurusan berhasil ditambahkan.', ['id' => (int) $pdo->lastInsertId()], 201);
}
$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    api_json(false, 'ID jurusan tidak valid.', [], 422);
}
if ($method === 'DELETE') {
    $pdo->beginTransaction();
    try {
        $exists = $pdo->prepare('SELECT id FROM jurusan WHERE id = ? FOR UPDATE');
        $exists->execute([$id]);
        if (!$exists->fetchColumn()) {
            $pdo->rollBack();
            api_json(false, 'Jurusan tidak ditemukan.', [], 404);
        }
        $pdo->prepare('DELETE FROM jurusan WHERE id = ?')->execute([$id]);
        api_audit($pdo, $actor['id'], 'admin.jurusan.delete.' . $id);
        $pdo->commit();
    } catch (PDOException $exception) {
        $pdo->rollBack();
        api_json(false, 'Jurusan yang sudah dipilih pendaftar tidak dapat dihapus.', [], 409);
    }
    api_json(true, 'Jurusan berhasil dihapus.');
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT terisi FROM jurusan WHERE id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $filled = $stmt->fetchColumn();
    if ($filled === false) {
        $pdo->rollBack();
        api_json(false, 'Jurusan tidak ditemukan.', [], 404);
    }
    $updates = [];
    $values = [];
    foreach (['kode_jurusan' => 'string','nama_jurusan' => 'string','kuota' => 'int','status_gelombang' => 'status'] as $field => $type) {
        if (!array_key_exists($field, $input)) {
            continue;
        }
        $value = $input[$field];
        if ($type === 'status' && !in_array($value, ['buka','tutup'], true)) {
            throw new InvalidArgumentException('Status gelombang tidak valid.');
        }
        if ($type === 'int' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < (int) $filled)) {
            throw new InvalidArgumentException('Kuota harus bilangan bulat dan tidak boleh di bawah jumlah terisi.');
        }
        if ($type === 'string' && (!is_string($value) || trim($value) === '')) {
            throw new InvalidArgumentException('Data jurusan tidak valid.');
        }
        $updates[] = "`$field` = ?";
        $values[] = $type === 'string' ? trim($value) : $value;
    }
    if (!$updates) {
        throw new InvalidArgumentException('Tidak ada perubahan yang dikirim.');
    }
    $pdo->prepare('UPDATE jurusan SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute([...$values, $id]);
    api_audit($pdo, $actor['id'], 'admin.jurusan.update.' . $id);
    $pdo->commit();
} catch (InvalidArgumentException $exception) {
    $pdo->rollBack();
    api_json(false, $exception->getMessage(), [], 422);
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Department update failed: ' . $exception->getMessage());
    api_json(false, 'Jurusan gagal diperbarui.', [], 500);
}
api_json(true, 'Jurusan berhasil diperbarui.');