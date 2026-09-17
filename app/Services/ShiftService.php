<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\FuelTransaction;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Models\Shift;
use App\Models\ShiftReading;
use App\Models\Station;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Open a shift for a station / employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function open(array $data, ?User $user = null): Shift
    {
        return DB::transaction(function () use ($data, $user) {
            $station = Station::findOrFail($data['station_id']);

            // Ensure no other open shift for the same employee / station.
            $existing = Shift::where('status', 'open')
                ->where('station_id', $station->id)
                ->when($data['employee_id'] ?? null, fn ($q, $e) => $q->where('employee_id', $e))
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('An open shift already exists for this selection. Close it first.');
            }

            $shift = Shift::create([
                'station_id' => $station->id,
                'employee_id' => $data['employee_id'] ?? $user?->id,
                'pump_id' => $data['pump_id'] ?? null,
                'opening_cash' => (float) ($data['opening_cash'] ?? 0),
                'opening_meter' => $data['opening_meter'] ?? null,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            AuditLog::record([
                'action' => 'open',
                'module' => 'shifts',
                'record_id' => $shift->id,
                'description' => "Shift opened for {$station->name} by " . ($user?->name ?? 'system'),
                'new_values' => ['opening_cash' => $shift->opening_cash],
            ]);

            return $shift;
        });
    }

    /**
     * Close a shift: reconcile cash and meter readings, mark closed.
     *
     * @param  array<string, mixed>  $data
     */
    public function close(Shift $shift, array $data, ?User $user = null): Shift
    {
        return DB::transaction(function () use ($shift, $data, $user) {
            if ($shift->status !== 'open') {
                throw new \InvalidArgumentException('Only open shifts can be closed.');
            }

            $shift->actual_cash = (float) ($data['actual_cash'] ?? 0);
            $shift->closing_meter = $data['closing_meter'] ?? null;
            $shift->reconcileCashFromSales();
            $shift->status = 'closed';
            $shift->closed_at = now();
            $shift->save();

            // Record closing readings per pump/nozzle touched during the shift.
            $reading = $shift->transactions()
                ->whereNotNull('pump_id')
                ->select('pump_id', 'nozzle_id', DB::raw('SUM(litres) as litres'),
                    DB::raw('MIN(meter_start) as meter_start'), DB::raw('MAX(meter_end) as meter_end'))
                ->groupBy('pump_id', 'nozzle_id')
                ->get();

            foreach ($reading as $row) {
                ShiftReading::updateOrCreate(
                    ['shift_id' => $shift->id, 'pump_id' => $row->pump_id, 'nozzle_id' => $row->nozzle_id],
                    ['meter_start' => $row->meter_start, 'meter_end' => $row->meter_end, 'litres' => $row->litres],
                );
            }

            if ($shift->cash_variance !== null && abs($shift->cash_variance) > 1000) {
                AlertService::raise(
                    severity: 'warning',
                    title: 'Cash variance detected',
                    message: "Shift #{$shift->id} at {$shift->station->name} has a cash variance of " . number_format($shift->cash_variance, 2),
                    stationId: $shift->station_id,
                );
            }

            AuditLog::record([
                'action' => 'close',
                'module' => 'shifts',
                'record_id' => $shift->id,
                'description' => "Shift closed: expected " . number_format((float) $shift->expected_cash, 2) . ", actual " . number_format((float) $shift->actual_cash, 2),
                'new_values' => ['expected_cash' => $shift->expected_cash, 'actual_cash' => $shift->actual_cash, 'variance' => $shift->cash_variance],
            ]);

            return $shift->fresh();
        });
    }

    public function cancel(Shift $shift, string $reason, ?User $user = null): Shift
    {
        $shift->update(['status' => 'cancelled', 'notes' => $reason, 'closed_at' => now()]);

        AuditLog::record([
            'action' => 'cancel',
            'module' => 'shifts',
            'record_id' => $shift->id,
            'description' => "Shift cancelled: {$reason}",
        ]);

        return $shift;
    }
}