# Backend API Modular (Alternatif Terpisah)

Dokumen ini hanya untuk API PHP modular di folder root `api/`, dengan skema `schema.sql`. Implementasi ini terpisah dari aplikasi Laravel di folder `laravel/` dan tidak digunakan oleh frontend aktif di `laravel/public/src`, yang memanggil API Laravel melalui `laravel/routes/api.php`. Jangan mengikuti langkah instalasi ini untuk deployment Laravel kecuali frontend memang sudah dimigrasikan ke API modular.

API modular menyediakan endpoint pada `/api/auth/`, `/api/siswa/`, `/api/verifikator/`, `/api/keuangan/`, dan `/api/admin/`. API ini menggunakan sesi cookie dan token CSRF; mekanismenya berbeda dari API Laravel yang memakai Sanctum.

## Instalasi

1. Impor `schema.sql` ke MySQL 8+ atau MariaDB versi yang mendukung `CHECK` constraints. Skema ini menggunakan InnoDB dan UTF-8.
  Untuk menguji DDL terisolasi, jalankan `php scripts/validate_schema.php`; script memerlukan izin membuat/menghapus database sementara.
2. Atur environment PHP: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `UPLOAD_DIR`. Buat `UPLOAD_DIR` sebagai path absolut di luar document root dan pastikan user proses PHP dapat menulis ke sana. Jangan gunakan folder publik.
3. Gunakan akun database khusus aplikasi dengan hak minimum. Jangan gunakan akun `root` di produksi.
4. Buat super-admin satu kali dari CLI. Di PowerShell, set `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, dan `SUPER_ADMIN_PASSWORD` (minimal 16 karakter), lalu jalankan `php scripts/create_super_admin.php`; hapus variabel password setelah selesai.
5. Gunakan HTTPS. Cookie sesi mengaktifkan `HttpOnly`, `SameSite=Strict`, dan `Secure` otomatis saat HTTPS terdeteksi. Jangan pasang CORS wildcard; frontend dan API sebaiknya same-origin.
6. Lindungi direktori upload dengan konfigurasi web-server. `storage/.htaccess` menolak akses langsung di Apache; untuk Nginx/IIS, pasang aturan deny yang setara. Direktori produksi upload tetap wajib di luar document root.

## Autentikasi dan Integrasi Fetch

Login menetapkan cookie sesi. Simpan token CSRF dari respons di memori halaman (jangan di `localStorage`) dan kirim token itu pada setiap request mutasi. Browser harus menyertakan cookie sesi.

```js
let csrfToken = '';

