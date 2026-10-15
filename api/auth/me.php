<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
$activeUser = api_user();
$stmt = $pdo->prepare('SELECT id, nama, email, role, status_akun FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$activeUser['id']]);
$user = $stmt->fetch();
if (!$user || $user['status_akun'] !== 'aktif') {
    $_SESSION = [];
    session_destroy();
    api_json(false, 'Akun tidak aktif.', [], 401);
}
$profile = null;
if ($user['role'] === 'siswa') {
    $stmt = $pdo->prepare('SELECT id, no_pendaftaran, status_verifikasi, status_pendaftaran, status_pembayaran FROM pendaftar WHERE user_id = ?');
    $stmt->execute([$activeUser['id']]);
    $profile = $stmt->fetch() ?: null;
}
api_json(true, 'Profil berhasil diambil.', ['user' => $user, 'pendaftar' => $profile, 'csrf_token' => api_csrf_token()]);