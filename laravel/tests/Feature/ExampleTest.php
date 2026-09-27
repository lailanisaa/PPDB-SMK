<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
