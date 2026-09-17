<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StationSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_switching_station_persists_and_the_dashboard_honours_it(): void
    {
        $super = $this->user('super@fuelcore.test');
        $station = Station::orderBy('id')->firstOrFail();

        $this->actingAs($super)
            ->post(route('stations.switch'), [
                'station_id' => $station->id,
                'redirect' => route('dashboard'),
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($station->id, session('active_station'));

        $this->actingAs($super)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('selectedStation', fn ($selected) => $selected?->id === $station->id);
    }

    public function test_switching_to_all_stations_clears_the_active_station(): void
    {
        $super = $this->user('super@fuelcore.test');
        $station = Station::orderBy('id')->firstOrFail();

        $this->actingAs($super)->withSession(['active_station' => $station->id]);

        $this->actingAs($super)
            ->post(route('stations.switch'), ['redirect' => route('dashboard')])
            ->assertRedirect(route('dashboard'));

        $this->assertNull(session('active_station'));
    }

    public function test_a_user_cannot_switch_to_a_station_they_cannot_see(): void
    {
        $attendant = $this->user('attendant@fuelcore.test');
        $visibleIds = Station::query()->visibleTo($attendant)->pluck('id');
        $hidden = Station::whereNotIn('id', $visibleIds)->first();

        if (! $hidden) {
            $this->markTestSkipped('Every station is visible to the attendant.');
        }

        $this->actingAs($attendant)
            ->from(route('dashboard'))
            ->post(route('stations.switch'), ['station_id' => $hidden->id])
            ->assertForbidden();
    }

    public function test_dashboard_totals_are_scoped_to_the_selected_station(): void
    {
        $super = $this->user('super@fuelcore.test');
        $station = Station::orderBy('id')->firstOrFail();
        $service = app(DashboardService::class);

        $all = $service->get(['user' => $super]);
        $single = $service->get(['user' => $super, 'station' => $station]);

        $this->assertSame(Station::count(), $all['totalStations']);
        $this->assertSame(1, $single['totalStations']);
    }
}
