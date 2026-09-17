@extends('layouts.app')

@section('active', 'shifts')
@section('page_title', 'Shifts')
@section('page_breadcrumb', 'FUELCORE / Shifts')

@section('content')
  <div class="page-head">
    <div><h1>Shifts</h1><p>{{ $openShifts }} shift(s) currently open.</p></div>
    <div class="page-actions">
      @can('shift.manage')
        <a href="{{ route('shifts.open') }}" class="btn btn-primary"><i data-lucide="plus"></i> Open Shift</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('shifts.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['open', 'closed', 'cancelled'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('shifts.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Shift</th><th>Station</th><th>Employee</th><th class="numeric">Opening Cash</th><th class="numeric">Closing Cash</th><th class="numeric">Expected</th><th>Opened</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($shifts as $shift)
              <tr>
                <td class="bold mono">{{ $shift->shift_number }}</td>
                <td>{{ $shift->station?->name }}</td>
                <td class="muted small">{{ $shift->employee?->name }}</td>
                <td class="numeric">{{ currency() }} {{ number_format((float) $shift->opening_cash, 0) }}</td>
                <td class="numeric">{{ $shift->closing_cash !== null ? currency().' '.number_format((float) $shift->closing_cash, 0) : '—' }}</td>
                <td class="numeric">{{ $shift->expected_cash !== null ? currency().' '.number_format((float) $shift->expected_cash, 0) : '—' }}</td>
                <td class="muted small">{{ $shift->opened_at?->format('d M Y H:i') }}</td>
                <td><span class="status-pill-sm {{ $shift->status === 'open' ? 'online' : ($shift->status === 'closed' ? 'neutral' : 'warning') }}">{{ $shift->status }}</span></td>
                <td class="center">
                  @if ($shift->status === 'open')
                    @can('shift.manage')
                      <button type="button" class="btn-sm" onclick="window.FcModal?.open ? window.FcModal.open('closeShift{{ $shift->id }}') : document.getElementById('closeShift{{ $shift->id }}').classList.add('open')"><i data-lucide="lock-open"></i> Close</button>
                    @endcan
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No shifts found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $shifts->withQueryString()->links('partials.pagination') }}
    </div>
  </div>

  @foreach ($shifts as $shift)
    @if ($shift->status === 'open')
      <form class="modal-overlay" id="closeShift{{ $shift->id }}" method="POST" action="{{ route('shifts.close', $shift) }}">
        @csrf
        <div class="modal">
          <div class="modal-header"><h3>Close Shift {{ $shift->shift_number }}</h3><button type="button" class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('open')"><i data-lucide="x"></i></button></div>
          <div class="modal-body">
            <p class="muted small">Expected cash is computed from POS sales for this shift. Enter the physical cash counted at handover.</p>
            <div class="form-group">
              <label class="form-label">Actual Cash Counted *</label>
              <input class="form-control" type="number" step="0.01" min="0" name="actual_cash" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm" onclick="this.closest('.modal-overlay').classList.remove('open')">Cancel</button>
            <button type="submit" class="btn btn-sm primary"><i data-lucide="lock"></i> Close Shift</button>
          </div>
        </div>
      </form>
    @endif
  @endforeach
@endsection