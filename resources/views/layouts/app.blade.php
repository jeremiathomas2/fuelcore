@php
  $sectionActive = trim((string) $__env->yieldContent('active', ''));
  $sectionTitle = trim((string) $__env->yieldContent('page_title', ''));
  $sectionBreadcrumb = trim((string) $__env->yieldContent('page_breadcrumb', ''));
  $active = $sectionActive !== '' ? $sectionActive : ($active ?? '');
  $page_title = $sectionTitle !== '' ? $sectionTitle : ($page_title ?? 'Dashboard');
  $page_breadcrumb = $sectionBreadcrumb !== '' ? $sectionBreadcrumb : ($page_breadcrumb ?? 'FUELCORE');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'FUELCORE') · Fuel Station Management System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800;6..12,900&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
  @stack('head')
</head>
<body data-currency="{{ currency() }}" data-active-section="{{ $active }}">
  <div class="app">
    @include('partials.sidebar')
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ========== MAIN AREA ========== -->
    <div class="main-area" id="mainArea">
      @include('partials.header')

      <!-- CONTENT WRAPPER -->
      <div class="content-wrapper">
        <main class="main-content">
          @include('partials.flash')
          @yield('content')
        </main>

        @include('partials.right-panel')
      </div>

      <footer class="footer">
        <div class="footer-left">
          FUELCORE · Fuel Station Management System
        </div>
        <div class="footer-right">
          <div class="status-pill">
            <span class="dot"></span>
            System Online
          </div>
          <span class="muted small">&copy; {{ now()->year }} FUELCORE</span>
        </div>
      </footer>
    </div>
  </div>

  <!-- TOASTS -->
  <div class="toast-container" id="toastContainer">
    @if (session('success'))
      <div class="toast success">
        <i data-lucide="circle-check" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>{{ session('success') }}</span>
      </div>
    @endif
    @if (session('error'))
      <div class="toast error">
        <i data-lucide="triangle-alert" style="width:16px;height:16px;flex-shrink:0;"></i>
        <span>{{ session('error') }}</span>
      </div>
    @endif
  </div>

  <!-- SEARCH OVERLAY -->
  <div class="search-overlay" id="searchOverlay">
    <form class="search-box" action="{{ route('search') }}" method="GET">
      <div class="search-input-wrapper">
        <i data-lucide="search"></i>
        <input type="text" id="searchInput" name="q" placeholder="Search stations, sales, customers, vehicles…  (Ctrl+K)" autocomplete="off">
        <button type="button" class="modal-close" id="searchClose" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
    </form>
  </div>

  @stack('modals')

  <!-- CONFIRM MODAL -->
  <div class="modal-overlay" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
    <div class="modal">
      <div class="modal-header">
        <h3 id="confirmModalTitle">Are you sure?</h3>
        <button type="button" class="modal-close" data-confirm-cancel aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="modal-body">
        <p class="confirm-message" style="color:var(--text-dark);margin:0;font-size:0.85rem;line-height:1.6;"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm" data-confirm-cancel>Cancel</button>
        <button type="button" class="btn btn-sm btn-primary" data-confirm-ok>
          <i data-lucide="check" style="width:14px;height:14px;"></i><span>Confirm</span>
        </button>
      </div>
    </div>
  </div>

  @stack('scripts')
</body>
</html>