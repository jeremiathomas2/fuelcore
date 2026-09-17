<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::query()
            ->withCount('deliveries')
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('contact_person', 'like', "%{$s}%")))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('suppliers.index', ['suppliers' => $suppliers]);
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $supplier = Supplier::create([
            ...$data,
            'code' => 'SUP-' . strtoupper(\Illuminate\Support\Str::random(4)),
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'suppliers',
            'record_id' => $supplier->id,
            'description' => "Supplier created: {$supplier->name}",
            'new_values' => $supplier->only(['code', 'name', 'status']),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate($this->rules($supplier->id));
        $supplier->update($data);

        AuditLog::record([
            'action' => 'update',
            'module' => 'suppliers',
            'record_id' => $supplier->id,
            'description' => "Supplier updated: {$supplier->name}",
            'new_values' => $supplier->only(['name', 'status']),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function toggleStatus(Request $request, Supplier $supplier): RedirectResponse
    {
        $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
        $oldStatus = $supplier->status;
        $supplier->update(['status' => $newStatus]);

        AuditLog::record([
            'action' => $newStatus === 'active' ? 'activate' : 'archive',
            'module' => 'suppliers',
            'record_id' => $supplier->id,
            'description' => "Supplier {$newStatus}: {$supplier->name}",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
        ]);

        return back()->with('success', "Supplier {$supplier->name} is now {$newStatus}.");
    }

    protected function rules(?int $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(Supplier::STATUSES)],
            'performance_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}