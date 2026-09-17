<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Station;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $shifts = Shift::query()
            ->with(['station', 'employee'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('opened_at')
            ->paginate($this->perPage())
            ->withQueryString();

        $openShifts = Shift::whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->where('status', 'open')->count();

        return view('shifts.index', [
            'shifts' => $shifts,
            'openShifts' => $openShifts,
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
        ]);
    }

    public function open(Request $request): View
    {
        $user = $request->user();

        $employees = User::where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['fuel_attendant', 'cashier', 'supervisor', 'station_manager']))
            ->orderBy('name')
            ->get();

        return view('shifts.open', [
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'pump_id' => ['nullable', 'integer', Rule::exists('pumps', 'id')->where('station_id', $request->input('station_id'))],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'opening_meter' => ['nullable', 'numeric', 'min:0'],
        ]);

        $station = Station::findOrFail($data['station_id']);
        $this->authorize('access-station', $station);

        if (! empty($data['employee_id'])) {
            $eligible = User::whereKey($data['employee_id'])
                ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['fuel_attendant', 'cashier', 'supervisor', 'station_manager']))
                ->exists();

            if (! $eligible) {
                throw ValidationException::withMessages(['employee_id' => 'Selected employee cannot be assigned a shift.']);
            }
        }

        try {
            $shift = app(ShiftService::class)->open($data, $request->user());

            return redirect()->route('shifts.index')
                ->with('success', 'Shift opened with opening cash ' . number_format((float) $shift->opening_cash, 0) . ' ' . currency() . '.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function close(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('access-station', $shift->station);

        $data = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'closing_meter' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(ShiftService::class)->close($shift, $data, $request->user());

            return redirect()->route('shifts.index')->with('success', 'Shift closed.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, Shift $shift): RedirectResponse
    {
        $this->authorize('access-station', $shift->station);

        if (! in_array($shift->status, ['open', 'closing'], true)) {
            return back()->withErrors(['error' => 'Only open shifts can be cancelled.']);
        }

        $data = $request->validate(['notes' => ['required', 'string', 'max:255']]);

        app(ShiftService::class)->cancel($shift, $data['notes'], $request->user());

        return redirect()->route('shifts.index')->with('success', 'Shift cancelled.');
    }
}