<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('formulir_siswa') && !Schema::hasColumn('formulir_siswa', 'catatan_verifikasi')) {
            Schema::table('formulir_siswa', function (Blueprint $table) {
                $table->text('catatan_verifikasi')->nullable();
            });
        }

        if (!Schema::hasTable('pengaturan_spmb')) {
            Schema::create('pengaturan_spmb', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->boolean('gelombang_1')->default(false);
                $table->boolean('gelombang_2')->default(false);
                $table->date('tgl_buka')->nullable();
                $table->date('tgl_tutup')->nullable();
                $table->timestamps();
            });
        }
        if (Schema::hasTable('pengaturan_spmb')) {
            DB::table('pengaturan_spmb')->insertOrIgnore([
                'id' => 1,
                'gelombang_1' => false,
                'gelombang_2' => false,
                'tgl_buka' => null,
                'tgl_tutup' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('action', 190);
                $table->string('ip_address', 45)->default('');
                $table->string('user_agent', 500)->default('');
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('admin_id')->references('id')->on('admin')->nullOnDelete();
                $table->index(['admin_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('pengaturan_spmb');
        if (Schema::hasTable('formulir_siswa') && Schema::hasColumn('formulir_siswa', 'catatan_verifikasi')) {
            Schema::table('formulir_siswa', function (Blueprint $table) {
                $table->dropColumn('catatan_verifikasi');
            });
        }
    }
};