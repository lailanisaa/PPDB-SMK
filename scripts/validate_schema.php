<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$database = 'spmb_validation_' . bin2hex(random_bytes(6));
$adminPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
try {
    $adminPdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $schema = str_replace('spmb_kasatrian', $database, (string) file_get_contents(__DIR__ . '/../schema.sql'));
    foreach (explode(';', $schema) as $statement) {
        if (trim($statement) !== '') {
            $adminPdo->exec($statement);
        }
    }
    $tables = $adminPdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) !== 12) {
        throw new RuntimeException('Schema smoke test expected 12 tables, found ' . count($tables) . '.');
    }
    echo "Schema smoke test passed: 12 tables created.\n";
} finally {
    $adminPdo->exec("DROP DATABASE IF EXISTS `$database`");
}