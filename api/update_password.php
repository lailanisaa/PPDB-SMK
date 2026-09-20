<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$siswaId = (int) ($data['siswa_id'] ?? 0);
$oldPassword = (string) ($data['old_password'] ?? '');
$newPassword = (string) ($data['new_password'] ?? '');

if ($siswaId < 1 || $oldPassword === '' || strlen($newPassword) < 6) {
    respond(false, 'Data password tidak valid. Password baru minimal 6 karakter.', [], 422);
}
$statement = $pdo->prepare('SELECT password FROM siswa WHERE id = ? LIMIT 1');
$statement->execute([$siswaId]);
$account = $statement->fetch();
if (!$account || !password_verify($oldPassword, $account['password'])) {
    respond(false, 'Password lama salah.', [], 401);
}
$update = $pdo->prepare('UPDATE siswa SET password = ? WHERE id = ?');
$update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $siswaId]);
respond(true, 'Password berhasil diperbarui.');
