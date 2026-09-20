<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/send_reset_email.php';

$data = requestData();
$identifier = trim((string) ($data['identifier'] ?? ''));
if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Masukkan email yang terdaftar.', [], 422);
}

$statement = $pdo->prepare(
    "SELECT 'siswa' AS account_type, s.id AS account_id
     FROM siswa s
    LEFT JOIN formulir_siswa f ON f.siswa_id = s.id
    WHERE LOWER(s.email) = LOWER(?)
     UNION ALL
     SELECT 'admin' AS account_type, id AS account_id
     FROM admin
    WHERE LOWER(email) = LOWER(?)
     LIMIT 1"
);
$statement->execute([$identifier, $identifier]);
$account = $statement->fetch();

if (!$account) {
    respond(false, 'Akun dengan email atau nomor tersebut tidak ditemukan.', [], 404);
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE account_type = ? AND account_id = ? AND used_at IS NULL')->execute([$account['account_type'], $account['account_id']]);
$insert = $pdo->prepare('INSERT INTO password_reset_tokens (account_type, account_id, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
$insert->execute([$account['account_type'], $account['account_id'], $tokenHash]);

if (!sendResetEmail($identifier, $token)) {
    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = ?')->execute([$tokenHash]);
    respond(false, 'Email gagal dikirim. Periksa konfigurasi SMTP server.', [], 503);
}

respond(true, 'Link reset password telah dikirim ke email Anda. Link berlaku selama 15 menit.');
