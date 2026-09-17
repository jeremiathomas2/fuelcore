@extends('layouts.app')

@section('active', 'suppliers')
@section('page_title', 'Add Supplier')
@section('page_breadcrumb', 'FUELCORE / Suppliers / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Supplier</h1></div>
    <div class="page-actions"><a href="{{ route('suppliers.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('suppliers.store') }}" class="card" style="max-width:680px;">
    @csrf
    <div class="card-header"><h3>Supplier Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Company Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name') }}" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Contact Person</label>
          <input class="form-control" type="text" name="contact_person" value="{{ old('contact_person') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone') }}">
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
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['active', 'inactive'] as $s)
              <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Performance Rating (1–5)</label>
          <input class="form-control" type="number" min="1" max="5" name="performance_rating" value="{{ old('performance_rating') }}">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Supplier</button>
      <a href="{{ route('suppliers.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection