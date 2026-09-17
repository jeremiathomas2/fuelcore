@extends('layouts.app')

@section('active', 'settings')
@section('page_title', 'System Settings')
@section('page_breadcrumb', 'FUELCORE / Settings')

@section('content')
  <div class="page-head">
    <div><h1>System Settings</h1><p>Company profile, thresholds and tax defaults.</p></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @foreach ($settings as $group => $items)
      <div class="card" style="margin-bottom:24px;">
        <div class="card-header"><h3>{{ ucfirst($group) }}</h3></div>
        <div class="card-body">
          <div class="form-grid">
            @foreach ($items as $s)
              <div class="form-group">
                <label class="form-label">{{ ucwords(str_replace('_', ' ', $s->key)) }}</label>
                @if (in_array($s->key, ['tax_rate', 'low_stock_threshold', 'variance_warning_threshold']))
                  <input class="form-control" type="number" step="0.01" min="0" name="{{ $s->key }}" value="{{ old($s->key, $s->value) }}">
                @elseif ($s->key === 'alert_email')
                  <input class="form-control" type="email" name="{{ $s->key }}" value="{{ old($s->key, $s->value) }}">
                @elseif ($s->key === 'currency')
                  <input class="form-control" type="text" name="{{ $s->key }}" value="{{ old($s->key, $s->value) }}" style="width:100px;">
                @else
                  <input class="form-control" type="text" name="{{ $s->key }}" value="{{ old($s->key, $s->value) }}">
                @endif
                <div class="muted small" style="margin-top:4px;">{{ $s->description ?? '' }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
    <div style="display:flex;gap:12px;">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Settings</button>
    </div>
  </form>
@endsection