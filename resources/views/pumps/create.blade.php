@extends('layouts.app')

@section('active', 'pumps')
@section('page_title', 'Add Pump')
@section('page_breadcrumb', 'FUELCORE / Pumps / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Fuel Pump</h1></div>
    <div class="page-actions"><a href="{{ route('pumps.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('pumps.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Pump Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station *</label>
          <select class="form-control" name="station_id" required>
            <option value="">Select station</option>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
          @error('station_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Pump Number *</label>
          <input class="form-control" type="text" name="pump_number" value="{{ old('pump_number') }}" placeholder="e.g. 01" required>
          @error('pump_number')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Manufacturer</label>
          <input class="form-control" type="text" name="manufacturer" value="{{ old('manufacturer') }}" placeholder="e.g. Gilbarco">
        </div>
        <div class="form-group">
          <label class="form-label">Model</label>
          <input class="form-control" type="text" name="model" value="{{ old('model') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Serial Number</label>
          <input class="form-control" type="text" name="serial_number" value="{{ old('serial_number') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Controller Address</label>
          <input class="form-control" type="text" name="controller_address" value="{{ old('controller_address') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Device Identifier</label>
          <input class="form-control" type="text" name="device_identifier" value="{{ old('device_identifier') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Installation Date</label>
          <input class="form-control" type="date" name="installation_date" value="{{ old('installation_date') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['online', 'offline', 'maintenance', 'error'] as $s)
              <option value="{{ $s }}" {{ old('status', 'offline') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Pump</button>
      <a href="{{ route('pumps.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection
