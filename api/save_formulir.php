<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$data = requestData();
$siswaId = (int) ($data['siswa_id'] ?? 0);
$form = is_array($data['form'] ?? null) ? $data['form'] : [];
$dataDiri = is_array($form['dataDiri'] ?? null) ? $form['dataDiri'] : [];
$dataOrtu = is_array($form['dataOrtu'] ?? null) ? $form['dataOrtu'] : [];
$asalSekolah = is_array($form['asalSekolah'] ?? null) ? $form['asalSekolah'] : [];

if ($siswaId < 1 || trim((string) ($dataDiri['nama'] ?? '')) === '' || strlen((string) ($dataDiri['nik'] ?? '')) !== 16 || trim((string) ($asalSekolah['sekolah'] ?? '')) === '') {
    respond(false, 'Data formulir belum lengkap.', [], 422);
}

try {
    $check = $pdo->prepare('SELECT id FROM siswa WHERE id = ? LIMIT 1');
    $check->execute([$siswaId]);
    if (!$check->fetchColumn()) {
        respond(false, 'Siswa tidak ditemukan.', [], 404);
    }

    $statement = $pdo->prepare(
        'INSERT INTO formulir_siswa (siswa_id, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, no_wa, alamat, nama_ayah, nama_ibu, nisn, tahun_lulus, jurusan_utama, jurusan_cadangan, jadwal_id, status_formulir)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Terkirim\')
            ON DUPLICATE KEY UPDATE nik = VALUES(nik), tempat_lahir = VALUES(tempat_lahir), tanggal_lahir = VALUES(tanggal_lahir), jenis_kelamin = VALUES(jenis_kelamin), no_wa = VALUES(no_wa), alamat = VALUES(alamat), nama_ayah = VALUES(nama_ayah), nama_ibu = VALUES(nama_ibu), nisn = VALUES(nisn), tahun_lulus = VALUES(tahun_lulus), jurusan_utama = VALUES(jurusan_utama), jurusan_cadangan = VALUES(jurusan_cadangan), jadwal_id = VALUES(jadwal_id), status_formulir = \'Terkirim\', updated_at = CURRENT_TIMESTAMP'
        );
    $statement->execute([
        $siswaId,
        trim((string) $dataDiri['nik']),
        trim((string) ($dataDiri['tempat'] ?? '')),
        ($dataDiri['tgl'] ?? '') ?: null,
        trim((string) ($dataDiri['jk'] ?? '')),
        trim((string) ($dataDiri['wa'] ?? '')),
        trim((string) ($dataDiri['alamat'] ?? '')),
        trim((string) ($dataOrtu['ayah'] ?? '')),
        trim((string) ($dataOrtu['ibu'] ?? '')),
        trim((string) ($asalSekolah['nisn'] ?? '')),
        trim((string) ($asalSekolah['tahunlulus'] ?? '')),
        trim((string) ($form['jurusanUtama'] ?? '')),
        trim((string) ($form['jurusanCadangan'] ?? '')),
        (int) ($form['jadwalId'] ?? 0)
    ]);
    $pdo->prepare("UPDATE siswa SET sekolah_asal = ?, jurusan = ?, status = 'Diproses' WHERE id = ?")
        ->execute([$asalSekolah['sekolah'], $form['jurusanUtama'], $siswaId]);
    respond(true, 'Formulir berhasil dikirim.');
} catch (PDOException $exception) {
    respond(false, 'Formulir gagal disimpan.', [], 500);
}
