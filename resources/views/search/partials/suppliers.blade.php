<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="warehouse"></i> Suppliers ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Code</th><th>Contact</th><th>Phone</th><th>Status</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $s)
            <tr>
              <td class="bold">{{ $s->name }}</td>
              <td class="mono">{{ $s->code }}</td>
              <td>{{ $s->contact_person ?? '—' }}</td>
              <td>{{ $s->phone ?? '—' }}</td>
              <td><span class="status-pill-sm {{ $s->status === 'active' ? 'online' : 'warning' }}">{{ ucfirst($s->status) }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>