<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_password_reset_allows_login_with_new_password(): void
    {
        $email = 'password-reset-' . Str::uuid() . '@example.test';
        $oldPassword = 'OldPassword123';
        $newPassword = 'NewPassword456';
        $studentId = DB::table('siswa')->insertGetId([
            'nama' => 'Password Reset Test',
            'sekolah_asal' => 'Test School',
            'jurusan' => 'Test',
            'email' => $email,
            'password' => password_hash($oldPassword, PASSWORD_BCRYPT),
        ]);
        $token = bin2hex(random_bytes(32));

        DB::table('password_resets')->insert([
            'account_type' => 'siswa',
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour(),
            'is_used' => 0,
            'created_at' => now(),
        ]);

        $this->postJson('/api/process_reset.php', [
            'token' => $token,
            'password_baru' => $newPassword,
        ])->assertOk()->assertJson(['success' => true]);

        $this->postJson('/api/login_siswa.php', [
            'email' => $email,
            'password' => $newPassword,
        ])->assertOk()->assertJson(['success' => true]);

        $this->postJson('/api/login_siswa.php', [
            'email' => $email,
            'password' => $oldPassword,
        ])->assertUnauthorized()->assertJson(['success' => false]);

        $this->assertDatabaseHas('siswa', ['id' => $studentId]);
    }

    public function test_admin_password_reset_allows_admin_login_with_new_password(): void
    {
        $email = 'password-reset-admin-' . Str::uuid() . '@example.test';
        $oldPassword = 'OldAdminPassword123';
        $newPassword = 'NewAdminPassword456';
        $accessCode = 'password-reset-test-access-code';
        $adminId = DB::table('admin')->insertGetId([
            'nama' => 'Password Reset Admin Test',
            'jabatan' => 'Administrator',
            'email' => $email,
            'password' => password_hash($oldPassword, PASSWORD_BCRYPT),
            'status' => 'Aktif',
        ]);
        $token = bin2hex(random_bytes(32));

        DB::table('password_resets')->insert([
            'account_type' => 'admin',
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHour(),
            'is_used' => 0,
            'created_at' => now(),
        ]);
        config(['spmb.admin_access_code' => $accessCode]);

        $this->postJson('/api/process_reset.php', [
            'token' => $token,
            'password_baru' => $newPassword,
        ])->assertOk()->assertJson(['success' => true, 'role' => 'admin']);

        $this->postJson('/api/login_admin.php', [
            'email' => $email,
            'password' => $newPassword,
            'access_code' => $accessCode,
        ])->assertOk()->assertJson(['success' => true]);

        $this->postJson('/api/login_admin.php', [
            'email' => $email,
            'password' => $oldPassword,
            'access_code' => $accessCode,
        ])->assertUnauthorized()->assertJson(['success' => false]);

        $this->assertDatabaseHas('admin', ['id' => $adminId]);
    }
}
