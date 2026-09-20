<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$email = trim((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

$statement = $pdo->prepare('SELECT id, nama, sekolah_asal AS sekolah, jurusan, email, password, status FROM siswa WHERE email = ? LIMIT 1');
$statement->execute([$email]);
$siswa = $statement->fetch();

if (!$siswa || !password_verify($password, $siswa['password'])) {
    respond(false, 'Email atau password tidak ditemukan.', [], 401);
}

unset($siswa['password']);
respond(true, 'Login berhasil.', ['siswa' => $siswa]);
