@extends('layouts.app')

@section('active', 'fleet')
@section('page_title', 'Add Fleet Account')
@section('page_breadcrumb', 'FUELCORE / Fleet / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Fleet Account</h1></div>
    <div class="page-actions"><a href="{{ route('fleet.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('fleet.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Account Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer *</label>
          <select class="form-control" name="customer_id" required>
            <option value="">Select customer</option>
            @foreach ($customers as $c)
              <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
          </select>
          @error('customer_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Company Name *</label>
          <input class="form-control" type="text" name="company_name" value="{{ old('company_name') }}" required>
          @error('company_name')<div class="field-error">{{ $message }}</div>@enderror
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
          <label class="form-label">Fuel Limit (Litres)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="fuel_limit_litres" value="{{ old('fuel_limit_litres') }}">
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
        <div class="form-group">
          <div class="form-check"><input type="checkbox" id="monthly_statement" name="monthly_statement" value="1" {{ old('monthly_statement') ? 'checked' : '' }}><label for="monthly_statement">Send monthly statements</label></div>
        </div>
      </div>
    </div>

    <div class="card-header"><h3>Vehicles</h3></div>
    <div class="card-body">
      <div id="vehiclesWrap">
        <div class="vehicle-row form-grid" style="grid-template-columns:1fr 1fr 1fr 1fr auto;">
          <div class="form-group"><input class="form-control" type="text" name="vehicles[0][registration_number]" placeholder="Registration (T 123 ABC)"></div>
          <div class="form-group"><input class="form-control" type="text" name="vehicles[0][driver_name]" placeholder="Driver name"></div>
          <div class="form-group"><input class="form-control" type="text" name="vehicles[0][driver_phone]" placeholder="Driver phone"></div>
          <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="vehicles[0][fuel_limit_litres]" placeholder="Fuel limit (L)"></div>
          <button type="button" class="btn-sm danger remove-vehicle" style="align-self:center;">✕</button>
        </div>
      </div>
      <button type="button" class="btn-sm" id="addVehicle"><i data-lucide="plus"></i> Add Vehicle</button>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Fleet Account</button>
      <a href="{{ route('fleet.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      let vi = 1;
      document.getElementById('addVehicle').addEventListener('click', () => {
        const wrap = document.getElementById('vehiclesWrap');
        const row = document.createElement('div');
        row.className = 'vehicle-row form-grid';
        row.style.cssText = 'grid-template-columns:1fr 1fr 1fr 1fr auto;';
        row.innerHTML = `
          <div class="form-group"><input class="form-control" type="text" name="vehicles[${vi}][registration_number]" placeholder="Registration (T 123 ABC)"></div>
          <div class="form-group"><input class="form-control" type="text" name="vehicles[${vi}][driver_name]" placeholder="Driver name"></div>
          <div class="form-group"><input class="form-control" type="text" name="vehicles[${vi}][driver_phone]" placeholder="Driver phone"></div>
          <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="vehicles[${vi}][fuel_limit_litres]" placeholder="Fuel limit (L)"></div>
          <button type="button" class="btn-sm danger remove-vehicle" style="align-self:center;">✕</button>`;
        wrap.appendChild(row);
        vi++;
      });
      document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-vehicle')) e.target.closest('.vehicle-row').remove();
      });
    });
  </script>
@endpush