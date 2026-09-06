<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_reachable_for_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Export Readiness');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create(['name' => 'Rina Kusuma']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Welcome back, Rina')
            ->assertSee('Recent Activity');
    }
}
