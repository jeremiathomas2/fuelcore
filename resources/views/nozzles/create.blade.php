@extends('layouts.app')

@section('active', 'nozzles')
@section('page_title', 'Add Nozzle')
@section('page_breadcrumb', 'FUELCORE / Nozzles / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Nozzle</h1></div>
    <div class="page-actions"><a href="{{ route('nozzles.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('nozzles.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Nozzle Assignment</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Pump *</label>
          <select class="form-control" name="pump_id" id="pumpSelect" required>
            <option value="">Select pump</option>
            @foreach ($pumps as $pm)
              <option value="{{ $pm->id }}" data-station="{{ $pm->station->name }}" {{ old('pump_id') == $pm->id ? 'selected' : '' }}>Pump {{ $pm->pump_number }} — {{ $pm->station->name }}</option>
            @endforeach
          </select>
          @error('pump_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Fuel Product *</label>
          <select class="form-control" name="fuel_product_id" required>
            <option value="">Select fuel</option>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" {{ old('fuel_product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ currency() }} {{ number_format((float) $p->price, 0) }})</option>
            @endforeach
          </select>
          @error('fuel_product_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Nozzle Number *</label>
          <input class="form-control" type="text" name="nozzle_number" value="{{ old('nozzle_number') }}" placeholder="e.g. 01" required>
          @error('nozzle_number')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Meter Start (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="meter_start" value="{{ old('meter_start', 0) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Meter Current (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="meter_current" value="{{ old('meter_current', 0) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['idle', 'dispensing', 'completed', 'offline', 'error', 'maintenance'] as $s)
              <option value="{{ $s }}" {{ old('status', 'idle') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Nozzle</button>
      <a href="{{ route('nozzles.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection
