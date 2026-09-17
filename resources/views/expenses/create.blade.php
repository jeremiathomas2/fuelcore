@extends('layouts.app')

@section('active', 'expenses')
@section('page_title', 'Record Expense')
@section('page_breadcrumb', 'FUELCORE / Expenses / Record')

@section('content')
  <div class="page-head">
    <div><h1>Record Expense</h1></div>
    <div class="page-actions"><a href="{{ route('expenses.index') }}" class="btn"><i data-lucide="arrow-left"></i> Back</a></div>
  </div>

  @include('partials.flash')

  <form method="POST" action="{{ route('expenses.store') }}" class="card" style="max-width:680px;">
    @csrf
    <div class="card-header"><h3>Expense Details</h3></div>
    <div class="card-body">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Station *</label>
          <select class="form-control" name="station_id" required>
            <option value="">Select station</option>
            @foreach ($stations as $st)
              <option value="{{ $st->id }}" {{ old('station_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
          </select>
          @error('station_id')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-control" name="category_id">
            <option value="">Select category or create below</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">New Category</label>
          <input class="form-control" type="text" name="new_category" value="{{ old('new_category') }}" placeholder="e.g. Utilities, Maintenance…">
        </div>
        <div class="form-group">
          <label class="form-label">Amount ({{ currency() }}) *</label>
          <input class="form-control" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
          @error('amount')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Description *</label>
          <input class="form-control" type="text" name="description" value="{{ old('description') }}" required>
          @error('description')<div class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Expense Date *</label>
          <input class="form-control" type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Method *</label>
          <select class="form-control" name="payment_method" required>
            @foreach (['cash', 'mobile_money', 'card', 'bank_transfer', 'credit'] as $m)
              <option value="{{ $m }}" {{ old('payment_method', 'cash') === $m ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Receipt Reference</label>
          <input class="form-control" type="text" name="receipt_ref" value="{{ old('receipt_ref') }}" placeholder="Receipt / invoice no">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Expense</button>
      <a href="{{ route('expenses.index') }}" class="btn">Cancel</a>
    </div>
  </form>
@endsection