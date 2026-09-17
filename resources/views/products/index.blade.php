@extends('layouts.app')

@section('active', 'products')
@section('page_title', 'Fuel Products')
@section('page_breadcrumb', 'FUELCORE / Products')

@section('content')
  <div class="page-head">
    <div><h1>Fuel Products</h1><p>Manage fuel types, pricing and inventory thresholds.</p></div>
    <div class="page-actions">
                    @can('product.manage')
        <a href="{{ route('products.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add Product</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('products.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search name or code…" style="flex:1;min-width:200px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-family:inherit;font-size:0.75rem;">
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('products.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Code</th><th>Product</th><th class="numeric">Price</th><th class="numeric">Cost</th><th class="numeric">Tax</th><th>Nozzles</th><th>Tanks</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($products as $product)
              <tr>
                <td class="mono">{{ $product->code }}</td>
                <td class="bold">{{ $product->name }}<div class="muted small">{{ $product->description }}</div></td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float)$product->price, 0) }}</td>
                <td class="numeric">{{ $product->cost_price ? currency().' '.number_format((float)$product->cost_price, 0) : '—' }}</td>
                <td class="numeric">{{ $product->tax_rate ? number_format((float)$product->tax_rate, 1).'%' : '—' }}</td>
                <td class="numeric">{{ $product->nozzles_count }}</td>
                <td class="numeric">{{ $product->tanks_count }}</td>
                <td><span class="status-pill-sm {{ $product->active ? 'online' : 'warning' }}">{{ $product->active ? 'Active' : 'Inactive' }}</span></td>
                <td class="center">
                  <div class="action-links">
      @can('product.manage')
                      <a href="{{ route('products.edit', $product) }}" title="Edit"><i data-lucide="pencil"></i></a>
                      @include('partials.toggle-status', [
                        'action' => route('products.toggleStatus', $product),
                        'active' => $product->active,
                        'label' => $product->name,
                        'noun' => 'Product',
                      ])
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No fuel products defined.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $products->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection