<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="users"></i> Customers ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Email</th><th>Type</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $c)
            <tr onclick="window.location='{{ route('customers.show', $c) }}'">
              <td class="mono">{{ $c->customer_number }}</td>
              <td class="bold">{{ $c->name }}</td>
              <td>{{ $c->phone }}</td>
              <td class="muted">{{ $c->email }}</td>
              <td><span class="badge badge-info">{{ ucfirst($c->type) }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>