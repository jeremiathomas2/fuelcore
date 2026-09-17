<?php

namespace Tests\Feature;

use App\Models\FuelDelivery;
use App\Models\FuelProduct;
use App\Models\FuelTransaction;
use App\Models\Nozzle;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Models\Station;
use App\Models\User;
use App\Services\FuelSaleService;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CriticalPathTest extends TestCase
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

    public function test_pos_sale_records_transaction_and_increments_nozzle_meter(): void
    {
        $attendant = $this->user('attendant@fuelcore.test');
        $nozzle = Nozzle::where('station_id', $attendant->station_id)->with('fuelProduct')->firstOrFail();
        $before = (float) $nozzle->meter_current;

        $response = $this->actingAs($attendant)->post(route('pos.complete'), [
            'station_id' => $nozzle->station_id,
            'fuel_product_id' => $nozzle->fuel_product_id,
            'nozzle_id' => $nozzle->id,
            'fuel_type' => 'volume',
            'litres' => 5,
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();

        $txn = FuelTransaction::latest('id')->firstOrFail();
        $this->assertSame('completed', $txn->status);
        $this->assertEqualsWithDelta(5.0, (float) $txn->litres, 0.001);
        $this->assertEqualsWithDelta($before + 5, (float) $nozzle->fresh()->meter_current, 0.001);

        $this->assertDatabaseHas('payments', [
            'transaction_id' => $txn->id,
            'method' => 'cash',
            'status' => 'paid',
        ]);
    }

    public function test_void_reverses_nozzle_payment_and_status(): void
    {
        $attendant = $this->user('attendant@fuelcore.test');
        $super = $this->user('super@fuelcore.test');
        $nozzle = Nozzle::where('station_id', $attendant->station_id)->firstOrFail();
        $before = (float) $nozzle->meter_current;

        $txn = app(FuelSaleService::class)->create([
            'station_id' => $nozzle->station_id,
            'fuel_product_id' => $nozzle->fuel_product_id,
            'nozzle_id' => $nozzle->id,
            'litres' => 12,
            'payment_method' => 'cash',
        ], $attendant);

        $this->assertEqualsWithDelta($before + 12, (float) $nozzle->fresh()->meter_current, 0.001);

        app(FuelSaleService::class)->void($txn, 'Test reversal', $super);

        $this->assertSame('voided', $txn->fresh()->status);
        $this->assertEqualsWithDelta($before, (float) $nozzle->fresh()->meter_current, 0.001);
        $this->assertSame('refunded', $txn->payment->fresh()->status);
    }

    public function test_reconciliation_is_deduplicated_for_same_period(): void
    {
        $super = $this->user('super@fuelcore.test');
        $station = Station::firstOrFail();
        $today = now()->toDateString();

        $service = app(ReconciliationService::class);
        $service->run($station, ['from' => $today, 'to' => $today], $super);
        $service->run($station, ['from' => $today, 'to' => $today], $super);

        $this->assertSame(1, Reconciliation::where('station_id', $station->id)
            ->where('period_start', $today)
            ->count());

        $recon = Reconciliation::where('station_id', $station->id)->firstOrFail();
        $this->assertSame('reconciled', $recon->status);
    }

    public function test_api_ingest_requires_authentication(): void
    {
        $nozzle = Nozzle::firstOrFail();

        $this->postJson('/api/integration/fuel-transactions', [
            'transaction_uuid' => (string) Str::uuid(),
            'station_code' => $nozzle->station->code,
            'fuel_product_id' => $nozzle->fuel_product_id,
            'litres' => 3,
            'amount' => 9600,
        ])->assertUnauthorized();
    }

    public function test_api_token_can_be_issued_and_ingest_is_idempotent(): void
    {
        $super = $this->user('super@fuelcore.test');
        $nozzle = Nozzle::firstOrFail();
        $uuid = (string) Str::uuid();

        $this->postJson('/api/token', [
            'email' => $super->email,
            'password' => 'DemoPass123',
            'device_name' => 'pump-controller',
        ])->assertOk()->assertJsonStructure(['token']);

        $payload = [
            'transaction_uuid' => $uuid,
            'station_code' => $nozzle->station->code,
            'fuel_product_id' => $nozzle->fuel_product_id,
            'nozzle_id' => $nozzle->id,
            'litres' => 7,
            'amount' => 22400,
        ];

        $this->actingAs($super, 'sanctum')
            ->postJson('/api/integration/fuel-transactions', $payload)
            ->assertOk()
            ->assertJsonFragment(['status' => 'processed']);

        $this->assertSame(1, FuelTransaction::where('uuid', $uuid)->count());

        $this->actingAs($super, 'sanctum')
            ->postJson('/api/integration/fuel-transactions', $payload)
            ->assertOk()
            ->assertJsonFragment(['status' => 'duplicate']);

        $this->assertSame(1, FuelTransaction::where('uuid', $uuid)->count());
    }

    public function test_attendant_cannot_manage_products_or_cancel_deliveries(): void
    {
        $attendant = $this->user('attendant@fuelcore.test');
        $product = FuelProduct::firstOrFail();
        $delivery = FuelDelivery::firstOrFail();

        $this->actingAs($attendant)
            ->patch(route('products.toggleStatus', $product))
            ->assertForbidden();

        $this->actingAs($attendant)
            ->post(route('deliveries.cancel', $delivery))
            ->assertForbidden();
    }

    public function test_super_admin_can_toggle_product_status(): void
    {
        $super = $this->user('super@fuelcore.test');
        $product = FuelProduct::firstOrFail();
        $original = (bool) $product->active;

        $this->actingAs($super)
            ->patch(route('products.toggleStatus', $product))
            ->assertRedirect();

        $this->assertSame(! $original, (bool) $product->fresh()->active);
    }

    public function test_fleet_account_can_be_created_and_updated_with_vehicles(): void
    {
        $super = $this->user('super@fuelcore.test');
        $customer = \App\Models\Customer::where('type', 'fleet')->firstOrFail();

        $this->actingAs($super)->post(route('fleet.store'), [
            'customer_id' => $customer->id,
            'company_name' => 'Test Haulage Ltd',
            'credit_limit' => 1000000,
            'vehicles' => [
                ['registration_number' => 'TST-001', 'driver_name' => 'Driver One'],
            ],
        ])->assertRedirect(route('fleet.index'));

        $fleet = \App\Models\FleetAccount::where('company_name', 'Test Haulage Ltd')->firstOrFail();
        $this->assertDatabaseHas('fleet_vehicles', [
            'fleet_account_id' => $fleet->id,
            'registration_number' => 'TST-001',
        ]);

        $existing = $fleet->vehicles()->firstOrFail();

        $this->actingAs($super)->put(route('fleet.update', $fleet), [
            'customer_id' => $customer->id,
            'company_name' => 'Test Haulage Ltd',
            'vehicles' => [
                ['id' => $existing->id, 'registration_number' => $existing->registration_number, 'driver_name' => 'Renamed Driver'],
                ['registration_number' => 'TST-002', 'driver_name' => 'Driver Two'],
            ],
        ])->assertRedirect(route('fleet.show', $fleet));

        $this->assertSame('Renamed Driver', $existing->fresh()->driver_name);
        $this->assertDatabaseHas('fleet_vehicles', [
            'fleet_account_id' => $fleet->id,
            'registration_number' => 'TST-002',
        ]);
    }
}
