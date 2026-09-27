<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasTable('admin') && Schema::hasTable('jadwal_tes')
            && Schema::hasTable('formulir_siswa') && Schema::hasTable('dokumen_siswa')
            && Schema::hasTable('password_reset_tokens')) {
            if (!Schema::hasTable('pengumuman')) {
                Schema::create('pengumuman', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('judul', 200);
                    $table->string('kategori', 100);
                    $table->string('target', 100);
                    $table->text('isi');
                    $table->timestamps();
                });
            }

            return;
        }

        Schema::create('siswa', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 150);
            $table->string('sekolah_asal', 150);
            $table->string('jurusan', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('status', ['Menunggu', 'Diproses', 'Diterima', 'Ditolak'])->default('Menunggu');
            $table->timestamp('tanggal_daftar')->useCurrent();
        });

        Schema::create('admin', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 150);
            $table->string('jabatan', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->timestamp('tanggal_daftar')->useCurrent();
        });

        Schema::create('jadwal_tes', function (Blueprint $table) {
            $table->increments('id');
            $table->date('tanggal');
            $table->string('sesi', 50);
            $table->string('jam', 30);
            $table->string('jenis', 100);
            $table->unsignedInteger('kuota')->default(0);
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('formulir_siswa', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('siswa_id')->unique();
            $table->char('nik', 16);
            $table->string('tempat_lahir', 100)->default('');
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('no_wa', 25)->default('');
            $table->text('alamat');
            $table->string('nama_ayah', 150)->default('');
            $table->string('nama_ibu', 150)->default('');
            $table->string('nisn', 30)->default('');
            $table->year('tahun_lulus');
            $table->string('jurusan_utama', 100)->default('');
            $table->string('jurusan_cadangan', 100)->default('');
            $table->unsignedInteger('jadwal_id')->default(0);
            $table->enum('status_formulir', ['Draft', 'Terkirim', 'Diverifikasi', 'Revisi'])->default('Draft');
            $table->timestamps();
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
        });

        Schema::create('dokumen_siswa', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('siswa_id');
            $table->string('document_id', 50);
            $table->string('nama_file');
            $table->string('nama_file_server');
            $table->string('mime_type', 100);
            $table->unsignedInteger('ukuran');
            $table->enum('status_dokumen', ['Menunggu', 'Valid', 'Ditolak'])->default('Menunggu');
            $table->timestamps();
            $table->unique(['siswa_id', 'document_id']);
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('account_type', ['siswa', 'admin']);
            $table->unsignedInteger('account_id');
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['account_type', 'account_id'], 'idx_reset_account');
            $table->index('expires_at', 'idx_reset_expiry');
        });

        Schema::create('pengumuman', function (Blueprint $table) {
            $table->increments('id');
            $table->string('judul', 200);
            $table->string('kategori', 100);
            $table->string('target', 100);
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};