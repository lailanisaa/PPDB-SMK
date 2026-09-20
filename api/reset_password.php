<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$token = trim((string) ($data['token'] ?? ''));
$newPassword = (string) ($data['password'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/i', $token) || strlen($newPassword) < 6) {
    respond(false, 'Token tidak valid atau password minimal 6 karakter.', [], 422);
}

$tokenHash = hash('sha256', $token);
$statement = $pdo->prepare('SELECT id, account_type, account_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
$statement->execute([$tokenHash]);
$reset = $statement->fetch();
if (!$reset) {
    respond(false, 'Token reset tidak valid atau sudah kedaluwarsa.', [], 400);
}

$table = $reset['account_type'] === 'admin' ? 'admin' : 'siswa';
$update = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
$update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $reset['account_id']]);
$pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);
respond(true, 'Password berhasil diubah. Silakan login dengan password baru.');
