@extends('layouts.app')

@section('active', 'reconciliations')
@section('page_title', 'Reconciliations')
@section('page_breadcrumb', 'FUELCORE / Reconciliations')

@section('content')
  <div class="page-head">
    <div><h1>Fuel &amp; Cash Reconciliations</h1><p>Compare expected closing stock against readings to detect variances.</p></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('reconciliations.run') }}" class="card" style="margin-bottom:24px;">
    @csrf
    <div class="card-header"><h3><i data-lucide="git-compare"></i> Run Reconciliation</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station *</label>
          <select class="form-control" name="station_id" id="reconStation" required>
            <option value="">Select station</option>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Shift (optional)</label>
          <select class="form-control" name="shift_id">
            <option value="">—</option>
            @foreach ($openShifts as $sh)
              <option value="{{ $sh->id }}">{{ $sh->shift_number }} — {{ $sh->employee?->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">From</label>
          <input class="form-control" type="date" name="from" value="{{ old('from') }}">
        </div>
        <div class="form-group">
          <label class="form-label">To</label>
          <input class="form-control" type="date" name="to" value="{{ old('to') }}">
        </div>
      </div>
      @error('error')<div class="alert-box alert-error" style="margin-top:12px;"><i data-lucide="alert-triangle"></i><div>{{ $message }}</div></div>@enderror
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="play"></i> Run Reconciliation</button>
    </div>
  </form>

  <section class="filter-bar">
    <form action="{{ route('reconciliations.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (\App\Models\Reconciliation::STATUSES as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('reconciliations.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Recon No</th><th>Station</th><th>Shift</th><th class="numeric">Opening</th><th class="numeric">Expected Closing</th><th class="numeric">Actual Closing</th><th class="numeric">Variance</th><th>Status</th><th>Reconciled By</th></tr></thead>
          <tbody>
            @forelse ($reconciliations as $r)
              <tr>
                <td class="mono">{{ $r->reconciliation_number }}</td>
                <td>{{ $r->station?->name }}</td>
                <td class="muted small">{{ $r->shift?->shift_number ?? '—' }}</td>
                <td class="numeric">{{ number_format((float) $r->opening_stock, 2) }} L</td>
                <td class="numeric">{{ number_format((float) $r->expected_closing, 2) }} L</td>
                <td class="numeric bold">{{ number_format((float) $r->actual_closing, 2) }} L</td>
                @php($variance = (float) $r->variance_litres)
                <td class="numeric {{ $variance < 0 ? 'text-danger' : '' }}">{{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }} L</td>
                <td><span class="status-pill-sm {{ $r->status === 'reconciled' ? 'online' : ($r->status === 'disputed' ? 'warning' : 'neutral') }}">{{ $r->status }}</span></td>
                <td class="muted small">{{ $r->reconciledBy?->name ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No reconciliations yet.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $reconciliations->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection