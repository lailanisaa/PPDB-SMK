<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use Laravel\Sanctum\Sanctum;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_redirects_to_the_spmb_frontend(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/src/index.html');
    }

    public function test_admin_api_requires_authentication(): void
    {
        $this->getJson('/api/admin_stats.php')
            ->assertUnauthorized()
            ->assertJson(['success' => false]);
    }

    public function test_admin_panel_data_endpoints_require_authentication(): void
    {
        foreach (['/api/student_portal.php', '/api/admin/pendaftar', '/api/admin/stats', '/api/admin/pengumuman', '/api/admin/pengaturan-spmb'] as $endpoint) {
            $this->getJson($endpoint)->assertUnauthorized();
        }
        $this->patchJson('/api/admin/staf/77', [])->assertUnauthorized();
        $this->patchJson('/api/admin/pendaftar/77', [])->assertUnauthorized();
        $this->deleteJson('/api/admin/pendaftar/77')->assertUnauthorized();
    }

    public function test_staff_admin_cannot_change_spmb_settings(): void
    {
        $staff = new Admin(['id' => 77, 'jabatan' => 'Staf Administrasi', 'status' => 'Aktif']);
        Sanctum::actingAs($staff);

        $this->putJson('/api/admin/pengaturan-spmb', [
            'gelombang_1' => true,
            'gelombang_2' => false,
            'tgl_buka' => null,
            'tgl_tutup' => null,
        ])->assertForbidden();
    }

    public function test_deactivated_staff_token_is_rejected(): void
    {
        $staff = new Admin(['id' => 78, 'jabatan' => 'Super Admin', 'status' => 'Nonaktif']);
        Sanctum::actingAs($staff);

        $this->getJson('/api/admin/staf')->assertUnauthorized();
    }
}
