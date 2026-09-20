<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$nama = trim((string) ($data['nama'] ?? ''));
$sekolah = trim((string) ($data['sekolah'] ?? ''));
$jurusan = trim((string) ($data['jurusan'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($nama === '' || $sekolah === '' || $jurusan === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    respond(false, 'Data pendaftaran tidak valid. Password minimal 6 karakter.', [], 422);
}

try {
    $statement = $pdo->prepare('INSERT INTO siswa (nama, sekolah_asal, jurusan, email, password) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$nama, $sekolah, $jurusan, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) $pdo->lastInsertId();
    respond(true, 'Pendaftaran berhasil.', ['siswa' => ['id' => $id, 'nama' => $nama, 'sekolah' => $sekolah, 'jurusan' => $jurusan, 'email' => $email, 'status' => 'Menunggu']]);
} catch (PDOException $exception) {
    if ($exception->errorInfo[1] === 1062) {
        respond(false, 'Email siswa sudah terdaftar.', [], 409);
    }
    respond(false, 'Pendaftaran gagal diproses.', [], 500);
}
