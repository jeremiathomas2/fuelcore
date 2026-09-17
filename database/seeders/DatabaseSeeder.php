<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FleetAccount;
use App\Models\FleetVehicle;
use App\Models\FuelDelivery;
use App\Models\FuelDeliveryItem;
use App\Models\FuelProduct;
use App\Models\FuelProductPrice;
use App\Models\FuelTransaction;
use App\Models\FuelTransactionItem;
use App\Models\InventoryMovement;
use App\Models\IntegrationTransaction;
use App\Models\Nozzle;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Pump;
use App\Models\Reconciliation;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Station;
use App\Models\StationDevice;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\Tank;
use App\Models\TankReading;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRBAC();
        $stations = $this->seedStations();
        $products = $this->seedProducts();
        $this->seedEquipment($stations, $products);
        $tanks = Tank::all();
        $users = $this->seedUsers($stations);
        $attendant = $users['attendant'];
        $super = $users['super'];
        $this->seedCustomersFleet();
        $suppliers = $this->seedSuppliers();
        $shifts = $this->seedShifts($stations, $attendant);
        $this->seedTransactions($stations, $products, $attendant, $shifts);
        $this->seedDeliveries($stations, $suppliers, $products, $attendant);
        $this->seedExpenses($stations, $attendant);
        $this->seedReconciliations($stations, $shifts, $super);
        $this->seedAlerts($stations);
        $this->seedSettings();
        $this->seedAuditLogs($users);
        $this->seedInventoryMovements($stations, $products, $tanks, $attendant);
        $this->seedDevices($stations);
        $this->seedIntegrationTransactions($stations);
    }

    private function seedRBAC(): void
    {
        $all = config('permissions.permissions');
        $defs = array_map(fn ($slug) => [
            'slug' => $slug,
            'name' => ucwords(str_replace('.', ' ', $slug)),
            'group' => explode('.', $slug)[0],
        ], $all);

        $permModels = [];
        foreach ($defs as $d) {
            $permModels[$d['slug']] = Permission::firstOrCreate(['slug' => $d['slug']], $d);
        }

        $roleDefs = config('permissions.roles');
        foreach ($roleDefs as $slug => $perms) {
            $role = Role::firstOrCreate(['slug' => $slug], [
                'name' => ucwords(str_replace('_', ' ', $slug)),
                'description' => "Default {$slug} role",
            ]);
            $role->syncPermissions($perms);
        }
    }

    private function seedStations(): \Illuminate\Support\Collection
    {
        $regions = ['Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Mbeya', 'Tanga', 'Zanzibar', 'Iringa', 'Kilimanjaro', 'Tabora'];
        $statuses = ['online', 'online', 'online', 'online', 'warning', 'maintenance', 'online', 'online', 'online', 'offline'];
        $stations = collect();

        foreach ($regions as $i => $region) {
            $code = 'FS' . str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $stations->push(Station::firstOrCreate(['code' => $code], [
                'name' => "FUELCORE {$region}",
                'region' => $region,
                'district' => "{$region} District",
                'location' => "{$region} City Center",
                'address' => ($i + 1) . " Main Street, {$region}",
                'latitude' => -6.7924 + ($i * 0.3),
                'longitude' => 39.2083 + ($i * 0.2),
                'manager_name' => ['John Mwakasege', 'Amina Hassan', 'Peter Kimaro', 'Grace Mushi', 'David Mwamba', 'Sarah Kilonzo', 'Hassan Omar', 'Mary Mwangoka', 'James Lema', 'Fatuma Nyerere'][$i],
                'phone' => '+25571' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '', $region)) . '@fuelcore.test',
                'opening_date' => Carbon::create(2023, 1, 15)->addMonths($i),
                'status' => $statuses[$i],
            ]));
        }

        return $stations;
    }

    private function seedProducts(): \Illuminate\Support\Collection
    {
        $defs = [
            ['code' => 'PD95', 'name' => 'Petrol (RON 95)', 'price' => 3200, 'cost_price' => 2800, 'tax_rate' => 8, 'min_stock' => 2000],
            ['code' => 'PU98', 'name' => 'Petrol (RON 98)', 'price' => 3500, 'cost_price' => 3050, 'tax_rate' => 8, 'min_stock' => 1000],
            ['code' => 'DSL', 'name' => 'Diesel', 'price' => 3100, 'cost_price' => 2700, 'tax_rate' => 8, 'min_stock' => 3000],
            ['code' => 'KRS', 'name' => 'Kerosene', 'price' => 2900, 'cost_price' => 2550, 'tax_rate' => 5, 'min_stock' => 500],
        ];

        $products = collect();
        foreach ($defs as $d) {
            $p = FuelProduct::firstOrCreate(['code' => $d['code']], $d);
            FuelProductPrice::firstOrCreate(
                ['fuel_product_id' => $p->id, 'effective_date' => now()->toDateString()],
                ['price' => $d['price'], 'cost_price' => $d['cost_price'], 'changed_by' => null]
            );
            $products->push($p);
        }

        return $products;
    }

    private function seedEquipment(\Illuminate\Support\Collection $stations, \Illuminate\Support\Collection $products): void
    {
        $petrol = $products->firstWhere('code', 'PD95');
        $diesel = $products->firstWhere('code', 'DSL');
        $kerosene = $products->firstWhere('code', 'KRS');

        $stationFuelMap = [
            0 => [$petrol, $diesel],
            1 => [$petrol, $diesel],
            2 => [$petrol, $diesel],
            3 => [$petrol, $diesel, $kerosene],
            4 => [$petrol, $diesel],
            5 => [$petrol, $diesel],
            6 => [$petrol, $diesel],
            7 => [$petrol, $diesel],
            8 => [$petrol, $diesel, $kerosene],
            9 => [$petrol, $diesel],
        ];

        $tankNum = 1;
        foreach ($stations as $si => $station) {
            for ($p = 1; $p <= 3; $p++) {
                $pump = Pump::create([
                    'station_id' => $station->id,
                    'pump_number' => "P{$p}",
                    'manufacturer' => ['Gilbarco', 'Wayne', 'Tokheim', 'Bennett'][rand(0, 3)],
                    'model' => 'FM-' . rand(4000, 9999),
                    'serial_number' => Str::random(10),
                    'status' => $station->status === 'offline' ? 'offline' : 'online',
                    'installation_date' => $station->opening_date,
                    'last_communication_at' => now()->subMinutes(rand(1, 60)),
                ]);

                $nozzleNum = 1;
                $stationFuel = $stationFuelMap[$si] ?? [$petrol, $diesel];
                foreach ($stationFuel as $nf) {
                    Nozzle::create([
                        'station_id' => $station->id,
                        'pump_id' => $pump->id,
                        'fuel_product_id' => $nf->id,
                        'nozzle_number' => (string) $nozzleNum,
                        'status' => 'idle',
                        'meter_start' => rand(100000, 999999),
                        'meter_current' => rand(100000, 999999),
                        'total_litres' => rand(10000, 500000),
                    ]);
                    $nozzleNum++;
                }
            }

            $stationFuel = $stationFuelMap[$si] ?? [$petrol, $diesel];
            foreach ($stationFuel as $tf) {
                $cap = $tf->code === 'KRS' ? 15000 : 25000;
                $vol = round($cap * (rand(30, 90) / 100), 0);
                Tank::create([
                    'station_id' => $station->id,
                    'fuel_product_id' => $tf->id,
                    'tank_number' => 'T' . str_pad($tankNum, 2, '0', STR_PAD_LEFT),
                    'capacity' => $cap,
                    'current_volume' => $vol,
                    'min_level' => round($cap * 0.10, 0),
                    'max_level' => $cap,
                    'temperature' => rand(240, 310) / 10,
                    'water_level' => rand(0, 50) / 100,
                    'status' => $vol < $cap * 0.15 ? 'low' : 'normal',
                    'last_reading_at' => now()->subMinutes(rand(10, 120)),
                ]);
                $tankNum++;
            }
        }
    }

    private function seedUsers(\Illuminate\Support\Collection $stations): array
    {
        $home = $stations[0];
        $defs = [
            'super'      => ['email' => 'super@fuelcore.test',      'role' => 'super_admin',        'station' => null],
            'admin'      => ['email' => 'admin@fuelcore.test',      'role' => 'head_office_admin',  'station' => null],
            'manager'    => ['email' => 'manager@fuelcore.test',    'role' => 'station_manager',    'station' => $home],
            'attendant'  => ['email' => 'attendant@fuelcore.test',  'role' => 'fuel_attendant',     'station' => $home],
            'cashier'    => ['email' => 'cashier@fuelcore.test',    'role' => 'cashier',            'station' => $home],
            'accountant' => ['email' => 'accountant@fuelcore.test', 'role' => 'accountant',         'station' => null],
            'inventory'  => ['email' => 'inventory@fuelcore.test',  'role' => 'inventory_officer',  'station' => null],
            'auditor'    => ['email' => 'auditor@fuelcore.test',    'role' => 'auditor',            'station' => null],
        ];

        $users = [];
        foreach ($defs as $key => $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => ucwords(str_replace(['@fuelcore.test'], '', $d['email'])) . ' User',
                    'password' => 'DemoPass123',
                    'employee_number' => 'EMP-' . strtoupper(Str::random(6)),
                    'status' => 'active',
                    'station_id' => $d['station']?->id,
                    'email_verified_at' => now(),
                ]
            );
            $role = Role::firstWhere('slug', $d['role']);
            if ($role && !$user->roles()->where('roles.id', $role->id)->exists()) {
                $user->roles()->attach($role);
            }
            if ($d['station']) {
                $user->stations()->syncWithoutDetaching([$d['station']->id => ['station_role' => $d['role'], 'is_assigned' => true]]);
            }
            $users[$key] = $user;
        }

        return $users;
    }

    private function seedCustomersFleet(): void
    {
        $individuals = [
            ['name' => 'Mohamed Ally',       'phone' => '+255712345001', 'type' => 'individual'],
            ['name' => 'Neema Kimaro',       'phone' => '+255712345002', 'type' => 'individual'],
            ['name' => 'Joseph Mwangi',      'phone' => '+255712345003', 'type' => 'individual'],
            ['name' => 'Amina Bakari',       'phone' => '+255712345004', 'type' => 'corporate'],
            ['name' => 'Wizara ya Ulinzi',   'phone' => '+255712345005', 'type' => 'government'],
        ];

        foreach ($individuals as $c) {
            Customer::firstOrCreate(
                ['customer_number' => Customer::generateCustomerNumber()],
                [
                    'name' => $c['name'], 'phone' => $c['phone'], 'type' => $c['type'],
                    'status' => 'active', 'credit_limit' => rand(0, 5) * 100000,
                ]
            );
        }

        $fleetCompanyNames = ['Transport Co. Ltd', 'Quick Delivery Services'];
        foreach ($fleetCompanyNames as $i => $company) {
            $cust = Customer::firstOrCreate(
                ['customer_number' => Customer::generateCustomerNumber()],
                ['name' => $company, 'type' => 'fleet', 'phone' => '+25571234501' . $i, 'status' => 'active', 'credit_limit' => 5000000]
            );
            $fleet = FleetAccount::firstOrCreate(
                ['account_number' => FleetAccount::generateAccountNumber()],
                [
                    'customer_id' => $cust->id, 'company_name' => $company,
                    'contact_person' => 'Manager ' . $company, 'phone' => $cust->phone,
                    'fuel_limit_litres' => 10000, 'credit_limit' => 5000000, 'monthly_statement' => true, 'status' => 'active',
                ]
            );
            $fleet->authorizedProducts()->sync(FuelProduct::where('active', true)->pluck('id'));

            for ($v = 1; $v <= 3; $v++) {
                $reg = strtoupper(Str::random(2) . '-' . Str::random(4));
                FleetVehicle::firstOrCreate(
                    ['registration_number' => $reg],
                    ['fleet_account_id' => $fleet->id, 'customer_id' => $cust->id, 'driver_name' => "Driver {$v}", 'status' => 'active']
                );
            }
        }
    }

    private function seedSuppliers(): \Illuminate\Support\Collection
    {
        $defs = [
            ['code' => 'SUP01', 'name' => 'Tanzania Petrolium Supply Co.', 'contact_person' => 'Abdul Rahman', 'phone' => '+255754000001'],
            ['code' => 'SUP02', 'name' => 'East Africa Fuel Distributors', 'contact_person' => 'Grace Mwangi', 'phone' => '+255754000002'],
            ['code' => 'SUP03', 'name' => 'Coast Oil Suppliers Ltd',       'contact_person' => 'Hassan Juma',   'phone' => '+255754000003'],
        ];

        $suppliers = collect();
        foreach ($defs as $d) {
            $suppliers->push(Supplier::firstOrCreate(['code' => $d['code']], array_merge($d, ['status' => 'active', 'performance_rating' => rand(3, 5)])));
        }
        return $suppliers;
    }

    private function seedShifts(\Illuminate\Support\Collection $stations, User $attendant): \Illuminate\Support\Collection
    {
        $shifts = collect();
        $statusPool = ['open', 'closed', 'closed', 'closed'];

        for ($i = 0; $i < 4; $i++) {
            $station = $stations[$i % $stations->count()];
            $opened = now()->subDays(rand(0, 5))->setTime(6 + ($i % 2) * 8, 0);
            $status = $statusPool[$i];

            $shifts->push(Shift::create([
                'station_id' => $station->id,
                'employee_id' => $attendant->id,
                'opening_cash' => rand(5, 30) * 10000,
                'status' => $status,
                'opened_at' => $opened,
                'closed_at' => $status === 'closed' ? $opened->copy()->addHours(8) : null,
                'expected_cash' => $status === 'closed' ? rand(200, 800) * 1000 : null,
                'actual_cash' => $status === 'closed' ? rand(190, 810) * 1000 : null,
                'notes' => "Shift {$i}",
            ]));
        }

        return $shifts;
    }

    private function seedTransactions(\Illuminate\Support\Collection $stations, \Illuminate\Support\Collection $products, User $attendant, \Illuminate\Support\Collection $shifts): void
    {
        $paymentMethods = Payment::METHODS;
        $station = $stations[0];
        $petrol = $products->firstWhere('code', 'PD95');
        $diesel = $products->firstWhere('code', 'DSL');
        $pump = Pump::where('station_id', $station->id)->first();
        $nozzles = Nozzle::where('pump_id', $pump->id)->get();
        $shift = $shifts->first();

        for ($i = 0; $i < 20; $i++) {
            $product = [$petrol, $diesel][rand(0, 1)];
            $nozzle = $nozzles->firstWhere('fuel_product_id', $product->id) ?? $nozzles->first();
            $litres = round(rand(500, 8000) / 100, 3) * 100;
            $price = (float) $product->price;
            $amount = round($litres * $price, 2);
            $tax = round($amount * ((float) $product->tax_rate / 100), 2);
            $netAmount = $amount - $tax;
            $paymentStatus = ['paid', 'paid', 'paid', 'paid', 'pending'][rand(0, 4)];
            $txnStatus = $paymentStatus === 'pending' ? 'pending' : 'completed';
            $transacted = now()->subDays(rand(0, 7))->subHours(rand(0, 12));

            $txn = FuelTransaction::create([
                'station_id' => $station->id,
                'pump_id' => $pump->id,
                'nozzle_id' => $nozzle?->id,
                'fuel_product_id' => $product->id,
                'attendant_id' => $attendant->id,
                'shift_id' => $shift?->id,
                'meter_start' => rand(100000, 999999),
                'meter_end' => rand(100000, 999999),
                'litres' => $litres,
                'price' => $price,
                'amount' => $amount,
                'tax' => $tax,
                'net_amount' => round($netAmount, 2),
                'payment_status' => $paymentStatus,
                'status' => $txnStatus,
                'transacted_at' => $transacted,
                'created_by' => $attendant->id,
            ]);

            FuelTransactionItem::create([
                'fuel_transaction_id' => $txn->id,
                'fuel_product_id' => $product->id,
                'quantity' => $litres,
                'price' => $price,
                'amount' => $amount,
            ]);

            $method = $paymentMethods[rand(0, count($paymentMethods) - 1)];
            Payment::create([
                'payment_number' => 'PAY-' . strtoupper(Str::random(8)),
                'transaction_id' => $txn->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $method !== 'cash' ? strtoupper(Str::random(10)) : null,
                'status' => $paymentStatus,
                'paid_at' => $transacted,
                'created_by' => $attendant->id,
            ]);
        }
    }

    private function seedDeliveries(\Illuminate\Support\Collection $stations, \Illuminate\Support\Collection $suppliers, \Illuminate\Support\Collection $products, User $attendant): void
    {
        $station = $stations[0];
        $supplier = $suppliers->first();
        $product = $products->firstWhere('code', 'PD95');
        $tank = Tank::where('station_id', $station->id)->where('fuel_product_id', $product->id)->first();

        $deliveries = [
            ['status' => 'completed', 'ordered' => 15000, 'delivered' => 15000],
            ['status' => 'in_transit', 'ordered' => 20000, 'delivered' => null],
            ['status' => 'scheduled', 'ordered' => 10000, 'delivered' => null],
        ];

        foreach ($deliveries as $d) {
            $deliveredDate = $d['status'] === 'completed' ? now()->subDays(2) : now()->addDays(rand(1, 3));
            $delivery = FuelDelivery::create([
                'delivery_number' => 'DEL-' . strtoupper(Str::random(8)),
                'station_id' => $station->id,
                'supplier_id' => $supplier->id,
                'driver_name' => 'Driver ' . Str::random(5),
                'vehicle_number' => strtoupper(Str::random(2) . '-' . Str::random(4)),
                'ordered_qty' => $d['ordered'],
                'delivered_qty' => $d['delivered'],
                'delivery_date' => $deliveredDate,
                'status' => $d['status'],
                'completed_by' => $d['status'] === 'completed' ? $attendant->id : null,
                'completed_at' => $d['status'] === 'completed' ? $deliveredDate : null,
            ]);

            FuelDeliveryItem::create([
                'fuel_delivery_id' => $delivery->id,
                'fuel_product_id' => $product->id,
                'tank_id' => $tank?->id,
                'ordered_qty' => $d['ordered'],
                'delivered_qty' => $d['delivered'],
                'unit_price' => $product->cost_price,
                'total_cost' => $d['delivered'] ? round($d['delivered'] * (float) $product->cost_price, 2) : null,
            ]);
        }
    }

    private function seedExpenses(\Illuminate\Support\Collection $stations, User $attendant): void
    {
        $categories = ExpenseCategory::DEFAULTS;
        foreach ($categories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat], ['description' => "{$cat} related expenses"]);
        }
        $cats = ExpenseCategory::all();

        for ($i = 0; $i < 15; $i++) {
            $station = $stations[array_rand($stations->toArray())];
            $cat = $cats->random();
            Expense::create([
                'station_id' => $station->id,
                'category_id' => $cat->id,
                'amount' => rand(10, 500) * 1000,
                'description' => "{$cat->name} expense #" . ($i + 1),
                'expense_date' => now()->subDays(rand(0, 30)),
                'payment_method' => Expense::PAYMENT_METHODS[array_rand(Expense::PAYMENT_METHODS)],
                'created_by' => $attendant->id,
                'approval_status' => ['pending', 'approved', 'approved', 'rejected'][rand(0, 3)],
                'approved_by' => rand(0, 1) ? $attendant->id : null,
                'approved_at' => rand(0, 1) ? now() : null,
            ]);
        }
    }

    private function seedReconciliations(\Illuminate\Support\Collection $stations, \Illuminate\Support\Collection $shifts, User $super): void
    {
        $station = $stations[0];
        $shift = $shifts->first();

        Reconciliation::create([
            'station_id' => $station->id,
            'shift_id' => $shift?->id,
            'opening_stock' => 12000,
            'deliveries_qty' => 5000,
            'adjustments_qty' => 0,
            'sales_qty' => 3500,
            'expected_closing' => 13500,
            'actual_reading' => 13480,
            'variance_litres' => -20,
            'variance_pct' => -0.148,
            'variance_value' => round(-20 * 3200, 2),
            'status' => 'reconciled',
            'reconciled_at' => now()->subDay(),
            'reconciled_by' => $super->id,
        ]);
    }

    private function seedAlerts(\Illuminate\Support\Collection $stations): void
    {
        $defs = [
            ['station' => 0, 'severity' => 'critical', 'title' => 'Low fuel level',      'message' => 'Tank T01 Petrol D below 15%.'],
            ['station' => 1, 'severity' => 'warning',  'title' => 'Pump offline',         'message' => 'Pump P2 not communicating since 06:00.'],
            ['station' => 0, 'severity' => 'info',     'title' => 'Delivery scheduled',   'message' => 'Delivery DEL-001 scheduled for tomorrow.'],
        ];

        foreach ($defs as $d) {
            Alert::create([
                'station_id' => $stations[$d['station']]->id,
                'severity' => $d['severity'],
                'title' => $d['title'],
                'message' => $d['message'],
            ]);
        }
    }

    private function seedSettings(): void
    {
        $defs = [
            ['key' => 'regions', 'value' => json_encode(['Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Mbeya', 'Tanga', 'Zanzibar', 'Iringa', 'Kilimanjaro', 'Tabora', 'Morogoro', 'Kigoma', 'Pwani', 'Manyara', 'Rukwa', 'Singida', 'Ruvuma', 'Kagera', 'Mtwara', 'Lindi', 'Simiyu', 'Geita', 'Njombe', 'Katavi', 'Songwe', 'Shinyanga', 'Mara']), 'group' => 'options'],
            ['key' => 'company_name',              'value' => 'FUELCORE Tanzania Ltd',   'group' => 'general'],
            ['key' => 'currency',                  'value' => 'TSh',                      'group' => 'general'],
            ['key' => 'country',                   'value' => 'Tanzania',                 'group' => 'general'],
            ['key' => 'tax_rate',                  'value' => '8',                        'group' => 'general'],
            ['key' => 'low_stock_threshold',       'value' => '2000',                     'group' => 'thresholds'],
            ['key' => 'variance_warning_threshold','value' => '1',                        'group' => 'thresholds'],
            ['key' => 'alert_email',               'value' => 'admin@fuelcore.test',      'group' => 'alerts'],
        ];

        foreach ($defs as $d) {
            SystemSetting::firstOrCreate(['key' => $d['key']], $d);
        }
    }

    private function seedAuditLogs(array $users): void
    {
        $actions = ['create', 'update', 'login', 'logout', 'alert'];
        $modules = ['users', 'stations', 'auth', 'settings', 'alerts'];
        for ($i = 0; $i < 15; $i++) {
            AuditLog::create([
                'user_id' => $users[array_rand($users)]->id,
                'action' => $actions[array_rand($actions)],
                'module' => $modules[array_rand($modules)],
                'description' => 'Audit entry #' . ($i + 1) . ' – system generated',
                'old_values' => null,
                'new_values' => ['seeded' => true],
                'ip_address' => '127.0.0.1',
            ]);
        }
    }

    private function seedInventoryMovements(\Illuminate\Support\Collection $stations, \Illuminate\Support\Collection $products, \Illuminate\Support\Collection $tanks, User $attendant): void
    {
        $station = $stations[0];
        $product = $products->firstWhere('code', 'PD95');
        $tank = $tanks->firstWhere('station_id', $station->id)->first();

        InventoryMovement::create([
            'station_id' => $station->id,
            'fuel_product_id' => $product->id,
            'tank_id' => $tank?->id,
            'type' => 'delivery',
            'quantity' => 15000,
            'note' => 'Initial stock delivery',
            'created_by' => $attendant->id,
            'created_at' => now()->subDays(5),
        ]);

        InventoryMovement::create([
            'station_id' => $station->id,
            'fuel_product_id' => $product->id,
            'tank_id' => $tank?->id,
            'type' => 'sale',
            'quantity' => -3500,
            'note' => 'Sales from pump dispensing',
            'created_by' => $attendant->id,
            'created_at' => now()->subDays(3),
        ]);

        InventoryMovement::create([
            'station_id' => $station->id,
            'fuel_product_id' => $product->id,
            'tank_id' => $tank?->id,
            'type' => 'adjustment',
            'quantity' => -120,
            'note' => 'Loss adjustment - meter variance',
            'created_by' => $attendant->id,
            'created_at' => now()->subDay(),
        ]);
    }

    private function seedDevices(\Illuminate\Support\Collection $stations): void
    {
        $station = $stations[0];
        StationDevice::create([
            'station_id' => $station->id,
            'device_code' => 'FCC-' . strtoupper(Str::random(6)),
            'name' => 'FCC Unit Main',
            'device_type' => 'fcc',
            'status' => 'online',
            'last_heartbeat_at' => now()->subMinutes(2),
        ]);
    }

    private function seedIntegrationTransactions(\Illuminate\Support\Collection $stations): void
    {
        $station = $stations[0];
        IntegrationTransaction::create([
            'station_code' => $station->code,
            'transaction_uuid' => (string) Str::uuid(),
            'payload' => ['station_code' => $station->code, 'litres' => 20, 'amount' => 64000],
            'status' => 'processed',
            'received_at' => now()->subDay(),
            'processed_at' => now()->subDay(),
        ]);
    }
}
