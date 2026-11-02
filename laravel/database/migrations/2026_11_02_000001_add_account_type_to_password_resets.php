<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_resets') || Schema::hasColumn('password_resets', 'account_type')) {
            return;
        }

        Schema::table('password_resets', function (Blueprint $table) {
            $table->enum('account_type', ['siswa', 'admin'])->default('siswa')->after('id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('password_resets') && Schema::hasColumn('password_resets', 'account_type')) {
            Schema::table('password_resets', function (Blueprint $table) {
                $table->dropColumn('account_type');
            });
        }
    }
};
