<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('GET');
api_require_roles(['super_admin']);
$stmt = $pdo->query("SELECT id, nama, email, role, status_akun, created_at FROM users WHERE role <> 'siswa' ORDER BY created_at DESC");
api_json(true, 'Akun staf berhasil diambil.', ['items' => $stmt->fetchAll()]);