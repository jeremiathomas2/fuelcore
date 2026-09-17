<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="building-2"></i> Stations ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Code</th><th>Name</th><th>Location</th><th>Status</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $s)
            <tr onclick="window.location='{{ route('stations.show', $s) }}'">
              <td class="mono">{{ $s->code }}</td>
              <td class="bold">{{ $s->name }}</td>
              <td>{{ $s->location }} <span class="muted small">· {{ $s->region }}</span></td>
              <td><span class="status-pill-sm {{ $s->status === 'online' ? 'online' : (in_array($s->status, ['warning', 'offline']) ? 'warning' : 'neutral') }}">{{ ucfirst($s->status) }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>