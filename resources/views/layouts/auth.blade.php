<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Sign in') · FUELCORE</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800;6..12,900&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="logo-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L12 22"/>
            <path d="M8 6L16 6"/>
            <path d="M8 18L16 18"/>
            <path d="M6 10L18 10"/>
            <path d="M6 14L18 14"/>
            <circle cx="12" cy="12" r="2" fill="currentColor" stroke="none"/>
          </svg>
        </div>
        <div class="auth-brand">
          FUELCORE
          <small>Fuel Station Management System</small>
        </div>
      </div>

      @include('partials.flash')

      @yield('content')

      <div class="auth-foot">
        &copy; {{ now()->year }} FUELCORE&nbsp;&middot;&nbsp;Powered by Laravel
      </div>
    </div>
  </div>
</body>
</html>