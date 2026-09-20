<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$siswaId = (int) ($_GET['siswa_id'] ?? 0);
if ($siswaId < 1) {
    respond(false, 'ID siswa tidak valid.', [], 422);
}

$student = $pdo->prepare('SELECT id, nama, sekolah_asal AS sekolah, jurusan, email, status, tanggal_daftar FROM siswa WHERE id = ? LIMIT 1');
$student->execute([$siswaId]);
$siswa = $student->fetch();
if (!$siswa) {
    respond(false, 'Siswa tidak ditemukan.', [], 404);
}

$form = $pdo->prepare('SELECT f.*, s.sekolah_asal FROM formulir_siswa f INNER JOIN siswa s ON s.id = f.siswa_id WHERE f.siswa_id = ? LIMIT 1');
$form->execute([$siswaId]);
$documents = $pdo->prepare('SELECT document_id, nama_file, status_dokumen FROM dokumen_siswa WHERE siswa_id = ?');
$documents->execute([$siswaId]);
respond(true, 'Data siswa berhasil diambil.', ['siswa' => $siswa, 'formulir' => $form->fetch() ?: null, 'dokumen' => $documents->fetchAll()]);
