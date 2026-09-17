@extends('layouts.app')

@section('active', 'products')
@section('page_title', 'Edit Product')
@section('page_breadcrumb', 'FUELCORE / Products / Edit')

@section('content')
  <div class="page-head">
    <div><h1>Edit {{ $product->name }}</h1></div>
    <div class="page-actions"><a href="{{ route('products.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('products.update', $product) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Product Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Code *</label>
          <input class="form-control" type="text" name="code" value="{{ old('code', $product->code) }}" required>
          @error('code')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name', $product->name) }}" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Unit</label>
          <select class="form-control" name="unit">
            @foreach (['liter' => 'Litre', 'gallon' => 'Gallon'] as $val => $label)
              <option value="{{ $val }}" {{ old('unit', $product->unit) === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Selling Price ({{ currency() }}) *</label>
          <input class="form-control" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
          @error('price')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Cost Price ({{ currency() }})</label>
          <input class="form-control" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Tax Rate (%)</label>
          <input class="form-control" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Min Stock (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="form-group">
          <div class="form-check"><input type="checkbox" id="active" name="active" value="1" {{ old('active', $product->active) ? 'checked' : '' }}><label for="active">Active (available for sale)</label></div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Product</button>
      <a href="{{ route('products.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection