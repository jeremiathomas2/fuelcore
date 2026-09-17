<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3><i data-lucide="truck"></i> Fleet Accounts ({{ $items['total'] }})</h3>
  </div>
  <div class="card-body flush">
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Account No</th><th>Company</th><th>Contact</th><th>Credit Limit</th><th>Status</th></tr></thead>
        <tbody>
          @foreach ($items['rows'] as $f)
            <tr onclick="window.location='{{ route('fleet.show', $f) }}'">
              <td class="mono">{{ $f->account_number }}</td>
              <td class="bold">{{ $f->company_name }}</td>
              <td>{{ $f->contact_person ?? '—' }} <span class="muted small">· {{ $f->phone ?? '' }}</span></td>
              <td class="numeric bold">{{ currency() }} {{ number_format((float) $f->credit_limit, 0) }}</td>
              <td><span class="status-pill-sm {{ $f->status === 'active' ? 'online' : 'warning' }}">{{ $f->status }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>