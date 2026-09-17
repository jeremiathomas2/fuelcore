@extends('layouts.app')

@section('active', 'customers')
@section('page_title', 'Add Customer')
@section('page_breadcrumb', 'FUELCORE / Customers / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Customer</h1></div>
    <div class="page-actions"><a href="{{ route('customers.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('customers.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Customer Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer Type *</label>
          <select class="form-control" name="type" required>
            @foreach (['individual', 'corporate', 'fleet', 'government', 'other'] as $t)
              <option value="{{ $t }}" {{ old('type', 'individual') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name') }}" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone') }}" placeholder="+255 7xx xxx xxx">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="{{ old('email') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input class="form-control" type="text" name="address" value="{{ old('address') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Tax ID (TIN)</label>
          <input class="form-control" type="text" name="tax_id" value="{{ old('tax_id') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Credit Limit ({{ currency() }})</label>
          <input class="form-control" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', 0) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['active', 'inactive', 'blocked'] as $s)
              <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Customer</button>
      <a href="{{ route('customers.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection