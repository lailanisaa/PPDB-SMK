<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Email Reset Password

Endpoint `POST /api/request_reset.php` menerima `identifier` dan `role` (`siswa` atau `admin`), membuat token reset acak yang berlaku 1 jam, lalu mengirim tautan ke email akun tersebut. Role admin hanya dapat meminta reset untuk akun admin aktif. Endpoint selalu memberikan respons generik untuk menghindari enumerasi email. Endpoint `POST /api/process_reset.php` menerima `token` dan `password_baru`, memperbarui password untuk jenis akun yang tersimpan pada token, lalu mencabut token API lama milik akun tersebut. Tabel `password_resets` dibuat melalui migrasi Laravel; migrasi berikutnya mengubah token lama yang tersimpan mentah menjadi hash dan membedakan token siswa/admin.

Untuk mengirim email sungguhan melalui Gmail, isi variabel `MAIL_USERNAME` dengan alamat Gmail pengirim dan `MAIL_PASSWORD` dengan App Password Gmail (bukan password login Gmail). Aktifkan verifikasi 2 langkah di akun Google, lalu buat App Password. Pengirim email menggunakan alamat `MAIL_USERNAME`. Atur `APP_URL` ke alamat publik aplikasi (tanpa akhiran `/src`) agar tautan email membuka halaman reset di situs yang benar. Pengaturan `MAIL_HOST`, `MAIL_PORT`, dan `MAIL_ENCRYPTION` dipakai oleh kedua jalur API; nilai Gmail umumnya `smtp.gmail.com`, `587`, dan `tls`.

Dependensi PHPMailer disertakan melalui Composer. Setelah mengubah `.env`, jalankan `php artisan config:clear` dari folder `laravel`, lalu jalankan `php artisan migrate` jika tabel baru belum dibuat. Jika pengiriman gagal, periksa `laravel/storage/logs/laravel.log` dan pastikan kredensial SMTP benar. Jangan commit atau membagikan App Password.
