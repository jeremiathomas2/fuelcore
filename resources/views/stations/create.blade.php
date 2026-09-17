@extends('layouts.app')

@section('active', 'stations')
@section('page_title', 'Add Station')
@section('page_breadcrumb', 'FUELCORE / Stations / Add')

@section('content')
  <div class="page-head">
    <div>
      <h1>Add Filling Station</h1>
      <p>Register a new station in the network.</p>
    </div>
    <div class="page-actions">
      <a href="{{ route('stations.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('stations.store') }}" class="card">
    @csrf
    <div class="card-header"><h3>Station Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Moshi Main Station" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Station Code *</label>
          <input class="form-control" type="text" name="code" value="{{ old('code') }}" placeholder="e.g. MOS-001" required>
          @error('code')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Region *</label>
          <select class="form-control" name="region" required>
            <option value="">Select region</option>
            @foreach (options('regions') as $region)
              <option value="{{ $region }}" {{ old('region') === $region ? 'selected' : '' }}>{{ $region }}</option>
            @endforeach
          </select>
          @error('region')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['online', 'offline', 'maintenance', 'warning'] as $status)
              <option value="{{ $status }}" {{ old('status', 'online') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Location / Address</label>
          <input class="form-control" type="text" name="location" value="{{ old('location') }}" placeholder="Street, area, city">
        </div>
        <div class="form-group">
          <label class="form-label">Manager</label>
          <input class="form-control" type="text" name="manager_name" value="{{ old('manager_name') }}" placeholder="Station manager name">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone') }}" placeholder="+255 7xx xxx xxx">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="station@fuelcore.test">
        </div>
        <div class="form-group">
          <label class="form-label">Latitude</label>
          <input class="form-control" type="number" step="any" name="latitude" value="{{ old('latitude') }}" placeholder="-3.3357">
        </div>
        <div class="form-group">
          <label class="form-label">Longitude</label>
          <input class="form-control" type="number" step="any" name="longitude" value="{{ old('longitude') }}" placeholder="37.3405">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Station</button>
      <a href="{{ route('stations.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection