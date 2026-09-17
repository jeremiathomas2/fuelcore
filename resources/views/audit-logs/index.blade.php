@extends('layouts.app')

@section('active', 'audit-logs')
@section('page_title', 'Audit Logs')
@section('page_breadcrumb', 'FUELCORE / Audit Logs')

@section('content')
  <div class="page-head">
    <div><h1>Audit Trail</h1><p>Immutable record of all admin &amp; system actions.</p></div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('audit-logs.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search description…" style="flex:1;min-width:220px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="module" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Modules</option>
        @foreach ($modules as $m)
          <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
        @endforeach
      </select>
      <select name="action" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Actions</option>
        @foreach (['create', 'update', 'delete', 'void', 'login', 'logout', 'alert', 'receive', 'adjust', 'approve', 'reject'] as $a)
          <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Timestamp</th><th>User</th><th>Module</th><th>Action</th><th>Description</th></tr></thead>
          <tbody>
            @forelse ($logs as $l)
              @php($tone = ['create' => 'online', 'update' => 'info', 'delete' => 'error', 'void' => 'error', 'approve' => 'online', 'reject' => 'warning'][$l->action] ?? 'neutral')
              <tr>
                <td class="muted small">{{ $l->created_at->format('d M Y H:i:s') }}</td>
                <td class="small">{{ $l->user?->name ?? 'System' }}</td>
                <td><span class="badge badge-info">{{ ucfirst($l->module) }}</span></td>
                <td><span class="status-pill-sm {{ $tone }}">{{ $l->action }}</span></td>
                <td class="small">{{ $l->description }}</td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty-state">No audit records.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $logs->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection