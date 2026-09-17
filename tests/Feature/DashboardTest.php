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

    public function test_dashboard_is_filterable_by_an_encrypted_station_token(): void
    {
        $user = User::where('email', 'super@fuelcore.test')->first();
        $station = \App\Models\Station::first();

        $token = url_id($station->id);

        $this->assertIsString($token);
        $this->assertNotSame((string) $station->id, $token);
        $this->assertSame($station->id, \App\Support\UrlId::decode($token));

        $this->actingAs($user)
            ->get(route('dashboard', ['station' => $token]))
            ->assertOk()
            ->assertViewHas('selectedStation', fn ($selected) => $selected?->id === $station->id);
    }

    public function test_dashboard_rejects_a_tampered_station_token(): void
    {
        $user = User::where('email', 'super@fuelcore.test')->first();

        $this->actingAs($user)
            ->get(route('dashboard', ['station' => 'not-a-real-token']))
            ->assertOk()
            ->assertViewHas('selectedStation', null);
    }
}