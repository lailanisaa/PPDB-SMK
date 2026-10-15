<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$user = api_require_roles(['staf_admin','ketua_panitia','super_admin']);
$kind = (string) ($_POST['jenis_jalur'] ?? '');
$applicantId = filter_var($_POST['pendaftar_id'] ?? null, FILTER_VALIDATE_INT);
$amount = (string) ($_POST['jumlah_bayar'] ?? '');
if (!$applicantId || !in_array($kind, ['kuota_khusus','reguler'], true) || !preg_match('/^\d{1,12}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
    api_json(false, 'Pendaftar, jalur, atau jumlah pembayaran tidak valid.', [], 422);
}
$file = $_FILES['bukti_bayar'] ?? null;
$relativePath = null;
$targetPath = null;
if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || (int) $file['size'] < 1 || (int) $file['size'] > 2 * 1024 * 1024) {
        api_json(false, 'Bukti pembayaran tidak valid atau melebihi 2 MB.', [], 422);
    }
    $allowed = ['pdf' => 'application/pdf','jpg' => 'image/jpeg','jpeg' => 'image/jpeg','png' => 'image/png','webp' => 'image/webp'];
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$extension]) || $mime !== $allowed[$extension]) {
        api_json(false, 'Format bukti harus PDF, JPG, PNG, atau WEBP.', [], 422);
    }
    $base = getenv('UPLOAD_DIR');
    if (!$base || (!str_starts_with($base, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $base))) {
        api_json(false, 'Penyimpanan privat belum dikonfigurasi.', [], 500);
    }
    $directory = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . $applicantId;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        api_json(false, 'Penyimpanan bukti tidak tersedia.', [], 500);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $directory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        api_json(false, 'Bukti pembayaran gagal disimpan.', [], 500);
    }
    $relativePath = 'payments/' . $applicantId . '/' . $filename;
}

try {
    $pdo->beginTransaction();
    $check = $pdo->prepare('SELECT id FROM pendaftar WHERE id = ? FOR UPDATE');
    $check->execute([$applicantId]);
    if (!$check->fetchColumn()) {
        $pdo->rollBack();
        if ($targetPath) @unlink($targetPath);
        api_json(false, 'Pendaftar tidak ditemukan.', [], 404);
    }
    $pdo->prepare('INSERT INTO transaksi_pembayaran (pendaftar_id, jenis_jalur, jumlah_bayar, bukti_bayar) VALUES (?, ?, ?, ?)')
        ->execute([$applicantId, $kind, $amount, $relativePath]);
    $paymentId = (int) $pdo->lastInsertId();
    $pdo->prepare("UPDATE pendaftar SET status_pembayaran = 'Menunggu' WHERE id = ? AND status_pembayaran <> 'Lunas'")->execute([$applicantId]);
    api_audit($pdo, $user['id'], 'keuangan.pembayaran.input');
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($targetPath) @unlink($targetPath);
    error_log('Payment input failed: ' . $exception->getMessage());
    api_json(false, 'Transaksi pembayaran gagal dicatat.', [], 500);
}
api_json(true, 'Transaksi pembayaran berhasil dicatat.', ['transaksi_id' => $paymentId], 201);