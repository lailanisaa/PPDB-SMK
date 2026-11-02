<?php
declare(strict_types=1);

require_once __DIR__ . '/environment.php';

$host = appConfigValue('DB_HOST', '127.0.0.1');
$dbname = appConfigValue('DB_DATABASE', 'spmb_kasatrian');
$username = appConfigValue('DB_USERNAME', 'root');
$password = appConfigValue('DB_PASSWORD');
$environment = appConfigValue('APP_ENV', 'development');

if ($environment === 'production' && ($username === 'root' || $password === '')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    error_log('Production database configuration requires a non-root user and password.');
    echo json_encode(['success' => false, 'message' => 'Konfigurasi layanan tidak lengkap.']);
    exit;
}

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    error_log('Database connection failed: ' . $exception->getMessage());
    echo json_encode(['success' => false, 'message' => 'Layanan database tidak tersedia.']);
    exit;
}
