<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $expenses = Expense::query()
            ->with(['station', 'category', 'createdBy'])
            ->whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->when($request->filled('station_id'), fn ($q, $v) => $q->where('station_id', $v))
            ->when($request->filled('category_id'), fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->filled('approval_status'), fn ($q, $v) => $q->where('approval_status', $v))
            ->when($request->filled('from'), fn ($q, $v) => $q->where('expense_date', '>=', \Illuminate\Support\Carbon::parse($v)))
            ->when($request->filled('to'), fn ($q, $v) => $q->where('expense_date', '<=', \Illuminate\Support\Carbon::parse($v)))
            ->latest('expense_date')
            ->paginate($this->perPage())
            ->withQueryString();

        $total = Expense::whereIn('station_id', $this->visibleStations($user)->pluck('id'))
            ->selectRaw('COALESCE(SUM(amount),0) as total, COUNT(*) as count')
            ->first();

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'stations' => $this->visibleStations($user)->orderBy('code')->get(),
            'total' => $total,
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'stations' => $this->visibleStations(request()->user())->orderBy('code')->get(),
            'categories' => ExpenseCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $this->authorize('access-station', Station::findOrFail($data['station_id']));

        $category = $data['category_id'] ?? null;
        if (empty($category) && ! empty($data['new_category'])) {
            $category = ExpenseCategory::firstOrCreate(['name' => $data['new_category']])->id;
        }

        $expense = Expense::create([
            ...$data,
            'category_id' => $category,
            'created_by' => $request->user()->id,
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'expenses',
            'record_id' => $expense->id,
            'description' => "Expense recorded: " . number_format((float) $expense->amount, 0) . ' ' . currency() . " — {$expense->description}",
            'new_values' => $expense->only(['amount', 'description', 'expense_date']),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate(['approval_status' => ['required', Rule::in(['approved', 'rejected'])]]);

        $expense->update([
            'approval_status' => $data['approval_status'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        AuditLog::record([
            'action' => $data['approval_status'],
            'module' => 'expenses',
            'record_id' => $expense->id,
            'description' => "Expense {$data['approval_status']}: " . number_format((float) $expense->amount, 0) . ' ' . currency(),
            'new_values' => ['approval_status' => $data['approval_status']],
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense ' . $data['approval_status'] . '.');
    }

    protected function rules(): array
    {
        return [
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'new_category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'mobile_money', 'card', 'bank_transfer', 'credit'])],
            'receipt_ref' => ['nullable', 'string', 'max:100'],
        ];
    }
}