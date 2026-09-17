<?php

namespace App\Http\Controllers;

use App\Models\FuelProduct;
use App\Models\Station;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Registry of available reports.
     *
     * @return array<string, array{title: string, description: string, method: string}>
     */
    public static function registry(): array
    {
        return [
            'sales' => ['title' => 'Sales Report', 'description' => 'Daily fuel sales volume & revenue', 'method' => 'salesByDate'],
            'sales-by-station' => ['title' => 'Sales by Station', 'description' => 'Revenue and litres per station', 'method' => 'salesByStation'],
            'sales-by-fuel' => ['title' => 'Sales by Fuel Type', 'description' => 'Performance per fuel product', 'method' => 'salesByFuel'],
            'sales-by-pump' => ['title' => 'Sales by Pump', 'description' => 'Forecourt output per pump', 'method' => 'salesByPump'],
            'sales-by-nozzle' => ['title' => 'Sales by Nozzle', 'description' => 'Dispensing detail per nozzle', 'method' => 'salesByNozzle'],
            'sales-by-attendant' => ['title' => 'Sales by Attendant', 'description' => 'Vendor performance analysis', 'method' => 'salesByAttendant'],
            'sales-by-payment' => ['title' => 'Sales by Payment Method', 'description' => 'Revenue split by instrument', 'method' => 'salesByPayment'],
            'inventory' => ['title' => 'Inventory Levels', 'description' => 'Current stock by station & fuel', 'method' => 'inventoryLevels'],
            'shifts' => ['title' => 'Cashier Shifts', 'description' => 'Shift performance and cash handling', 'method' => 'shifts'],
            'reconciliation' => ['title' => 'Tank Reconciliations', 'description' => 'Fuel variances per reconciliation', 'method' => 'reconciliations'],
            'expenses' => ['title' => 'Expenses', 'description' => 'Station operating expenses', 'method' => 'expenses'],
            'deliveries' => ['title' => 'Fuel Deliveries', 'description' => 'Supplier delivery performance', 'method' => 'deliveries'],
            'customers' => ['title' => 'Customer Report', 'description' => 'Customer activity and credit', 'method' => 'customers'],
            'station-performance' => ['title' => 'Station Performance', 'description' => 'Complete station P&L snapshot', 'method' => 'stationPerformance'],
        ];
    }

    public const COLUMN_LABELS = [
        'label' => 'Dimension',
        'litres' => 'Litres',
        'revenue' => 'Revenue',
        'count' => 'Transactions',
        'last_balance' => 'Closing Stock',
        'net_movement' => 'Net Movement',
        'opening' => 'Opening Cash',
        'actual' => 'Actual Cash',
        'variance' => 'Variance',
        'ordered' => 'Ordered (L)',
        'delivered' => 'Delivered (L)',
        'total' => 'Total',
        'transactions' => 'Transactions',
        'total_spent' => 'Total Spent',
    ];

    public function index(Request $request): View
    {
        return view('reports.index', [
            'registry' => static::registry(),
        ]);
    }

    public function show(Request $request, string $report): View
    {
        abort_unless(isset(static::registry()[$report]), 404);

        $service = app(ReportService::class);
        $method = static::registry()[$report]['method'];
        $data = $service->{$method}($request->only(['from', 'to', 'station_id', 'fuel_product_id', 'attendant_id', 'type']));

        return view('reports.show', [
            'report' => $report,
            'meta' => static::registry()[$report],
            'rows' => $data['rows'],
            'totals' => $data['totals'],
            'columns' => $this->columnsFor($data['rows']),
            'stations' => Station::query()->visibleTo($request->user())->orderBy('code')->get(),
            'products' => FuelProduct::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless(isset(static::registry()[$report]), 404);

        $service = app(ReportService::class);
        $method = static::registry()[$report]['method'];
        $data = $service->{$method}($request->query());

        $columns = $this->columnsFor($data['rows']);

        return response()->streamDownload(function () use ($data, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map(fn ($c) => static::COLUMN_LABELS[$c] ?? ucfirst($c), $columns));

            foreach ($data['rows'] as $row) {
                $line = [];
                foreach ($columns as $col) {
                    $value = data_get($row, $col);
                    $line[] = $value instanceof \BackedEnum ? $value->value : (string) $value;
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, "fuelcore-{$report}-" . now()->format('Ymd-His') . '.csv');
    }

    protected function columnsFor($rows): array
    {
        $first = $rows->first() ?? collect();
        $keys = array_keys((array) $first);

        return $keys;
    }
}