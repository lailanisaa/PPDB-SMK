<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/admin_access.php';

$data = requestData();
$nama = trim((string) ($data['nama'] ?? ''));
$jabatan = trim((string) ($data['jabatan'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');
$accessCode = (string) ($data['access_code'] ?? '');

if (!hash_equals(ADMIN_ACCESS_CODE, $accessCode)) {
    respond(false, 'Kode akses admin salah.', [], 403);
}

if ($nama === '' || $jabatan === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    respond(false, 'Data admin tidak valid. Password minimal 6 karakter.', [], 422);
}

try {
    $statement = $pdo->prepare('INSERT INTO admin (nama, jabatan, email, password) VALUES (?, ?, ?, ?)');
    $statement->execute([$nama, $jabatan, $email, password_hash($password, PASSWORD_DEFAULT)]);
    respond(true, 'Pendaftaran admin berhasil.', ['admin' => ['id' => (int) $pdo->lastInsertId(), 'nama' => $nama, 'jabatan' => $jabatan, 'email' => $email]]);
} catch (PDOException $exception) {
    if ($exception->errorInfo[1] === 1062) {
        respond(false, 'Email admin sudah terdaftar.', [], 409);
    }
    respond(false, 'Pendaftaran admin gagal diproses.', [], 500);
}
