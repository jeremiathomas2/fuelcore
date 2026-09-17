@extends('layouts.app')

@section('active', 'payments')
@section('page_title', 'Record Payment')
@section('page_breadcrumb', 'FUELCORE / Payments / Record')

@section('content')
  <div class="page-head">
    <div><h1>Record Customer Payment</h1><p>Record an outstanding balance payment.</p></div>
    <div class="page-actions"><a href="{{ route('payments.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('payments.store') }}" class="card" style="max-width:640px;">
    @csrf
    <div class="card-header"><h3>Payment Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Customer *</label>
          <select class="form-control" name="customer_id" id="customerSelect" required>
            <option value="">Select customer with outstanding balance</option>
            @foreach ($customers as $c)
              <option value="{{ $c->id }}" data-balance="{{ number_format((float) $c->outstanding_balance, 2) }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} — outstanding: {{ currency() }} {{ number_format((float) $c->outstanding_balance, 0) }}</option>
            @endforeach
          </select>
          @error('customer_id')<div class="field-error">{{ $message }}</div>@enderror
          <div id="balanceInfo" class="muted small" style="margin-top:4px;"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Amount ({{ currency() }}) *</label>
          <input class="form-control" type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required>
          @error('amount')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Method *</label>
          <select class="form-control" name="method" required>
            @foreach (['cash', 'mobile_money', 'card', 'bank_transfer'] as $m)
              <option value="{{ $m }}" {{ old('method') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
            @endforeach
          </select>
          @error('method')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Reference</label>
          <input class="form-control" type="text" name="reference" value="{{ old('reference') }}" placeholder="M-Pesa ref / card last 4…">
        </div>
        <div class="form-group">
          <label class="form-label">Provider</label>
          <input class="form-control" type="text" name="provider" value="{{ old('provider') }}" placeholder="Vodacom M-Pesa / NMB…">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Record Payment</button>
      <a href="{{ route('payments.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const sel = document.getElementById('customerSelect');
      const info = document.getElementById('balanceInfo');
      const amtInput = document.querySelector('input[name="amount"]');
      const showBalance = () => {
        const opt = sel.selectedOptions[0];
        if (opt && opt.dataset.balance) {
          info.textContent = 'Outstanding balance: {{ currency() }} ' + opt.dataset.balance;
          amtInput.max = parseFloat(opt.dataset.balance.replace(/,/g, ''));
        } else {
          info.textContent = '';
          amtInput.removeAttribute('max');
        }
      };
      sel.addEventListener('change', showBalance);
      showBalance();
    });
  </script>
@endpush