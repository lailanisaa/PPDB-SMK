<?php
declare(strict_types=1);
require_once __DIR__ . '/../_shared.php';
api_method('POST');
api_require_csrf();
$user = api_require_roles(['siswa']);
$input = api_input();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT id, status_verifikasi FROM pendaftar WHERE user_id = ? FOR UPDATE');
    $stmt->execute([$user['id']]);
    $applicant = $stmt->fetch();
    $applicantId = (int) ($applicant['id'] ?? 0);
    if ($applicantId < 1) {
        $pdo->rollBack();
        api_json(false, 'Profil pendaftar tidak ditemukan.', [], 404);
    }
    if ($applicant['status_verifikasi'] === 'Diterima' && !empty($input['jurusan_pilihan'])) {
        $pdo->rollBack();
        api_json(false, 'Pilihan jurusan tidak dapat diubah setelah pendaftar diterima.', [], 409);
    }

    $sections = [
        'pendaftar' => ['tempat_lahir','tgl_lahir','jk','nisn','agama','alamat','rt_rw','kelurahan','kecamatan','kota_kab','moda_transportasi','tinggi_badan','berat_badan'],
        'orang_tua' => ['nama_ayah','hp_ayah','kerja_ayah','hasil_ayah','nama_ibu','hp_ibu','kerja_ibu','hasil_ibu'],
        'sekolah_karir' => ['asal_sekolah','tahun_lulus','rencana_karir'],
        'jurusan_pilihan' => ['jurusan_utama_id','jurusan_cadangan_id'],
    ];
    foreach ($sections as $table => $columns) {
        $values = is_array($input[$table] ?? null) ? $input[$table] : [];
        $selected = array_values(array_intersect($columns, array_keys($values)));
        if (!$selected) {
            continue;
        }
        $clean = [];
        foreach ($selected as $column) {
            $value = $values[$column];
            if (is_array($value) || is_object($value)) {
                throw new InvalidArgumentException('Nilai formulir tidak valid.');
            }
            $clean[] = is_string($value) ? trim($value) : $value;
        }
        if ($table === 'pendaftar') {
            $sets = implode(', ', array_map(static fn(string $column): string => "`$column` = ?", $selected));
            $pdo->prepare("UPDATE pendaftar SET $sets, status_pendaftaran = 'Terkirim' WHERE id = ?")->execute([...$clean, $applicantId]);
            continue;
        }
        $allColumns = ['pendaftar_id', ...$selected];
        $quoted = implode(', ', array_map(static fn(string $column): string => "`$column`", $allColumns));
        $placeholders = implode(', ', array_fill(0, count($allColumns), '?'));
        $updates = implode(', ', array_map(static fn(string $column): string => "`$column` = VALUES(`$column`)", $selected));
        $pdo->prepare("INSERT INTO `$table` ($quoted) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates")
            ->execute([$applicantId, ...$clean]);
    }
    $pdo->prepare("UPDATE pendaftar SET status_pendaftaran = 'Terkirim' WHERE id = ?")->execute([$applicantId]);
    api_audit($pdo, $user['id'], 'siswa.formulir.simpan');
    $pdo->commit();
} catch (InvalidArgumentException $exception) {
    $pdo->rollBack();
    api_json(false, $exception->getMessage(), [], 422);
} catch (Throwable $exception) {
    $pdo->rollBack();
    error_log('Form submission failed: ' . $exception->getMessage());
    api_json(false, 'Formulir gagal disimpan.', [], 500);
}
api_json(true, 'Formulir berhasil disimpan.');