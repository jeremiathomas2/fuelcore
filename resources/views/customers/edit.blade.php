@extends('layouts.app')

@section('active', 'customers')
@section('page_title', 'Edit Customer')
@section('page_breadcrumb', 'FUELCORE / Customers / Edit')

@section('content')
  <div class="page-head">
    <div><h1>Edit {{ $customer->name }}</h1></div>
    <div class="page-actions">
      <a href="{{ route('customers.show', $customer) }}" class="btn"><i data-lucide="eye"></i> View</a>
      <a href="{{ route('customers.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('customers.update', $customer) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Customer Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer Type *</label>
          <select class="form-control" name="type" required>
            @foreach (['individual', 'corporate', 'fleet', 'government', 'other'] as $t)
              <option value="{{ $t }}" {{ old('type', $customer->type) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name', $customer->name) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone', $customer->phone) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="{{ old('email', $customer->email) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input class="form-control" type="text" name="address" value="{{ old('address', $customer->address) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Tax ID (TIN)</label>
          <input class="form-control" type="text" name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Credit Limit ({{ currency() }})</label>
          <input class="form-control" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['active', 'inactive', 'blocked'] as $s)
              <option value="{{ $s }}" {{ old('status', $customer->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Customer</button>
      <a href="{{ route('customers.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection