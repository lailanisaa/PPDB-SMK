# 🏫 PPDB-SMK — Sistem Informasi PPDB SMK Kasatrian Solo

![Status](https://img.shields.io/badge/status-in%20development-yellow)
![License](https://img.shields.io/badge/license-MIT-green)
![Platform](https://img.shields.io/badge/platform-web-blue)

Modul Sistem Informasi **Penerimaan Peserta Didik Baru (PPDB)**
yang terintegrasi dengan website resmi SMK Kasatrian Solo
([smkkasatriansolo.sch.id](https://smkkasatriansolo.sch.id)).
Sistem ini memungkinkan calon siswa mendaftar secara online,
panitia memverifikasi berkas, dan kepala sekolah memantau
statistik pendaftaran — semuanya dalam satu platform terpadu.

> 🎓 Proyek ini dikembangkan dalam rangka **Kuliah Magang
> Mahasiswa (KMM)** Program Studi Informatika,

---

| Nama | NIM | Role |
|------|-----|------|
| Laila Khoirunnisa | l0122087 | Full Stack Developer / Project Lead |

---

## ✨ Fitur Utama

### 🎒 Portal Siswa
1. Registrasi & login akun calon siswa
2. Pengisian formulir pendaftaran multi-step
3. Pemilihan jurusan (utama & cadangan)
4. Upload berkas persyaratan online
5. Pemantauan status pendaftaran real-time
6. Download kartu pendaftaran (PDF)
7. Notifikasi status berkas & pengumuman

### 🏫 Portal Admin / Panitia
1. Login admin dengan verifikasi OTP
2. Konfigurasi periode & kuota PPDB
3. Verifikasi berkas pendaftar
4. Manajemen data pendaftar
5. Pembuatan & pengiriman pengumuman
6. Laporan & statistik pendaftaran
7. Export data ke Excel/PDF

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Frontend | HTML, CSS, JavaScript / Blade Template |
| Backend | PHP (Laravel) |
| Database | MySQL |
| Version Control | Git & GitHub |
| Desain UI | Figma |
| Deployment | (menyesuaikan server sekolah) |

## Menjalankan Lokal dan Deployment

Frontend aktif berada di `laravel/public/src` dan menggunakan API Laravel yang terdaftar di `laravel/routes/api.php`. API PHP modular di folder `api/` adalah implementasi terpisah; panduan dan skemanya ada di [`docs/backend-api.md`](docs/backend-api.md). Jangan mencampur kedua API atau mengimpor `schema.sql` untuk deployment Laravel.

### Lokal dengan Laragon
1. Start **MySQL** dan buat database kosong.
2. Buka terminal pada folder `laravel`, lalu jalankan `composer install` jika dependensi belum tersedia.
3. Salin `.env.example` menjadi `.env`, atur koneksi database dan `ADMIN_ACCESS_CODE`, kemudian jalankan `php artisan key:generate`.
4. Jalankan `php artisan migrate`.
5. Jalankan `php artisan serve --host=127.0.0.1 --port=8002`, lalu buka `http://localhost:8002`.

### Produksi
- Gunakan PHP 8.1+, Composer, dan MySQL yang sesuai dengan konfigurasi Laravel.
- Arahkan document root domain ke folder `laravel/public`, bukan ke root repository.
- Atur `.env` produksi, termasuk `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, kredensial database, dan `ADMIN_ACCESS_CODE`. Jangan unggah `.env` ke Git atau web root.
- Jalankan `composer install --no-dev --optimize-autoloader` dan `php artisan migrate --force`; pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP.
- Jangan jalankan `php artisan storage:link` untuk berkas pendaftaran. Upload baru disimpan privat dan hanya boleh diunduh melalui endpoint yang memeriksa otorisasi.
- Jika sebelumnya sudah ada upload di `storage/app/public/uploads`, pindahkan berkas tersebut ke `storage/app/uploads` dan pastikan salinan publik serta symlink `public/storage` tidak lagi menyajikannya sebelum membuka situs.

---

## 📁 Struktur Folder
