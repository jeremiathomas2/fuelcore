@extends('layouts.app')

@section('active', 'users')
@section('page_title', 'Users')
@section('page_breadcrumb', 'FUELCORE / Users')

@section('content')
  <div class="page-head">
    <div><h1>Users &amp; Roles</h1><p>Manage system users and their access roles.</p></div>
    <div class="page-actions">
      @can('user.manage')
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Add User</a>
      @endcan
    </div>
  </div>

  @include('partials.flash')

  <section class="filter-bar">
    <form action="{{ route('users.index') }}" method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
      <input type="search" name="search" value="{{ request('search') }}" class="filter-chip" placeholder="Search name, email, employee no…" style="flex:1;min-width:220px;padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
      <select name="role" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Roles</option>
        @foreach ($roles as $role)
          <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
        @endforeach
      </select>
      <select name="status" class="filter-chip" style="padding:7px 12px;border-radius:8px;border:1px solid var(--border-light);font-size:0.75rem;">
        <option value="">All Status</option>
        @foreach (['active', 'suspended'] as $s)
          <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <button class="btn-sm primary" type="submit"><i data-lucide="funnel"></i> Filter</button>
      <a href="{{ route('users.index') }}" class="btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
    </form>
  </section>

  <div class="card">
    <div class="card-body flush">
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Station</th><th>Last Login</th><th>Status</th><th class="center">Actions</th></tr></thead>
          <tbody>
            @forelse ($users as $u)
              <tr onclick="window.location='{{ route('users.show', $u) }}'">
                <td>
                  <span class="row-avatar">{{ $u->avatar_text }}</span>
                  <strong>{{ $u->name }}</strong>
                  <span class="muted small">· {{ $u->employee_number }}</span>
                </td>
                <td class="muted small">{{ $u->email }}</td>
                <td><span class="badge badge-info">{{ $u->roleLabel() }}</span></td>
                <td class="muted small">{{ $u->station?->name ?? '—' }}</td>
                <td class="muted small">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                <td><span class="status-pill-sm {{ $u->status === 'active' ? 'online' : 'warning' }}">{{ $u->status }}</span></td>
                <td class="center">
                  <div class="action-links">
                    <a href="{{ route('users.show', $u) }}" class="icon-btn" title="View user" onclick="event.stopPropagation()"><i data-lucide="eye"></i></a>
                    @can('user.manage')
                      <a href="{{ route('users.edit', $u) }}" class="icon-btn" title="Edit user" onclick="event.stopPropagation()"><i data-lucide="pencil"></i></a>
                      <form method="POST" action="{{ route('users.toggleStatus', $u) }}" style="display:inline;" onclick="event.stopPropagation()">
                        @csrf
                        @method('PATCH')
                        <button class="icon-btn" type="submit" title="{{ $u->status === 'active' ? 'Suspend user' : 'Activate user' }}">
                          <i data-lucide="{{ $u->status === 'active' ? 'power' : 'power-off' }}"></i>
                        </button>
                      </form>
                      @if ($u->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $u) }}" style="display:inline;" onclick="event.stopPropagation()" onsubmit="return confirm('Delete {{ $u->name }}? This permanently removes the user and their station assignments.')">
                          @csrf
                          @method('DELETE')
                          <button class="icon-btn danger" type="submit" title="Delete user"><i data-lucide="trash-2"></i></button>
                        </form>
                      @endif
                    @endcan
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="7"><div class="empty-state">No users found.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $users->withQueryString()->links('partials.pagination') }}
    </div>
  </div>
@endsection