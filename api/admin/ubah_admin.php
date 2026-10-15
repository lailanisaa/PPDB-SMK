<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('PATCH','POST');
api_require_csrf();
$actor = api_require_roles(['super_admin']);
$input = api_input();
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id === $actor['id']) {
    api_json(false, 'ID staf tidak valid atau akun sendiri tidak dapat diubah.', [], 422);
}
$updates = [];
$values = [];
foreach (['nama' => 'name', 'role' => 'role', 'status_akun' => 'status'] as $field => $type) {
    if (!array_key_exists($field, $input)) {
        continue;
    }
    $value = $input[$field];
    if ($type === 'name' && (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > 150)) {
        api_json(false, 'Nama tidak valid.', [], 422);
    }
    if ($type === 'role' && !in_array($value, ['ketua_panitia','verifikator','staf_admin'], true)) {
        api_json(false, 'Role tidak valid.', [], 422);
    }
    if ($type === 'status' && !in_array($value, ['aktif','nonaktif'], true)) {
        api_json(false, 'Status akun tidak valid.', [], 422);
    }
    $updates[] = "`$field` = ?";
    $values[] = $type === 'name' ? trim($value) : $value;
}
if (array_key_exists('password', $input)) {
    if (!is_string($input['password']) || strlen($input['password']) < 12) {
        api_json(false, 'Password minimal 12 karakter.', [], 422);
    }
    $updates[] = 'password_hash = ?';
    $values[] = password_hash($input['password'], PASSWORD_DEFAULT);
}
if (!$updates) {
    api_json(false, 'Tidak ada perubahan yang dikirim.', [], 422);
}
$stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ? AND role <> 'super_admin'");
$stmt->execute([...$values, $id]);
if (!$stmt->rowCount()) {
    $exists = $pdo->prepare("SELECT 1 FROM users WHERE id = ? AND role <> 'super_admin'");
    $exists->execute([$id]);
    if (!$exists->fetchColumn()) {
        api_json(false, 'Akun staf tidak ditemukan atau tidak dapat diubah.', [], 404);
    }
}
api_audit($pdo, $actor['id'], 'admin.user.update.' . $id);
api_json(true, 'Akun staf berhasil diperbarui.');