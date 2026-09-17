@extends('layouts.app')

@section('active', 'tanks')
@section('page_title', 'Edit Tank')
@section('page_breadcrumb', 'FUELCORE / Tanks / Edit')

@section('content')
  <div class="page-head">
    <div><h1>Edit Tank {{ $tank->tank_number }}</h1></div>
    <div class="page-actions"><a href="{{ route('tanks.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('tanks.update', $tank) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Tank Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station *</label>
          <select class="form-control" name="station_id" required>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id', $tank->station_id) == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Fuel Product *</label>
          <select class="form-control" name="fuel_product_id" required>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" {{ old('fuel_product_id', $tank->fuel_product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tank Number *</label>
          <input class="form-control" type="text" name="tank_number" value="{{ old('tank_number', $tank->tank_number) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Capacity (L) *</label>
          <input class="form-control" type="number" step="0.01" min="1" name="capacity" value="{{ old('capacity', $tank->capacity) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Current Volume (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="current_volume" value="{{ old('current_volume', $tank->current_volume) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Min Level (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="min_level" value="{{ old('min_level', $tank->min_level) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Max Level (L)</label>
          <input class="form-control" type="number" step="0.01" name="max_level" value="{{ old('max_level', $tank->max_level) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Leak Status</label>
          <select class="form-control" name="leak_status">
            @foreach (['no', 'yes', 'unknown'] as $s)
              <option value="{{ $s }}" {{ old('leak_status', $tank->leak_status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Tank</button>
      <a href="{{ route('tanks.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection