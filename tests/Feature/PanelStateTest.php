<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::where('email', 'super@fuelcore.test')->firstOrFail();
    }

    public function test_sidebar_is_a_turbo_permanent_element(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="sidebar" data-turbo-permanent', false);
    }

    public function test_cookie_restores_collapsed_sidebar_and_closed_panel(): void
    {
        $this->actingAs($this->superAdmin())
            ->withCookie('fc_sidebar', 'collapsed')
            ->withCookie('fc_right_panel', '0')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="sidebar collapsed"', false)
            ->assertSee('right-panel closed', false);
    }

    public function test_cookie_expands_sidebar_and_opens_panel(): void
    {
        $this->actingAs($this->superAdmin())
            ->withCookie('fc_sidebar', 'expanded')
            ->withCookie('fc_right_panel', '1')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="sidebar"', false)
            ->assertDontSee('right-panel closed', false);
    }

    public function test_active_section_is_exposed_for_client_side_navigation(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('data-active-section="audit"', false)
            ->assertSee('data-nav-key="audit"', false);

        $this->actingAs($admin)
            ->get(route('nozzles.index'))
            ->assertOk()
            ->assertSee('data-active-section="pumps"', false);
    }
}
