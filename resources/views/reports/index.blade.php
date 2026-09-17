@extends('layouts.app')

@section('active', 'reports')
@section('page_title', 'Reports')
@section('page_breadcrumb', 'FUELCORE / Reports')

@section('content')
  <div class="page-head">
    <div><h1>Reports</h1><p>Select a report to view and export.</p></div>
  </div>

  <div class="kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
    @foreach ($registry as $slug => $meta)
      <a href="{{ route('reports.show', $slug) }}" class="kpi-card" style="text-decoration:none;cursor:pointer;">
        <div class="kpi-header">
          <div class="kpi-icon blue">
            <i data-lucide="{{ in_array($slug, ['inventory', 'reconciliation']) ? 'package' : (str_contains($slug, 'expense') ? 'wallet' : (str_contains($slug, 'customer') ? 'users' : (str_contains($slug, 'shift') ? 'clock' : (str_contains($slug, 'delivery') ? 'truck' : 'bar-chart-2')))) }}"></i>
          </div>
        </div>
        <div class="kpi-value" style="font-size:1.1rem;">{{ $meta['title'] }}</div>
        <div class="kpi-label">{{ $meta['description'] }}</div>
      </a>
    @endforeach
  </div>
@endsection