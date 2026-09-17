@extends('layouts.app')

@section('active', 'fleet')
@section('page_title', 'Edit Fleet Account')
@section('page_breadcrumb', 'FUELCORE / Fleet / Edit')

@section('content')
  <div class="page-head">
    <div><h1>Edit {{ $account->company_name }}</h1></div>
    <div class="page-actions">
      <a href="{{ route('fleet.show', $account) }}" class="btn"><i data-lucide="eye"></i> View</a>
      <a href="{{ route('fleet.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('fleet.update', $account) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Account Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer *</label>
          <select class="form-control" name="customer_id" required>
            @foreach ($customers as $c)
              <option value="{{ $c->id }}" {{ old('customer_id', $account->customer_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Company Name *</label>
          <input class="form-control" type="text" name="company_name" value="{{ old('company_name', $account->company_name) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Contact Person</label>
          <input class="form-control" type="text" name="contact_person" value="{{ old('contact_person', $account->contact_person) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone', $account->phone) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="{{ old('email', $account->email) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Fuel Limit (Litres)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="fuel_limit_litres" value="{{ old('fuel_limit_litres', $account->fuel_limit_litres) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Credit Limit ({{ currency() }})</label>
          <input class="form-control" type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', $account->credit_limit) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['active', 'inactive', 'blocked'] as $s)
              <option value="{{ $s }}" {{ old('status', $account->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <div class="form-check"><input type="checkbox" id="monthly_statement" name="monthly_statement" value="1" {{ old('monthly_statement', $account->monthly_statement) ? 'checked' : '' }}><label for="monthly_statement">Send monthly statements</label></div>
        </div>
      </div>
    </div>

    <div class="card-header"><h3>Vehicles</h3></div>
    <div class="card-body">
      <div id="vehiclesWrap">
        @forelse ($account->vehicles as $i => $vehicle)
          <div class="vehicle-row form-grid" style="grid-template-columns:1fr 1fr 1fr 1fr auto;">
            <input type="hidden" name="vehicles[{{ $i }}][id]" value="{{ $vehicle->id }}">
            <div class="form-group"><input class="form-control" type="text" name="vehicles[{{ $i }}][registration_number]" value="{{ old('vehicles.'.$i.'.registration_number', $vehicle->registration_number) }}" placeholder="Registration"></div>
            <div class="form-group"><input class="form-control" type="text" name="vehicles[{{ $i }}][driver_name]" value="{{ old('vehicles.'.$i.'.driver_name', $vehicle->driver_name) }}" placeholder="Driver name"></div>
            <div class="form-group"><input class="form-control" type="text" name="vehicles[{{ $i }}][driver_phone]" value="{{ old('vehicles.'.$i.'.driver_phone', $vehicle->driver_phone) }}" placeholder="Driver phone"></div>
            <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="vehicles[{{ $i }}][fuel_limit_litres]" value="{{ old('vehicles.'.$i.'.fuel_limit_litres', $vehicle->fuel_limit_litres) }}" placeholder="Fuel limit (L)"></div>
            <button type="button" class="btn-sm danger remove-vehicle" style="align-self:center;">✕</button>
          </div>
        @empty
          <div class="vehicle-row form-grid" style="grid-template-columns:1fr 1fr 1fr 1fr auto;">
            <div class="form-group"><input class="form-control" type="text" name="vehicles[0][registration_number]" placeholder="Registration (T 123 ABC)"></div>
            <div class="form-group"><input class="form-control" type="text" name="vehicles[0][driver_name]" placeholder="Driver name"></div>
            <div class="form-group"><input class="form-control" type="text" name="vehicles[0][driver_phone]" placeholder="Driver phone"></div>
            <div class="form-group"><input class="form-control" type="number" step="0.01" min="0" name="vehicles[0][fuel_limit_litres]" placeholder="Fuel limit (L)"></div>
            <button type="button" class="btn-sm danger remove-vehicle" style="align-self:center;">✕</button>
          </div>
        @endforelse
      </div>
      <p class="muted small" style="margin:8px 0;">Removing a vehicle row archives it against this fleet account.</p>
      <button type="button" class="btn-sm" id="addVehicle"><i data-lucide="plus"></i> Add Vehicle</button>
    </div>

    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Fleet Account</button>
      <a href="{{ route('fleet.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      let vi = {{ max($account->vehicles->count(), 1) }};
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