async function api(path, { method = 'GET', body, headers = {} } = {}) {
  const response = await fetch(`/api/${path}`, {
    method,
    credentials: 'include',
    headers: {
      ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...(method !== 'GET' && csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
      ...headers,
    },
    body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
  });
  const data = await response.json();
  if (!response.ok) throw new Error(data.message || 'Request gagal');
  return data;
}

const login = await api('auth/login.php', {
  method: 'POST', body: { email, password },
});
csrfToken = login.csrf_token;
const profile = await api('auth/me.php');
```

Semua mutasi selain login/registrasi memerlukan `X-CSRF-Token`. Role yang tidak diizinkan mendapat `403`; sesi hilang/kedaluwarsa mendapat `401`. Jangan menerima `user_id`/`pendaftar_id` dari browser sebagai identitas pemilik.
Konten API berupa teks biasa; frontend harus menampilkan teks dengan `textContent`/binding framework, bukan memasukkannya melalui `innerHTML`.

## Endpoint

| Metode dan path | Role | Kegunaan |
|---|---|---|
| `POST /api/auth/login.php` | publik | Login, sesi cookie, token CSRF, batas 5 kegagalan per 15 menit per IP/email |
| `POST /api/auth/register_siswa.php` | publik | Registrasi siswa, password minimal 12 karakter |
| `GET /api/auth/me.php` | semua | Profil dan token CSRF |
| `POST /api/auth/logout.php` | semua | Logout dengan CSRF |
| `POST /api/siswa/simpan_formulir.php` | siswa | Simpan JSON section `pendaftar`, `orang_tua`, `sekolah_karir`, `jurusan_pilihan` dalam satu transaksi |
| `POST /api/siswa/upload_dokumen.php` | siswa | Multipart `jenis_dokumen` dan `file`; PDF/JPG/PNG/WEBP, maksimum 2 MB |
| `GET /api/siswa/get_pendaftar_detail.php` | semua role terdaftar | Siswa hanya dapat melihat profil sendiri; staf dapat memakai `?id=` |
| `GET /api/verifikator/antrian_berkas.php` | verifikator, ketua, super-admin | Antrian dokumen menunggu |
| `GET /api/verifikator/download_dokumen.php?id=` | verifikator, ketua, super-admin | Unduh dokumen privat setelah otorisasi |
| `POST /api/verifikator/proses_verifikasi.php` | verifikator, ketua, super-admin | JSON `dokumen_id`, `status_dokumen`, `catatan_revisi` |
| `POST /api/keuangan/konfirmasi_pembayaran.php` | staf admin, ketua, super-admin | JSON `transaksi_id`, `status_bayar` (`lunas`/`ditolak`) |
| `POST /api/keuangan/input_pembayaran.php` | staf admin, ketua, super-admin | Multipart `pendaftar_id`, `jenis_jalur`, `jumlah_bayar`, opsional `bukti_bayar` |
| `GET /api/keuangan/download_bukti.php?id=` | staf admin, ketua, super-admin | Unduh bukti pembayaran privat |
| `GET /api/keuangan/rekap_pembayaran.php` | staf admin, ketua, super-admin | Rekap, filter opsional `?status=menunggu|lunas|ditolak` |
| `GET /api/admin/dashboard_stats.php` | ketua, super-admin | Statistik pendaftar, jurusan, tren 30 hari |
| `GET /api/admin/list_siswa.php` | verifikator, staf, ketua, super-admin | Pencarian `q`, status, pagination `page` dan `per_page` |
| `GET /api/admin/list_admin.php` | super-admin | Daftar akun staf |
| `POST /api/admin/tambah_admin.php` | super-admin | Buat ketua/verifikator/staf; password minimal 12 karakter |
| `POST/PATCH /api/admin/ubah_admin.php` | super-admin | Ubah nama, role, status aktif, atau reset password akun staf |
| `POST` atau `DELETE /api/admin/hapus_admin.php` | super-admin | JSON `id`; tidak dapat menghapus akun sendiri atau super-admin |
| `POST/PATCH /api/admin/update_status_pendaftar.php` | ketua, super-admin | JSON `pendaftar_id`, `status_verifikasi`; penerimaan mengunci dan memperbarui kuota dalam transaksi |
| `GET/POST/PATCH/DELETE /api/admin/manage_jurusan.php` | baca: ketua/super-admin; tulis: super-admin | CRUD jurusan dan kuota |
| `GET/POST/PATCH/DELETE /api/admin/manage_pengumuman.php` | baca: user aktif; tulis: ketua/super-admin | Pengumuman target-role |
| `GET /api/admin/export_data.php` | ketua, super-admin | CSV Excel-compatible, maksimum 10.000 baris |

Input nominal pembayaran hanya dapat dilakukan staf berwenang; tarif resmi belum dimodelkan karena belum ada tabel tarif pada kebutuhan skema. Validasi nominal terhadap tarif sekolah perlu ditambahkan sebelum menerima input dari luar petugas.

## cURL

```sh
curl -i -c cookies.txt -H 'Content-Type: application/json' \
  -d '{"email":"siswa@example.test","password":"password-uji"}' \
  http://localhost/api/auth/login.php

curl -i -b cookies.txt http://localhost/api/auth/me.php
curl -i -b cookies.txt -H 'X-CSRF-Token: TOKEN_DARI_LOGIN' \
  -H 'Content-Type: application/json' -d '{"pendaftar":{"alamat":"Alamat baru"}}' \
  http://localhost/api/siswa/simpan_formulir.php
```

## Uji Keamanan

Impor `docs/spmb-api.postman_collection.json` ke Postman. Gunakan database uji, akun khusus per role, dan file dummy. Koleksi mencakup smoke login/profil, upaya bypass RBAC, BOLA/IDOR, payload SQLi pada pencarian, serta upload file executable. Tes negatif harus ditolak dengan `401/403/422`, bukan `5xx`; jangan jalankan pentest terhadap data produksi.

Contoh pemeriksaan cepat setelah login:

```sh
curl -i -b cookies.txt 'http://localhost/api/admin/list_siswa.php?q=%27%20OR%201=1--'
curl -i -b cookies.txt 'http://localhost/api/siswa/get_pendaftar_detail.php?id=ID_MILIK_USER_LAIN'
curl -i http://localhost/api/admin/dashboard_stats.php
```

Panggilan admin oleh sesi siswa harus `403`; detail pendaftar dari sesi siswa harus tetap milik akun sendiri; payload pencarian tidak boleh memperluas hasil; file `.php` harus ditolak. Audit rutin perlu mencakup patch PHP/MySQL, backup terenkripsi, retensi audit, rotasi kredensial, HTTPS, serta pemantauan log. Tidak ada implementasi yang dapat dijanjikan “90–100% aman”; keamanan produksi tetap bergantung pada konfigurasi host, review, dan uji independen.