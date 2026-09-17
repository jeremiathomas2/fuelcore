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
    {{-- Station switcher --}}
    @if (Route::current() && Route::current()->getActionMethod() === 'index' && in_array(Route::current()->getName() ?? '', ['dashboard','pos']))
      @php
          $switchStations = \App\Models\Station::query()->visibleTo(auth()->user())->orderBy('code')->get();
          $activeStationId = (int) request()->session()->get('active_station');
          $activeStation = $activeStationId ? $switchStations->firstWhere('id', $activeStationId) : null;
          $switchRedirect = request()->fullUrlWithoutQuery(['station']);
      @endphp
      @if ($switchStations->isNotEmpty())
        <div class="station-switch">
          <button type="button" class="header-selector" id="stationSwitchBtn" aria-haspopup="true" aria-expanded="false">
            <i data-lucide="building-2"></i>
            <span>{{ $activeStation?->name ?? 'All Stations' }}</span>
            <i data-lucide="chevron-down" style="width:12px;height:12px;"></i>
          </button>
          <div class="station-menu" id="stationMenu">
            <form method="POST" action="{{ route('stations.switch') }}">
              @csrf
              <input type="hidden" name="redirect" value="{{ $switchRedirect }}">
              <button type="submit" class="station-option {{ $activeStation ? '' : 'active' }}">
                <i data-lucide="layout-grid" style="width:14px;height:14px;"></i>
                <span class="station-option-name">All Stations</span>
              </button>
            </form>
            @foreach ($switchStations as $station)
              <form method="POST" action="{{ route('stations.switch') }}">
                @csrf
                <input type="hidden" name="station_id" value="{{ $station->id }}">
                <input type="hidden" name="redirect" value="{{ $switchRedirect }}">
                <button type="submit" class="station-option {{ $activeStation?->id === $station->id ? 'active' : '' }}">
                  <span class="station-status-dot {{ $station->status }}"></span>
                  <span class="station-option-name">{{ $station->name }}</span>
                  <span class="station-option-code">{{ $station->code }}</span>
                </button>
              </form>
            @endforeach
          </div>
        </div>
      @endif
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
      <button class="header-icon-btn" id="notificationBtn" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
        <i data-lucide="bell" style="width:18px;height:18px;"></i>
        <span class="badge" id="notifBadge" @if ($unreadCount === 0) style="display:none" @endif>{{ $unreadCount }}</span>
      </button>
      <div class="notification-panel" id="notificationPanel"
           data-feed-url="{{ route('notifications.feed') }}"
           data-read-url-template="{{ route('notifications.read', ['notification' => '__ID__']) }}"
           data-read-all-url="{{ route('notifications.readAll') }}">
        <div class="notification-panel-header">
          Notifications
          <form method="POST" action="{{ route('notifications.readAll') }}" id="notifMarkAllForm" style="display:inline;">@csrf<button type="submit" style="background:none;border:none;color:var(--accent-blue);cursor:pointer;font-size:0.65rem;font-weight:700;">Mark all read</button></form>
        </div>
        <div id="notificationList">
          @forelse ($recentNotifs as $notif)
            @php $d = $notif->data; @endphp
            <div class="notification-item" data-notif-id="{{ $notif->id }}" role="button" tabindex="0">
              <div class="alert-icon {{ ($d['severity'] ?? 'info') === 'critical' ? 'critical' : (($d['severity'] ?? 'info') === 'warning' ? 'warning' : 'info') }}" style="width:28px;height:28px;">
                <i data-lucide="{{ ($d['severity'] ?? 'info') === 'critical' ? 'triangle-alert' : 'circle-check' }}" style="width:13px;height:13px;"></i>
              </div>
              <div>
                <div style="font-size:0.74rem;font-weight:700;color:var(--text-dark);">{{ $d['title'] ?? 'Notification' }}</div>
                <div style="font-size:0.65rem;color:var(--text-muted);">{{ $d['message'] ?? '' }} · {{ $notif->created_at->diffForHumans() }}</div>
              </div>
            </div>
          @empty
            <div class="notification-empty" style="padding:18px;text-align:center;font-size:0.72rem;color:var(--text-muted);">No new notifications</div>
          @endforelse
        </div>
        <div style="border-top:1px solid var(--border-light);text-align:center;padding:10px;">
          <a href="{{ route('notifications.index') }}" style="font-size:0.68rem;color:var(--accent-blue);font-weight:700;text-decoration:none;">View all</a>
        </div>
      </div>
    </div>

    <button class="header-icon-btn" aria-label="Help" style="opacity:0.45;">
      <i data-lucide="circle-help" style="width:18px;height:18px;"></i>
    </button>

    <div class="header-divider"></div>

    <div class="profile-wrap">
      <button class="avatar" id="profileBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Account menu" style="width:34px;height:34px;font-size:0.7rem;">
        {{ $userNotif->avatar_text }}
        <span class="online-dot"></span>
      </button>
      <div class="profile-menu" id="profileMenu">
        <div class="profile-menu-head">
          <div class="avatar" style="width:38px;height:38px;font-size:0.75rem;">{{ $userNotif->avatar_text }}</div>
          <div style="min-width:0;">
            <div class="profile-menu-name">{{ $userNotif->name }}</div>
            <div class="profile-menu-role">{{ $userNotif->roleLabel() }}</div>
          </div>
        </div>
        @if ($userNotif->can('user.view'))
          <a href="{{ route('users.show', $userNotif) }}" class="profile-menu-item"><i data-lucide="user"></i> My profile</a>
        @endif
        <a href="{{ route('notifications.index') }}" class="profile-menu-item"><i data-lucide="bell"></i> Notifications</a>
        @if ($userNotif->can('settings.manage'))
          <a href="{{ route('settings.index') }}" class="profile-menu-item"><i data-lucide="settings"></i> Settings</a>
        @endif
        <form method="POST" action="{{ route('logout') }}" data-turbo="false" data-confirm="Sign out of FUELCORE?" data-confirm-title="Sign out" data-confirm-ok="Sign out" data-confirm-icon="log-out">
          @csrf
          <button type="submit" class="profile-menu-item danger"><i data-lucide="log-out"></i> Sign out</button>
        </form>
      </div>
    </div>
  </div>
</header>