@extends('layouts.app')

@section('active', 'tanks')
@section('page_title', 'Add Tank')
@section('page_breadcrumb', 'FUELCORE / Tanks / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add Fuel Tank</h1></div>
    <div class="page-actions"><a href="{{ route('tanks.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('tanks.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Tank Details</h3></div>
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
          <label class="form-label">Fuel Product *</label>
          <select class="form-control" name="fuel_product_id" required>
            <option value="">Select fuel</option>
            @foreach ($products as $p)
              <option value="{{ $p->id }}" {{ old('fuel_product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
          @error('fuel_product_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Tank Number *</label>
          <input class="form-control" type="text" name="tank_number" value="{{ old('tank_number') }}" placeholder="e.g. T1" required>
          @error('tank_number')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Capacity (L) *</label>
          <input class="form-control" type="number" step="0.01" min="1" name="capacity" value="{{ old('capacity') }}" required>
          @error('capacity')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Current Volume (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="current_volume" value="{{ old('current_volume', 0) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Min Level (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="min_level" value="{{ old('min_level') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Max Level (L)</label>
          <input class="form-control" type="number" step="0.01" name="max_level" value="{{ old('max_level') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Leak Status</label>
          <select class="form-control" name="leak_status">
            @foreach (['no', 'yes', 'unknown'] as $s)
              <option value="{{ $s }}" {{ old('leak_status', 'no') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Tank</button>
      <a href="{{ route('tanks.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection