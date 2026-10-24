<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'rt_rw', 'kelurahan', 'kecamatan', 'kota_kab', 'moda_transportasi', 'agama',
        'tinggi_badan', 'berat_badan', 'hp_ayah', 'kerja_ayah', 'hasil_ayah',
        'hp_ibu', 'kerja_ibu', 'hasil_ibu', 'rencana_karir',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('formulir_siswa')) return;

        $missing = array_values(array_filter($this->columns, fn (string $column) => !Schema::hasColumn('formulir_siswa', $column)));
        if (!$missing) return;

        Schema::table('formulir_siswa', function (Blueprint $table) use ($missing) {
            foreach (['rt_rw','kelurahan','kecamatan','kota_kab','moda_transportasi','agama','hp_ayah','kerja_ayah','hasil_ayah','hp_ibu','kerja_ibu','hasil_ibu','rencana_karir'] as $column) {
                if (in_array($column, $missing, true)) $table->string($column, 255)->nullable();
            }
            if (in_array('tinggi_badan', $missing, true)) $table->unsignedSmallInteger('tinggi_badan')->nullable();
            if (in_array('berat_badan', $missing, true)) $table->decimal('berat_badan', 5, 2)->unsigned()->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('formulir_siswa')) return;
        $existing = array_values(array_filter($this->columns, fn (string $column) => Schema::hasColumn('formulir_siswa', $column)));
        if ($existing) Schema::table('formulir_siswa', fn (Blueprint $table) => $table->dropColumn($existing));
    }
};