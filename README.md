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

## Menjalankan di Laragon

1. Start **MySQL** dari Laragon.
2. Pastikan database `spmb_kasatrian` sudah tersedia. Untuk instalasi baru, impor `database.sql` melalui HeidiSQL/phpMyAdmin.
3. Buka terminal pada folder `laravel`, lalu jalankan `composer install` jika folder `vendor` belum ada.
4. Salin `.env.example` menjadi `.env`, atur koneksi database dan `ADMIN_ACCESS_CODE`, kemudian jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate` dan `php artisan storage:link`.
6. Jalankan `php artisan serve --host=127.0.0.1 --port=8002`.
7. Buka `http://localhost:8002`.

Frontend HTML tetap berada di `laravel/public/src`; API Laravel mempertahankan URL lama di `/api/*.php`.

---

## 📁 Struktur Folder
