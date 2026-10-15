<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
$input = api_input();
$email = mb_strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    api_json(false, 'Email atau password tidak valid.', [], 422);
}
api_login_limit($pdo, $email);
$stmt = $pdo->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = ? AND status_akun = \'aktif\' LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password_hash'])) {
    api_login_failed($pdo, $email);
    api_json(false, 'Email atau password salah.', [], 401);
}
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}
api_login_succeeded($pdo, $email);
api_session();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
api_audit($pdo, (int) $user['id'], 'auth.login');
unset($user['password_hash']);
api_json(true, 'Login berhasil.', ['user' => $user, 'csrf_token' => $_SESSION['csrf_token']]);