<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('account_type', ['siswa', 'admin'])->default('siswa');
            $table->string('email', 254);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->boolean('is_used')->default(false);
            $table->index(['email', 'is_used'], 'idx_password_resets_email_used');
            $table->index('expires_at', 'idx_password_resets_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
