<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_resets')
            || !Schema::hasColumn('password_resets', 'token')
            || Schema::hasColumn('password_resets', 'token_hash')) {
            return;
        }

        Schema::table('password_resets', function (Blueprint $table) {
            $table->char('token_hash', 64)->nullable()->unique();
        });

        DB::table('password_resets')->select(['id', 'token'])->orderBy('id')->chunkById(500, function ($resets) {
            foreach ($resets as $reset) {
                DB::table('password_resets')->where('id', $reset->id)
                    ->update(['token_hash' => hash('sha256', $reset->token)]);
            }
        });

        DB::statement('ALTER TABLE password_resets MODIFY token_hash CHAR(64) NOT NULL');
        Schema::table('password_resets', function (Blueprint $table) {
            $table->dropUnique('password_resets_token_unique');
            $table->dropColumn('token');
        });
    }

    public function down(): void
    {
        return;
    }
};
