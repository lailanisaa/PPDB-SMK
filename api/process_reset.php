<?php
declare(strict_types=1);

require_once __DIR__ . '/_shared.php';

api_method('POST');
$data = api_input();
$token = trim((string) ($data['token'] ?? ''));
$newPassword = (string) ($data['password_baru'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/i', $token) || strlen($newPassword) < 6 || strlen($newPassword) > 72) {
    api_json(false, 'Token tidak valid atau password harus terdiri dari 6 hingga 72 byte.', [], 422);
}

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare(
        'SELECT id, email FROM password_resets
         WHERE token_hash = ? AND is_used = 0 AND expires_at > NOW()
         LIMIT 1 FOR UPDATE'
    );
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();

    if (!$reset) {
        $pdo->rollBack();
        api_json(false, 'Token reset tidak valid atau sudah kedaluwarsa.', [], 400);
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    if ($passwordHash === false) {
        throw new RuntimeException('Password hashing failed.');
    }

    $updateStudent = $pdo->prepare('UPDATE siswa SET password = ? WHERE email = ?');
    $updateStudent->execute([$passwordHash, $reset['email']]);
    if ($updateStudent->rowCount() !== 1) {
        $pdo->rollBack();
        api_json(false, 'Akun siswa tidak ditemukan.', [], 404);
    }

    $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE id = ?')->execute([$reset['id']]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Password reset processing failed: ' . $exception->getMessage());
    api_json(false, 'Password baru gagal disimpan.', [], 500);
}

api_json(true, 'Password berhasil diubah. Silakan login dengan password baru.');
