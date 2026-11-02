<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';

define('RESET_EMAIL_FROM', appConfigValue('MAIL_FROM_ADDRESS', 'noreply@smkkasatrian.sch.id'));
define('RESET_EMAIL_NAME', appConfigValue('MAIL_FROM_NAME', 'SPMB SMK Kasatrian'));
define('RESET_APP_URL', rtrim(appConfigValue('APP_URL', 'http://localhost/SPMB_SMK_KASATRIAN'), '/'));
define('RESET_SMTP_HOST', appConfigValue('MAIL_HOST', 'smtp.gmail.com'));
define('RESET_SMTP_PORT', (int) appConfigValue('MAIL_PORT', '587'));
define('RESET_SMTP_ENCRYPTION', strtolower(appConfigValue('MAIL_ENCRYPTION', 'tls')));
