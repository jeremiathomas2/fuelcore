@if (session('success'))
    <div class="alert-box alert-success">
        <i data-lucide="circle-check"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="alert-box alert-error">
        <i data-lucide="triangle-alert"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if (session('status'))
    <div class="alert-box alert-info">
        <i data-lucide="info"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any() && isset($errors) && $errors->isNotEmpty() && ! $errors->isEmpty())
    @php
        $visible = collect($errors->all())->filter(fn ($e) => ! str_contains($e, 'These credentials do not match'));
    @endphp
    @if ($visible->isNotEmpty())
        <div class="alert-box alert-error">
            <i data-lucide="triangle-alert"></i>
            <div>
                <strong>Please fix the following:</strong>
                <ul style="margin:6px 0 0 16px;padding:0;">
                    @foreach ($visible->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endif