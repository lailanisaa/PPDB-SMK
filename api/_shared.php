<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function api_json(bool $success, string $message, array $data = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message, ...$data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function api_input(): array
{
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType === 'application/json') {
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

function api_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('SPMBSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();
}

function api_csrf_token(): string
{
    api_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function api_require_csrf(): void
{
    api_session();
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($provided) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $provided)) {
        api_json(false, 'Token CSRF tidak valid.', [], 419);
    }
}

function api_user(): array
{
    global $pdo;
    api_session();
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        api_json(false, 'Autentikasi diperlukan.', [], 401);
    }
    $stmt = $pdo->prepare('SELECT role, status_akun FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $account = $stmt->fetch();
    if (!$account || $account['status_akun'] !== 'aktif') {
        $_SESSION = [];
        session_destroy();
        api_json(false, 'Sesi tidak lagi memiliki akses.', [], 401);
    }
    $_SESSION['role'] = $account['role'];
    return ['id' => (int) $_SESSION['user_id'], 'role' => (string) $account['role']];
}

function api_require_roles(array $roles): array
{
    $user = api_user();
    if (!in_array($user['role'], $roles, true)) {
        api_json(false, 'Akses tidak diizinkan.', [], 403);
    }
    return $user;
}

function api_audit(PDO $pdo, ?int $userId, string $action): void
{
    $statement = $pdo->prepare('INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)');
    $statement->execute([
        $userId,
        mb_substr($action, 0, 190),
        mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ]);
}

function api_method(string ...$allowed): void
{
    if (!in_array(strtoupper($_SERVER['REQUEST_METHOD'] ?? ''), $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        api_json(false, 'Metode HTTP tidak diizinkan.', [], 405);
    }
}

function api_login_limit(PDO $pdo, string $email): void
{
    $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $emailHash = hash('sha256', mb_strtolower(trim($email)));
    $stmt = $pdo->prepare('SELECT attempts, window_started FROM login_attempts WHERE ip_hash = ? AND email_hash = ? LIMIT 1');
    $stmt->execute([$ipHash, $emailHash]);
    $row = $stmt->fetch();
    if ($row && strtotime((string) $row['window_started']) > time() - 900 && (int) $row['attempts'] >= 5) {
        header('Retry-After: ' . max(1, 900 - (time() - strtotime((string) $row['window_started']))));
        api_json(false, 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.', [], 429);
    }
}

function api_login_failed(PDO $pdo, string $email): void
{
    $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $emailHash = hash('sha256', mb_strtolower(trim($email)));
    $pdo->prepare(
        'INSERT INTO login_attempts (ip_hash, email_hash, attempts, window_started) VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE attempts = IF(window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, attempts + 1),
         window_started = IF(window_started < DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), window_started)'
    )->execute([$ipHash, $emailHash]);
}

function api_login_succeeded(PDO $pdo, string $email): void
{
    $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $emailHash = hash('sha256', mb_strtolower(trim($email)));
    $pdo->prepare('DELETE FROM login_attempts WHERE ip_hash = ? AND email_hash = ?')->execute([$ipHash, $emailHash]);
}