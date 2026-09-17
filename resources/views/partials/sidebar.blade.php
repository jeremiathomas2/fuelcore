@php
    $active = isset($active) ? $active : '';
    $icon = function (string $page, string $name) use ($active) {
        return $active === $page ? ' active' : '';
    };
    $user = auth()->user();
    $sidebarCollapsed = request()->cookie('fc_sidebar') === 'collapsed';
@endphp
<aside class="sidebar{{ $sidebarCollapsed ? ' collapsed' : '' }}" id="sidebar" data-turbo-permanent>
  <div class="sidebar-header">
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
    <div class="logo-text">
      <span class="brand">FUELCORE</span>
      <span class="sub">Fuel Station Management</span>
    </div>
    <button type="button" class="sidebar-collapse" id="sidebarToggle" aria-label="Collapse sidebar" aria-expanded="{{ $sidebarCollapsed ? 'false' : 'true' }}" title="{{ $sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar' }}">
      <i data-lucide="{{ $sidebarCollapsed ? 'chevrons-right' : 'chevrons-left' }}" style="width:16px;height:16px;"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Main</div>
      <a class="nav-item{{ $icon('dashboard', '') }}" data-nav-key="dashboard" href="{{ route('dashboard') }}">
        <i data-lucide="layout-dashboard"></i><span class="label">Dashboard</span>
      </a>
      <a class="nav-item{{ $icon('stations', '') }}" data-nav-key="stations" href="{{ route('stations.index') }}">
        <i data-lucide="building-2"></i><span class="label">Stations</span>
      </a>
      <a class="nav-item{{ $icon('pumps', '') }}" data-nav-key="pumps" href="{{ route('pumps.index') }}">
        <i data-lucide="fuel"></i><span class="label">Pumps & Nozzles</span>
      </a>
      <a class="nav-item{{ $icon('pos', '') }}" data-nav-key="pos" href="{{ route('pos') }}">
        <i data-lucide="trending-up"></i><span class="label">Fuel Sales (POS)</span>
      </a>
      <a class="nav-item{{ $icon('customers', '') }}" data-nav-key="customers" href="{{ route('customers.index') }}">
        <i data-lucide="users"></i><span class="label">Customers</span>
      </a>
      <a class="nav-item{{ $icon('fleet', '') }}" data-nav-key="fleet" href="{{ route('fleet.index') }}">
        <i data-lucide="truck"></i><span class="label">Fleet Accounts</span>
      </a>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Operations</div>
      <a class="nav-item{{ $icon('inventory', '') }}" data-nav-key="inventory" href="{{ route('inventory.index') }}">
        <i data-lucide="package"></i><span class="label">Fuel Inventory</span>
      </a>
      <a class="nav-item{{ $icon('deliveries', '') }}" data-nav-key="deliveries" href="{{ route('deliveries.index') }}">
        <i data-lucide="truck-icon"></i><span class="label">Deliveries</span>
      </a>
      <a class="nav-item{{ $icon('tanks', '') }}" data-nav-key="tanks" href="{{ route('tanks.index') }}">
        <i data-lucide="database"></i><span class="label">Tank Monitoring</span>
      </a>
      <a class="nav-item{{ $icon('shifts', '') }}" data-nav-key="shifts" href="{{ route('shifts.index') }}">
        <i data-lucide="clock"></i><span class="label">Shift Management</span>
      </a>
      <a class="nav-item{{ $icon('expenses', '') }}" data-nav-key="expenses" href="{{ route('expenses.index') }}">
        <i data-lucide="receipt"></i><span class="label">Expenses</span>
      </a>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Finance</div>
      <a class="nav-item{{ $icon('payments', '') }}" data-nav-key="payments" href="{{ route('payments.index') }}">
        <i data-lucide="credit-card"></i><span class="label">Payments</span>
      </a>
      <a class="nav-item{{ $icon('reconciliations', '') }}" data-nav-key="reconciliations" href="{{ route('reconciliations.index') }}">
        <i data-lucide="scale"></i><span class="label">Cash Reconciliation</span>
      </a>
      <a class="nav-item{{ $icon('reports', '') }}" data-nav-key="reports" href="{{ route('reports.index') }}">
        <i data-lucide="bar-chart-3"></i><span class="label">Financial Reports</span>
      </a>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Monitoring</div>
      <a class="nav-item{{ $icon('live', '') }}" data-nav-key="live" href="{{ route('live') }}">
        <i data-lucide="radio"></i><span class="label">Live Operations</span>
      </a>
      <a class="nav-item{{ $icon('alerts', '') }}" data-nav-key="alerts" href="{{ route('alerts.index') }}">
        <i data-lucide="bell"></i><span class="label">Alerts</span>
      </a>
      <a class="nav-item{{ $icon('audit', '') }}" data-nav-key="audit" href="{{ route('audit-logs.index') }}">
        <i data-lucide="file-clock"></i><span class="label">System Logs</span>
      </a>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Management</div>
      <a class="nav-item{{ $icon('users', '') }}" data-nav-key="users" href="{{ route('users.index') }}">
        <i data-lucide="user-check"></i><span class="label">Employees</span>
      </a>
      <a class="nav-item{{ $icon('suppliers', '') }}" data-nav-key="suppliers" href="{{ route('suppliers.index') }}">
        <i data-lucide="factory"></i><span class="label">Suppliers</span>
      </a>
      <a class="nav-item{{ $icon('products', '') }}" data-nav-key="products" href="{{ route('products.index') }}">
        <i data-lucide="droplets"></i><span class="label">Fuel Products</span>
      </a>
      <a class="nav-item{{ $icon('settings', '') }}" data-nav-key="settings" href="{{ route('settings.index') }}">
        <i data-lucide="settings"></i><span class="label">Settings</span>
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <a class="user-block" href="{{ $user->can('user.view') ? route('users.show', $user) : route('dashboard') }}" style="text-decoration:none;">
      <div class="avatar">
        {{ $user->avatar_text }}
        <span class="online-dot"></span>
      </div>
      <div class="user-info">
        <div class="name" style="color:#E2E8F0;">{{ $user->name }}</div>
        <div class="role">{{ $user->roleLabel() }}</div>
      </div>
    </a>
    <form method="POST" action="{{ route('logout') }}" class="d-flex" data-turbo="false" data-confirm="Sign out of FUELCORE?" data-confirm-title="Sign out" data-confirm-ok="Sign out" data-confirm-icon="log-out">
      @csrf
      <button class="action-btn" style="background:none;border:none;color:#7A9CB5;cursor:pointer;" title="Sign out">
        <i data-lucide="log-out" style="width:16px;height:16px;"></i>
      </button>
    </form>
  </div>
</aside>
