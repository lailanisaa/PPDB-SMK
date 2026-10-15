<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
$input = api_input();
$name = trim((string) ($input['nama'] ?? ''));
$email = mb_strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
if ($name === '' || mb_strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    api_json(false, 'Nama, email valid, dan password minimal 12 karakter wajib diisi.', [], 422);
}
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, \'siswa\')')
        ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();
    $registrationNumber = 'SPMB-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $pdo->prepare('INSERT INTO pendaftar (user_id, no_pendaftaran) VALUES (?, ?)')->execute([$userId, $registrationNumber]);
    api_audit($pdo, $userId, 'auth.register_siswa');
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        api_json(false, 'Email sudah terdaftar.', [], 409);
    }
    error_log('Student registration failed: ' . $exception->getMessage());
    api_json(false, 'Registrasi gagal diproses.', [], 500);
}
api_json(true, 'Registrasi berhasil.', ['user' => ['id' => $userId, 'nama' => $name, 'email' => $email], 'no_pendaftaran' => $registrationNumber], 201);