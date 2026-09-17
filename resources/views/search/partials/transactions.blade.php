<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="receipt"></i> Fuel Transactions ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Receipt</th><th>Station</th><th>Fuel</th><th class="numeric">Litres</th><th class="numeric">Amount</th><th>Time</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $t)
            <tr>
              <td class="mono">{{ $t->transaction_number }}</td>
              <td>{{ $t->station?->name }}</td>
              <td>{{ $t->fuelProduct?->name }}</td>
              <td class="numeric">{{ number_format((float) $t->litres, 2) }} L</td>
              <td class="numeric bold">{{ currency() }} {{ number_format((float) $t->net_amount, 0) }}</td>
              <td class="muted small">{{ $t->transacted_at->format('d M Y H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>