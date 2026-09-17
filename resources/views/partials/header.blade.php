@php
    $title = $page_title ?? 'Dashboard';
    $breadcrumb = $page_breadcrumb ?? 'FUELCORE';
@endphp
<header class="top-header">
  <div class="header-left">
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
      <i data-lucide="menu" style="width:22px;height:22px;"></i>
    </button>
    <div class="page-title-block">
      <div class="title">{{ $title }}</div>
      <div class="breadcrumb">{{ $breadcrumb }}</div>
    </div>
  </div>

  <div class="header-right">
    {{-- Station selector --}}
    @if (Route::current()->getActionMethod() === 'index' && in_array(Route::current()->getName() ?? '', ['dashboard','pos']))
      @php
          $currentStation = request()->session()->get('active_station');
          $allStations = \App\Models\Station::query()->visibleTo(auth()->user())->orderBy('code')->get();
      @endphp
      <div class="header-selector">
        <i data-lucide="building-2"></i>
        <span>{{ $currentStation ? $allStations->where('id', $currentStation)->first()?->name ?? 'Station' : 'All Stations' }}</span>
        <i data-lucide="chevron-down" style="width:12px;height:12px;"></i>
      </div>
    @endif

    <div class="header-selector">
      <i data-lucide="calendar"></i>
      <span>{{ now()->format('d M Y') }}</span>
    </div>

    <div class="current-time" id="currentTime">12:00:00</div>

    <div class="header-divider"></div>

    <div class="status-pill">
      <span class="dot"></span>
      System Online
    </div>

    <div class="header-divider"></div>

    <button class="header-icon-btn" id="rightPanelToggle" aria-label="Toggle side panel" title="Show / hide side panel">
      <i data-lucide="chevrons-right" style="width:18px;height:18px;"></i>
    </button>

    <button class="header-icon-btn" id="searchBtn" aria-label="Search">
      <i data-lucide="search" style="width:18px;height:18px;"></i>
    </button>

    {{-- Notifications --}}
    @php
        $userNotif = auth()->user();
        $unreadCount = $userNotif->unreadNotifications()->count();
        $recentNotifs = $userNotif->unreadNotifications()->latest()->limit(6)->get();
    @endphp
    <div style="position:relative;">
      <button class="header-icon-btn" id="notificationBtn" aria-label="Notifications">
        <i data-lucide="bell" style="width:18px;height:18px;"></i>
        @if ($unreadCount > 0)
          <span class="badge">{{ $unreadCount }}</span>
        @endif
      </button>
      <div class="notification-panel" id="notificationPanel">
        <div class="notification-panel-header">
          Notifications
          <form method="POST" action="{{ route('notifications.readAll') }}" style="display:inline;">@csrf<button type="submit" style="background:none;border:none;color:var(--accent-blue);cursor:pointer;font-size:0.65rem;font-weight:700;">Mark all read</button></form>
        </div>
        @forelse ($recentNotifs as $notif)
          @php $d = json_decode($notif->data, true); @endphp
          <div class="notification-item">
            <div class="alert-icon {{ ($d['severity'] ?? 'info') === 'critical' ? 'critical' : (($d['severity'] ?? 'info') === 'warning' ? 'warning' : 'info') }}" style="width:28px;height:28px;">
              <i data-lucide="{{ ($d['severity'] ?? 'info') === 'critical' ? 'alert-triangle' : 'check-circle' }}" style="width:13px;height:13px;"></i>
            </div>
            <div>
              <div style="font-size:0.74rem;font-weight:700;color:var(--text-dark);">{{ $d['title'] ?? 'Notification' }}</div>
              <div style="font-size:0.65rem;color:var(--text-muted);">{{ $d['message'] ?? '' }} · {{ $notif->created_at->diffForHumans() }}</div>
            </div>
          </div>
        @empty
          <div style="padding:18px;text-align:center;font-size:0.72rem;color:var(--text-muted);">No new notifications</div>
        @endforelse
        <div style="border-top:1px solid var(--border-light);text-align:center;padding:10px;">
          <a href="{{ route('notifications.index') }}" style="font-size:0.68rem;color:var(--accent-blue);font-weight:700;text-decoration:none;">View all</a>
        </div>
      </div>
    </div>

    <button class="header-icon-btn" aria-label="Help" style="opacity:0.45;">
      <i data-lucide="help-circle" style="width:18px;height:18px;"></i>
    </button>

    <div class="header-divider"></div>

    <div class="avatar" style="width:34px;height:34px;font-size:0.7rem;cursor:pointer;">
      {{ $userNotif->avatar_text }}
      <span class="online-dot"></span>
    </div>
  </div>
</header>