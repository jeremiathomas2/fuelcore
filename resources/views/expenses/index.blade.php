@extends('layouts.app')

@section('active', 'expenses')
@section('page_title', 'Expenses')
@section('page_breadcrumb', 'FUELCORE / Expenses')

@section('content')
  <div class="page-head">
    <div>
      <h1>Expenses</h1>
      <p class="muted">
        @if ($total)
          {{ number_format((float) $total->count, 0) }} expenses · Total {{ currency() }} {{ number_format((float) $total->total, 0) }}
        @endif
      </p>
    </div>
    <div class="page-actions">
      @can('expense.manage')
        <a href="{{ route('expenses.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Record Expense</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('expenses.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Stations</option>
        @foreach ($stations as $st)
          <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
        @endforeach
      </select>
      <select name="category_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Categories</option>
        @foreach ($categories as $cat)
          <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
      </select>
      <select name="approval_status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['pending', 'approved', 'rejected'] as $s)
          <option value="{{ $s }}" {{ request('approval_status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <input type="date" name="from" value="{{ request('from') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <input type="date" name="to" value="{{ request('to') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('expenses.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Date</th><th>Station</th><th>Category</th><th>Description</th><th class="numeric">Amount</th><th>Payment</th><th>By</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($expenses as $e)
              <tr>
                <td class="muted small">{{ $e->expense_date->format('d M Y') }}</td>
                <td>{{ $e->station?->name }}</td>
                <td><span class="badge badge-info">{{ $e->category?->name ?? '—' }}</span></td>
                <td class="small">{{ $e->description }}</td>
                <td class="numeric bold">{{ currency() }} {{ number_format((float) $e->amount, 0) }}</td>
                <td class="muted small">{{ ucfirst(str_replace('_', ' ', $e->payment_method)) }}</td>
                <td class="muted small">{{ $e->createdBy?->name }}</td>
                <td><span class="status-pill-sm {{ $e->approval_status === 'approved' ? 'online' : ($e->approval_status === 'rejected' ? 'warning' : 'neutral') }}">{{ $e->approval_status }}</span></td>
                <td class="center">
                  @can('expense.approve')
                    @if ($e->approval_status === 'pending')
                      <form method="POST" action="{{ route('expenses.approve', $e) }}" style="display:inline;" onsubmit="return confirm('Approve this expense?')">
                        @csrf
                        <input type="hidden" name="approval_status" value="approved">
                        <button class="btn-sm success" title="Approve"><i data-lucide="check"></i></button>
                      </form>
                      <form method="POST" action="{{ route('expenses.approve', $e) }}" style="display:inline;" onsubmit="return confirm('Reject this expense?')">
                        @csrf
                        <input type="hidden" name="approval_status" value="rejected">
                        <button class="btn-sm danger" title="Reject"><i data-lucide="x"></i></button>
                      </form>
                    @endif
                  @endcan
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">No expenses recorded.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $expenses->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection