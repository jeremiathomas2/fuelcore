<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_sees_dashboard(): void
    {
        $user = User::where('email', 'super@fuelcore.test')->first();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('FUELCORE');
    }

    public function test_attendant_sees_dashboard(): void
    {
        $user = User::where('email', 'attendant@fuelcore.test')->first();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_dashboard_is_filterable_by_station(): void
    {
        $user = User::where('email', 'super@fuelcore.test')->first();
        $station = \App\Models\Station::first();

        $this->actingAs($user)
            ->get(route('dashboard', ['station' => $station->id]))
            ->assertOk();
    }
}