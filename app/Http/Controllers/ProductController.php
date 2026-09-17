<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FuelProduct;
use App\Models\FuelProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = FuelProduct::query()
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->boolean('active') || ! $request->boolean('show_all'), fn ($q) => $q->where('active', true))
            ->withCount(['nozzles', 'tanks'])
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('products.index', ['products' => $products]);
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $product = FuelProduct::create($data);

        FuelProductPrice::create([
            'fuel_product_id' => $product->id,
            'price' => $product->price,
            'cost_price' => $product->cost_price,
            'changed_by' => $request->user()->id,
            'effective_date' => now()->toDateString(),
        ]);

        AuditLog::record([
            'action' => 'create',
            'module' => 'fuel_products',
            'record_id' => $product->id,
            'description' => "Fuel product created: {$product->name} @ " . currency() . " {$product->price}",
            'new_values' => $product->only(['code', 'name', 'price']),
        ]);

        return redirect()->route('products.index')->with('success', 'Fuel product created.');
    }

    public function edit(FuelProduct $product): View
    {
        return view('products.edit', ['product' => $product]);
    }

    public function update(Request $request, FuelProduct $product): RedirectResponse
    {
        $old = $product->replicate();
        $data = $request->validate($this->rules($product->id));
        $product->update($data);

        if ((float) $old->price !== (float) $product->price) {
            FuelProductPrice::create([
                'fuel_product_id' => $product->id,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'changed_by' => $request->user()->id,
                'effective_date' => now()->toDateString(),
            ]);
        }

        AuditLog::record([
            'action' => 'update',
            'module' => 'fuel_products',
            'record_id' => $product->id,
            'description' => "Fuel product updated: {$product->name}",
            'old_values' => ['price' => $old->price],
            'new_values' => $product->only(['price', 'active']),
        ]);

        return redirect()->route('products.index')->with('success', 'Fuel product updated.');
    }

    public function toggleStatus(Request $request, FuelProduct $product): RedirectResponse
    {
        $product->update(['active' => ! $product->active]);

        AuditLog::record([
            'action' => $product->active ? 'activate' : 'archive',
            'module' => 'fuel_products',
            'record_id' => $product->id,
            'description' => "Fuel product " . ($product->active ? 'activated' : 'archived') . ": {$product->name}",
            'new_values' => ['active' => $product->active],
        ]);

        return back()->with('success', "Fuel product {$product->name} " . ($product->active ? 'activated' : 'archived') . '.');
    }

    protected function rules(?int $ignore = null): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('fuel_products', 'code')->ignore($ignore)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}