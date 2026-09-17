@extends('layouts.app')

@section('active', 'pumps')
@section('page_title', 'Edit Nozzle')
@section('page_breadcrumb', 'FUELCORE / Nozzles / Edit')

@section('content')
  <div class="page-head">
    <div><h1>Edit Nozzle {{ $nozzle->nozzle_number }}</h1></div>
    <div class="page-actions"><a href="{{ route('nozzles.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('nozzles.update', $nozzle) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Nozzle Assignment</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Pump *</label>
          <select class="form-control" name="pump_id" required>
            @foreach ($pumps as $pm)
              <option value="{{ $pm->id }}" {{ old('pump_id', $nozzle->pump_id) == $pm->id ? 'selected' : '' }}>Pump {{ $pm->pump_number }} — {{ $pm->station->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Fuel Product *</label>
          <select class="form-control" name="fuel_product_id" required>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" {{ old('fuel_product_id', $nozzle->fuel_product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nozzle Number *</label>
          <input class="form-control" type="text" name="nozzle_number" value="{{ old('nozzle_number', $nozzle->nozzle_number) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Meter Start (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="meter_start" value="{{ old('meter_start', $nozzle->meter_start) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Meter Current (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="meter_current" value="{{ old('meter_current', $nozzle->meter_current) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['idle', 'dispensing', 'completed', 'offline', 'error', 'maintenance'] as $s)
              <option value="{{ $s }}" {{ old('status', $nozzle->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Nozzle</button>
      <a href="{{ route('nozzles.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection