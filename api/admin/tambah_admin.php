<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$actor = api_require_roles(['super_admin']);
$input = api_input();
$name = trim((string) ($input['nama'] ?? ''));
$email = mb_strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$role = (string) ($input['role'] ?? '');
if ($name === '' || mb_strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !in_array($role, ['ketua_panitia','verifikator','staf_admin'], true)) {
    api_json(false, 'Nama, email valid, password minimal 12 karakter, dan role staf wajib diisi.', [], 422);
}
try {
    $stmt = $pdo->prepare('INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    $id = (int) $pdo->lastInsertId();
    api_audit($pdo, $actor['id'], 'admin.user.create.' . $role);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        api_json(false, 'Email sudah digunakan.', [], 409);
    }
    error_log('Staff account creation failed: ' . $exception->getMessage());
    api_json(false, 'Akun staf gagal dibuat.', [], 500);
}
api_json(true, 'Akun staf berhasil dibuat.', ['user' => ['id' => $id, 'nama' => $name, 'email' => $email, 'role' => $role]], 201);