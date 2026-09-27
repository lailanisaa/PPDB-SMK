<?php

use App\Http\Controllers\LegacyApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/list_siswa.php', [LegacyApiController::class, 'listSiswa']);
Route::get('/list_jadwal.php', [LegacyApiController::class, 'listJadwal']);
Route::post('/login_siswa.php', [LegacyApiController::class, 'loginSiswa']);
Route::post('/login_admin.php', [LegacyApiController::class, 'loginAdmin']);
Route::post('/register_siswa.php', [LegacyApiController::class, 'registerSiswa']);
Route::post('/register_admin.php', [LegacyApiController::class, 'registerAdmin']);
Route::post('/request_reset.php', [LegacyApiController::class, 'requestReset']);
Route::post('/reset_password.php', [LegacyApiController::class, 'resetPassword']);
Route::post('/logout.php', [LegacyApiController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'account.role:siswa'])->group(function () {
	Route::get('/get_siswa.php', [LegacyApiController::class, 'getSiswa']);
	Route::post('/save_formulir.php', [LegacyApiController::class, 'saveFormulir']);
	Route::post('/update_password.php', [LegacyApiController::class, 'updatePassword']);
	Route::post('/upload_dokumen.php', [LegacyApiController::class, 'uploadDokumen']);
});

Route::middleware(['auth:sanctum', 'account.role:admin'])->group(function () {
	Route::get('/list_dokumen.php', [LegacyApiController::class, 'listDokumen']);
	Route::get('/admin_stats.php', [LegacyApiController::class, 'adminStats']);
	Route::post('/update_status_siswa.php', [LegacyApiController::class, 'updateStatusSiswa']);
	Route::post('/simpan_pengumuman.php', [LegacyApiController::class, 'simpanPengumuman']);
});
