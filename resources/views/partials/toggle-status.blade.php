@php
  $isActive = (bool) ($active ?? true);
  $noun = $noun ?? 'record';
  $label = $label ?? $noun;
@endphp
<form method="POST" action="{{ $action }}" class="d-inline" onclick="event.stopPropagation();" data-turbo="false"
      data-confirm="{{ $isActive ? 'Archive' : 'Activate' }} {{ $label }}?"
      data-confirm-title="{{ $isActive ? 'Archive' : 'Activate' }} {{ ucfirst($noun) }}"
      data-confirm-ok="{{ $isActive ? 'Archive' : 'Activate' }}"
      data-confirm-icon="{{ $isActive ? 'archive' : 'rotate-ccw' }}">
  @csrf
  @method('PATCH')
  <button type="submit" class="icon-btn" title="{{ $isActive ? 'Archive' : 'Activate' }}">
    <i data-lucide="{{ $isActive ? 'archive' : 'archive-restore' }}"></i>
  </button>
</form>
