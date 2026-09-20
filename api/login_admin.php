<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/admin_access.php';

$data = requestData();
$email = trim((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');
$accessCode = (string) ($data['access_code'] ?? '');

if (!hash_equals(ADMIN_ACCESS_CODE, $accessCode)) {
    respond(false, 'Kode akses admin salah.', [], 403);
}

$statement = $pdo->prepare('SELECT id, nama, jabatan, email, password, status FROM admin WHERE email = ? AND status = \'Aktif\' LIMIT 1');
$statement->execute([$email]);
$admin = $statement->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    respond(false, 'Email atau password admin tidak ditemukan.', [], 401);
}

unset($admin['password']);
respond(true, 'Login admin berhasil.', ['admin' => $admin]);
