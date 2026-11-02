<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/email.php';

function sendResetEmail(string $email, string $token, string $accountType = 'siswa'): void
{
    $autoload = __DIR__ . '/../laravel/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('PHPMailer is not installed. Run composer install in the laravel directory.');
    }
    require_once $autoload;

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        throw new RuntimeException('PHPMailer is not installed. Run composer install in the laravel directory.');
    }

    $username = appConfigValue('MAIL_USERNAME', appConfigValue('SMTP_USERNAME'));
    $password = appConfigValue('MAIL_PASSWORD', appConfigValue('SMTP_PASSWORD'));
    if ($username === '' || $password === '') {
        throw new RuntimeException('MAIL_USERNAME and MAIL_PASSWORD must be configured.');
    }

    $link = rtrim(RESET_APP_URL, '/') . '/src/reset-password.html?token=' . rawurlencode($token)
        . '&role=' . rawurlencode($accountType);
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = RESET_SMTP_HOST;
    $mailer->SMTPAuth = true;
    $mailer->Username = $username;
    $mailer->Password = $password;
    if (RESET_SMTP_ENCRYPTION === 'ssl') {
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    } elseif (RESET_SMTP_ENCRYPTION === 'tls') {
        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        throw new RuntimeException('MAIL_ENCRYPTION must be configured as tls or ssl.');
    }
    $mailer->Port = RESET_SMTP_PORT;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($username, RESET_EMAIL_NAME);
    $mailer->addAddress($email);
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset Password SPMB SMK Kasatrian';
    $mailer->Body = '<!doctype html><html lang="id"><body style="font-family:Arial,sans-serif;color:#1B4332">'
        . '<h2>Reset Password SPMB SMK Kasatrian</h2>'
        . '<p>Klik tombol berikut untuk membuat password baru. Tautan ini berlaku selama 1 jam.</p>'
        . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#22C55E;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px">Buat Password Baru</a></p>'
        . '<p>Jika Anda tidak meminta reset password, abaikan email ini.</p></body></html>';
    $mailer->AltBody = "Buat password baru melalui tautan ini (berlaku 1 jam): $link\nJika Anda tidak meminta reset password, abaikan email ini.";
    $mailer->send();
}
