@extends('layouts.app')

@section('active', 'notifications')
@section('page_title', 'Notifications')
@section('page_breadcrumb', 'FUELCORE / Notifications')

@section('content')
  <div class="page-head">
    <div><h1>Notifications</h1><p>{{ $unread }} unread notification(s).</p></div>
    <div class="page-actions">
      @if ($unread > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}" style="display:inline;">
          @csrf
          <button class="btn" type="submit"><i data-lucide="check-check"></i> Mark all read</button>
        </form>
      @endif
    </div>
  </div>

  @include('partials.flash')

  <div class="card">
    <div class="card-body flush">
      @forelse ($notifications as $n)
        <div class="notification-row {{ $n->read_at ? '' : 'unread' }}">
          <div class="notification-icon"><i data-lucide="{{ $n->read_at ? 'bell' : 'bell-ring' }}"></i></div>
          <div class="notification-body">
            <div class="notification-title">{{ data_get($n->data, 'title', 'Notification') }}</div>
            <div class="notification-text">{{ data_get($n->data, 'message', '') }}</div>
            <div class="notification-time">{{ $n->created_at->diffForHumans() }}</div>
          </div>
          <div class="notification-action">
            @if (!$n->read_at)
              <form method="POST" action="{{ route('notifications.read', $n) }}" style="display:inline;">
                @csrf
                @method('PATCH')
                <button class="btn-sm" type="submit"><i data-lucide="check"></i> Mark read</button>
              </form>
            @endif
          </div>
        </div>
      @empty
        <div class="empty-state">No notifications.</div>
      @endforelse
      {{ $notifications->links('partials.pagination') }}
    </div>
  </div>
@endsection