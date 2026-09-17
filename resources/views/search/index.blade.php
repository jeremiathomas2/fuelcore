@extends('layouts.app')

@section('page_title', 'Search')
@section('page_breadcrumb', 'FUELCORE / Search')

@section('content')
  <div class="page-head">
    <div>
      <h1>Global Search</h1>
      <p>Search across transactions, customers, fleet accounts, suppliers, stations and deliveries.</p>
    </div>
  </div>

  <form action="{{ route('search') }}" method="GET" class="card" style="margin-bottom:24px;">
    <div class="card-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
      <div style="flex:1;min-width:260px;position:relative;">
        <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--text-muted);"></i>
        <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search receipt number, customer, fleet account, supplier…" style="padding-left:34px;font-size:0.8rem;" autofocus>
      </div>
      <button type="submit" class="btn btn-sm primary"><i data-lucide="search"></i> Search</button>
    </div>
  </form>

  @if ($query)
    <div class="page-head" style="margin-bottom:16px;">
      <p style="color:var(--text-muted);"><strong>"{{ $query }}"</strong> — {{ $results['transactions']['total'] }} transactions, {{ $results['customers']['total'] }} customers, {{ $results['fleet']['total'] }} fleet accounts, {{ $results['suppliers']['total'] }} suppliers, {{ $results['stations']['total'] }} stations, {{ $results['deliveries']['total'] }} deliveries</p>
    </div>

    @if ($results['transactions']['total'] > 0)
      @include('search.partials.transactions', ['items' => $results['transactions']])
    @endif
    @if ($results['customers']['total'] > 0)
      @include('search.partials.customers', ['items' => $results['customers']])
    @endif
    @if ($results['fleet']['total'] > 0)
      @include('search.partials.fleet', ['items' => $results['fleet']])
    @endif
    @if ($results['suppliers']['total'] > 0)
      @include('search.partials.suppliers', ['items' => $results['suppliers']])
    @endif
    @if ($results['stations']['total'] > 0)
      @include('search.partials.stations', ['items' => $results['stations']])
    @endif
    @if ($results['deliveries']['total'] > 0)
      @include('search.partials.deliveries', ['items' => $results['deliveries']])
    @endif

    @if ($results['transactions']['total'] + $results['customers']['total'] + $results['fleet']['total'] + $results['suppliers']['total'] + $results['stations']['total'] + $results['deliveries']['total'] === 0)
      <div class="card"><div class="card-body"><div class="empty-state"><i data-lucide="search-x"></i>No results found for "{{ $query }}".</div></div></div>
    @endif
  @endif
@endsection