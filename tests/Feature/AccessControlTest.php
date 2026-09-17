<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_access_all_module_pages(): void
    {
        $user = User::where('email', 'super@fuelcore.test')->first();
        $routes = [
            'stations.index',
            'users.index',
            'inventory.index',
            'products.index',
            'sales.index',
            'payments.index',
            'customers.index',
            'fleet.index',
            'deliveries.index',
            'shifts.index',
            'reconciliations.index',
            'expenses.index',
            'suppliers.index',
            'alerts.index',
            'audit-logs.index',
            'settings.index',
            'integrations.index',
            'reports.index',
            'live',
            'pos',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $status = $response->getStatusCode();
            if ($status >= 500) {
                $this->fail("Route {$name} returned HTTP {$status}.");
            }
            $this->assertSame(200, $status, "Route {$name} returned {$status}.");
        }
    }

    public function test_fuel_attendant_cannot_access_admin_modules(): void
    {
        $user = User::where('email', 'attendant@fuelcore.test')->first();

        $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('integrations.index'))->assertForbidden();
    }

    public function test_fuel_attendant_cannot_create_stations(): void
    {
        $user = User::where('email', 'attendant@fuelcore.test')->first();

        $this->actingAs($user)->get(route('stations.create'))->assertForbidden();
    }

    public function test_unknown_user_cannot_login(): void
    {
        $this->post(route('login.attempt'), [
            'email' => 'nobody@fuelcore.test',
            'password' => 'Whatever123',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }
}