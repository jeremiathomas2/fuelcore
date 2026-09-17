@extends('layouts.app')

@section('active', 'reports')
@section('page_title', $meta['title'])
@section('page_breadcrumb', 'FUELCORE / Reports / ' . $meta['title'])

@section('content')
  <div class="page-head">
    <div><h1>{{ $meta['title'] }}</h1><p>{{ $meta['description'] }}</p></div>
    <div class="page-actions">
      <a href="{{ route('reports.index') }}" class="btn"><i data-lucide="arrow-left"></i> All Reports</a>
      <a href="{{ route('reports.export', $report) }}?{{ http_build_query(request()->query()) }}" class="btn btn-primary"><i data-lucide="download"></i> Export CSV</a>
    </div>
  </div>

  <section class="filter-bar">
    <form action="{{ route('reports.show', $report) }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="margin-bottom:4px;">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="margin-bottom:4px;">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="margin-bottom:4px;">Station</label>
        <select name="station_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
          <option value="">All</option>
          @foreach ($stations as $st)
            <option value="{{ $st->id }}" {{ request('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label" style="margin-bottom:4px;">Fuel</label>
        <select name="fuel_product_id" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
          <option value="">All</option>
          @foreach ($products as $p)
            <option value="{{ $p->id }}" {{ request('fuel_product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn-sm primary" type="submit" style="margin-top:18px;"><i data-lucide="filter"></i> Apply</button>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              @foreach ($columns as $col)
                <th class="{{ in_array($col, ['litres', 'revenue', 'count', 'last_balance', 'net_movement', 'opening', 'actual', 'variance', 'total', 'transactions', 'total_spent']) ? 'numeric' : '' }}">{{ \App\Http\Controllers\ReportController::COLUMN_LABELS[$col] ?? ucfirst(str_replace('_', ' ', $col)) }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse ($rows as $row)
              <tr>
                @foreach ($columns as $col)
                  @php($val = data_get($row, $col))
                  <td class="{{ in_array($col, ['litres', 'revenue', 'count', 'last_balance', 'net_movement', 'opening', 'actual', 'variance', 'total', 'transactions', 'total_spent']) ? 'numeric' : '' }}">
                    @if (in_array($col, ['revenue', 'opening', 'actual', 'variance', 'total_spent']))
                      {{ currency() }} {{ number_format((float) $val, 0) }}
                    @elseif (in_array($col, ['litres', 'last_balance', 'net_movement', 'total', 'ordered', 'delivered']))
                      {{ number_format((float) $val, 0) }} L
                    @elseif ($val instanceof \BackedEnum)
                      {{ $val->value }}
                    @else
                      {{ $val }}
                    @endif
                  </td>
                @endforeach
              </tr>
            @empty
              <tr><td colspan="{{ count($columns) }}"><div class="empty-state">No data for the selected period.</div></td></tr>
            @endforelse
          </tbody>
          @if ($totals)
            <tfoot>
              <tr>
                @foreach ($columns as $col)
                  @php($tv = data_get($totals, $col))
                  <td class="{{ in_array($col, ['litres', 'revenue', 'count', 'last_balance', 'net_movement', 'opening', 'actual', 'variance', 'total', 'transactions', 'total_spent']) ? 'numeric bold' : 'bold' }}">
                    @if (in_array($col, ['revenue', 'opening', 'actual', 'variance', 'total_spent']))
                      {{ currency() }} {{ number_format((float) $tv, 0) }}
                    @elseif (in_array($col, ['litres', 'last_balance', 'net_movement', 'total']))
                      {{ number_format((float) $tv, 0) }} L
                    @else
                      {{ $tv }}
                    @endif
                  </td>
                @endforeach
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>
@endsection