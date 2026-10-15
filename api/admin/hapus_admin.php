<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST','DELETE');
api_require_csrf();
$actor = api_require_roles(['super_admin']);
$input = api_input();
$targetId = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$targetId || $targetId === $actor['id']) {
    api_json(false, 'ID akun tidak valid atau akun sendiri tidak dapat dihapus.', [], 422);
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND role <> 'super_admin' FOR UPDATE");
    $stmt->execute([$targetId]);
    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        api_json(false, 'Akun staf tidak ditemukan atau tidak dapat dihapus.', [], 404);
    }
    api_audit($pdo, $actor['id'], 'admin.user.delete.' . $targetId);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role <> 'super_admin'")->execute([$targetId]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Staff account deletion failed: ' . $exception->getMessage());
    api_json(false, 'Akun staf gagal dihapus.', [], 500);
}
api_json(true, 'Akun staf berhasil dihapus.');