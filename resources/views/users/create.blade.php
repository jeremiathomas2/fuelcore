@extends('layouts.app')

@section('active', 'users')
@section('page_title', 'Add User')
@section('page_breadcrumb', 'FUELCORE / Users / Add')

@section('content')
  <div class="page-head">
    <div><h1>Add User</h1></div>
    <div class="page-actions"><a href="{{ route('users.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('users.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>User Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name') }}" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
          @error('email')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Employee Number</label>
          <input class="form-control" type="text" name="employee_number" value="{{ old('employee_number') }}">
        </div>
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select class="form-control" name="role_id" required>
            @foreach ($roles as $r)
              <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
          </select>
          @error('role_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Home Station</label>
          <select class="form-control" name="station_id">
            <option value="">Select station</option>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Assigned Stations</label>
          <select class="form-control" name="assigned_stations[]" multiple size="4">
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ in_array($st->id, old('assigned_stations', [])) ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
          <div class="muted small" style="margin-top:4px;">Hold Ctrl/Cmd to select multiple</div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['active', 'suspended'] as $s)
              <option value="{{ $s }}" {{ old('status', 'active') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input class="form-control" type="password" name="password" required>
          <div class="muted small" style="margin-top:4px;">Min 8 chars, upper+lower+numbers required</div>
          @error('password')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input class="form-control" type="password" name="password_confirmation" required>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save User</button>
      <a href="{{ route('users.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection