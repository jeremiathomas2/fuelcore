<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="truck"></i> Deliveries ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Delivery No</th><th>Station</th><th>Supplier</th><th class="numeric">Litres</th><th>Status</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $d)
            <tr onclick="window.location='{{ route('deliveries.show', $d) }}'">
              <td class="mono">{{ $d->delivery_number }}</td>
              <td>{{ $d->station?->name }}</td>
              <td>{{ $d->supplier?->name }}</td>
              <td class="numeric bold">{{ number_format((float) $d->ordered_qty, 0) }} L</td>
              <td><span class="status-pill-sm {{ in_array($d->status, ['completed', 'reconciled']) ? 'online' : ($d->status === 'cancelled' ? 'warning' : 'neutral') }}">{{ ucfirst(str_replace('_', ' ', $d->status)) }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>