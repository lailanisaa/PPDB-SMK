<?php
declare(strict_types=1);

require_once __DIR__ . '/_shared.php';
require_once __DIR__ . '/send_reset_email.php';

api_method('POST');
$data = api_input();
$identifier = trim((string) ($data['identifier'] ?? ''));
if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    api_json(false, 'Masukkan email yang terdaftar.', [], 422);
}

try {
    $statement = $pdo->prepare('SELECT email FROM siswa WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $statement->execute([$identifier]);
    $account = $statement->fetch();
    $genericMessage = 'Jika email tersebut terdaftar, tautan reset akan dikirim ke alamat email itu.';
    if (!$account) {
        api_json(true, $genericMessage);
    }

    $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0')
        ->execute([$account['email']]);
    $token = bin2hex(random_bytes(32));
    $insert = $pdo->prepare(
        'INSERT INTO password_resets (email, token_hash, expires_at, is_used)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0)'
    );
    $insert->execute([$account['email'], hash('sha256', $token)]);
    $resetId = (int) $pdo->lastInsertId();

    try {
        sendResetEmail($account['email'], $token);
    } catch (Throwable $exception) {
        $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE id = ?')->execute([$resetId]);
        error_log('Password reset email delivery failed: ' . $exception->getMessage());
        $message = str_contains($exception->getMessage(), 'Could not authenticate')
            ? 'Login SMTP Gmail gagal. Pastikan MAIL_USERNAME adalah hello.floraisa@gmail.com dan MAIL_PASSWORD adalah App Password Gmail, bukan password login Gmail.'
            : 'Email reset gagal dikirim. Periksa konfigurasi SMTP Gmail.';
        api_json(false, $message, [], 503);
    }
} catch (Throwable $exception) {
    error_log('Password reset request failed: ' . $exception->getMessage());
    api_json(false, 'Permintaan reset password gagal diproses.', [], 500);
}

api_json(true, $genericMessage);
