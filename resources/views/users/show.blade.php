@extends('layouts.app')

@section('active', 'users')
@section('page_title', $user->name)
@section('page_breadcrumb', 'FUELCORE / Users / Profile')

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $user->name }}</h1>
      <p>{{ $user->email }} · {{ $user->employee_number }}</p>
    </div>
    <div class="page-actions">
      @can('user.manage')
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary"><i data-lucide="pencil"></i> Edit</a>
        @if ($user->id !== auth()->id())
          <form method="POST" action="{{ route('users.toggleStatus', $user) }}" style="display:inline;">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $user->status === 'active' ? '' : 'btn-success' }}">
              <i data-lucide="{{ $user->status === 'active' ? 'power' : 'power-off' }}"></i>
              {{ $user->status === 'active' ? 'Suspend' : 'Activate' }}
            </button>
          </form>
          <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;" onsubmit="return confirm('Delete {{ $user->name }}? This permanently removes the user and their station assignments.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i data-lucide="trash-2"></i> Delete</button>
          </form>
        @endif
      @endcan
      <a href="{{ route('users.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a>
    </div>
  </div>

  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon blue"><i data-lucide="receipt"></i></div></div>
      <div class="kpi-value">{{ $sales->count() }}</div>
      <div class="kpi-label">Recent Sales</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon teal"><i data-lucide="clock"></i></div></div>
      <div class="kpi-value">{{ $shifts->count() }}</div>
      <div class="kpi-label">Recent Shifts</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon amber"><i data-lucide="shield-check"></i></div></div>
      <div class="kpi-value">{{ $user->roleLabel() }}</div>
      <div class="kpi-label">Role</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-header"><div class="kpi-icon {{ $user->status === 'active' ? 'green' : 'red' }}"><i data-lucide="power"></i></div></div>
      <div class="kpi-value">{{ ucfirst($user->status) }}</div>
      <div class="kpi-label">Status</div>
    </div>
  </section>

  <section class="two-col">
    <div class="card">
      <div class="card-header"><h3><i data-lucide="receipt"></i> Recent Sales</h3></div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Amount</th></tr></thead>
            <tbody>
              @forelse ($sales as $s)
                <tr>
                  <td class="mono">{{ $s->transaction_number }}</td>
                  <td>{{ $s->station?->name }}</td>
                  <td>{{ $s->fuelProduct?->name }}</td>
                  <td class="numeric bold">{{ currency() }} {{ number_format((float) $s->net_amount, 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="4"><div class="empty-state">No sales recorded.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i data-lucide="clock"></i> Shifts</h3></div>
      <div class="card-body flush">
        <div class="table-wrapper">
          <table class="data-table">
            <thead><tr><th>Shift</th><th>Station</th><th>Opened</th><th>Status</th></tr></thead>
            <tbody>
              @forelse ($shifts as $sh)
                <tr>
                  <td class="mono">{{ $sh->shift_number }}</td>
                  <td>{{ $sh->station?->name }}</td>
                  <td class="muted small">{{ $sh->opened_at?->format('d M Y H:i') }}</td>
                  <td><span class="status-pill-sm {{ $sh->status === 'open' ? 'online' : 'neutral' }}">{{ $sh->status }}</span></td>
                </tr>
              @empty
                <tr><td colspan="4"><div class="empty-state">No shifts yet.</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
@endsection