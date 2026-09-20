<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/email.php';

function sendResetEmail(string $email, string $token): bool
{
    $link = RESET_APP_URL . '/reset-password.html?token=' . rawurlencode($token);
    $subject = 'Reset Password SPMB SMK Kasatrian';
    $message = "<!doctype html><html><body style=\"font-family:Arial,sans-serif;color:#1B4332\">"
        . "<h2>Reset Password SPMB SMK Kasatrian</h2>"
        . "<p>Klik tombol berikut untuk membuat password baru. Tautan ini berlaku selama 15 menit.</p>"
        . "<p><a href=\"{$link}\" style=\"display:inline-block;background:#22C55E;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px\">Buat Password Baru</a></p>"
        . "<p>Jika Anda tidak meminta reset password, abaikan email ini.</p></body></html>";
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . RESET_EMAIL_NAME . ' <' . RESET_EMAIL_FROM . '>',
        'Reply-To: ' . RESET_EMAIL_FROM,
    ];

    return mail($email, $subject, $message, implode("\r\n", $headers));
}
