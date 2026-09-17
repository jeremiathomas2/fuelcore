@extends('layouts.app')

@section('active', 'stations')
@section('page_title', 'Edit Station')
@section('page_breadcrumb', 'FUELCORE / Stations / Edit')

@section('content')
  <div class="page-head">
    <div>
      <h1>Edit Station — {{ $station->name }}</h1>
    </div>
    <div class="page-actions">
      <a href="{{ route('stations.show', $station) }}" class="btn"><i data-lucide="eye"></i> View</a>
      <a href="{{ route('stations.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('stations.update', $station) }}" class="card">
    @csrf
    @method('PUT')
    <div class="card-header"><h3>Station Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station Name *</label>
          <input class="form-control" type="text" name="name" value="{{ old('name', $station->name) }}" required>
          @error('name')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Station Code *</label>
          <input class="form-control" type="text" name="code" value="{{ old('code', $station->code) }}" required>
          @error('code')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Region *</label>
          <select class="form-control" name="region" required>
            <option value="">Select region</option>
            @foreach (options('regions') as $region)
              <option value="{{ $region }}" {{ old('region', $station->region) === $region ? 'selected' : '' }}>{{ $region }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status">
            @foreach (['online', 'offline', 'maintenance', 'warning'] as $status)
              <option value="{{ $status }}" {{ old('status', $station->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Location / Address</label>
          <input class="form-control" type="text" name="location" value="{{ old('location', $station->location) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Manager</label>
          <input class="form-control" type="text" name="manager_name" value="{{ old('manager_name', $station->manager_name) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" type="text" name="phone" value="{{ old('phone', $station->phone) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="{{ old('email', $station->email) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Latitude</label>
          <input class="form-control" type="number" step="any" name="latitude" value="{{ old('latitude', $station->latitude) }}">
        </div>
        <div class="form-group">
          <label class="form-label">Longitude</label>
          <input class="form-control" type="number" step="any" name="longitude" value="{{ old('longitude', $station->longitude) }}">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Station</button>
      <a href="{{ route('stations.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection