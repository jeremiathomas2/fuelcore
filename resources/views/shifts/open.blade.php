@extends('layouts.app')

@section('active', 'shifts')
@section('page_title', 'Open Shift')
@section('page_breadcrumb', 'FUELCORE / Shifts / Open')

@section('content')
  <div class="page-head">
    <div><h1>Open New Shift</h1><p>Record the opening float and start the shift.</p></div>
    <div class="page-actions"><a href="{{ route('shifts.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('shifts.store') }}" class="card" style="max-width:640px;">
    @csrf
    <div class="card-header"><h3>Shift Details</h3></div>
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
          <label class="form-label">Employee</label>
          <select class="form-control" name="employee_id">
            <option value="">Me ({{ auth()->user()->name }})</option>
            @foreach ($employees as $emp)
              <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Opening Cash ({{ currency() }}) *</label>
          <input class="form-control" type="number" step="0.01" min="0" name="opening_cash" value="{{ old('opening_cash', 0) }}" required>
          @error('opening_cash')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Opening Meter Reading (L)</label>
          <input class="form-control" type="number" step="0.01" min="0" name="opening_meter" value="{{ old('opening_meter') }}">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="play"></i> Open Shift</button>
      <a href="{{ route('shifts.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection