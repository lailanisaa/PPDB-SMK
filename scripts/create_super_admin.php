<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$name = trim((string) getenv('SUPER_ADMIN_NAME'));
$email = mb_strtolower(trim((string) getenv('SUPER_ADMIN_EMAIL')));
$password = (string) getenv('SUPER_ADMIN_PASSWORD');
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 16) {
    fwrite(STDERR, "Set SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL, and SUPER_ADMIN_PASSWORD (minimum 16 characters).\n");
    exit(2);
}
try {
    $stmt = $pdo->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'super_admin')");
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Super admin created. Remove SUPER_ADMIN_PASSWORD from the environment.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, $exception->getCode() === '23000' ? "Email already exists.\n" : "Could not create super admin. Check server logs.\n");
    error_log('Super admin bootstrap failed: ' . $exception->getMessage());
    exit(1);
}