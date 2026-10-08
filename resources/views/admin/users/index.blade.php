@extends('layouts.admin')
@section('title', 'Users — Skillspot.in Admin')
@section('page-title', 'Users')
@section('page-sub', 'Manage all registered students and admins')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <span class="text-xl flex-shrink-0">✅</span> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600 text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold shadow-sm">
  <span class="text-xl flex-shrink-0">❌</span> {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600 text-xl">×</button>
</div>
@endif

<!-- Stats row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  @php $uStats = [
    ['label'=>'Total Users',   'value'=>$totalUsers,   'icon'=>'👥','bg'=>'bg-blue-50',  'text'=>'text-blue-700'],
    ['label'=>'Students',      'value'=>$totalStudents,'icon'=>'🎓','bg'=>'bg-green-50', 'text'=>'text-green-700'],
    ['label'=>'Admins',        'value'=>$totalAdmins,  'icon'=>'🛡️','bg'=>'bg-red-50',   'text'=>'text-red-700'],
    ['label'=>'This Month',    'value'=>$thisMonth,    'icon'=>'📅','bg'=>'bg-purple-50','text'=>'text-purple-700'],
  ]; @endphp
  @foreach($uStats as $s)
  <div class="{{ $s['bg'] }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $s['icon'] }}</div>
    <div class="text-2xl font-black {{ $s['text'] }}">{{ $s['value'] }}</div>
    <div class="text-xs font-semibold text-gray-600">{{ $s['label'] }}</div>
  </div>
  @endforeach
</div>

<!-- Filters + Actions bar -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
    <h3 class="font-black text-gray-900 text-sm">All Users</h3>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('admin.users.create') }}"
         class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-sm">
        ➕ Add User
      </a>
    </div>
  </div>

  <!-- Search & filter -->
  <form method="GET" action="{{ route('admin.users') }}" class="flex flex-col sm:flex-row gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
    <div class="relative flex-1">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
      <input type="text" name="search" value="{{ request('search') }}"
             placeholder="Search name, email or phone…"
             class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
    </div>
    <select name="role" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white cursor-pointer">
      <option value="">All Roles</option>
      <option value="student"    {{ request('role') === 'student'     ? 'selected' : '' }}>Students</option>
      <option value="admin"      {{ request('role') === 'admin'       ? 'selected' : '' }}>Admins</option>
      <option value="super-admin"{{ request('role') === 'super-admin' ? 'selected' : '' }}>Super Admin</option>
      <option value="vendor"      {{ request('role') === 'vendor'       ? 'selected' : '' }}>Vendors</option>
      <option value="teacher"     {{ request('role') === 'teacher'      ? 'selected' : '' }}>Teachers</option>
    </select>
    <select name="country" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white cursor-pointer">
      <option value="">All Countries</option>
      @foreach($countries as $code => $cname)
      <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>{{ $cname }}</option>
      @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition">Search</button>
    @if(request()->hasAny(['search','role','country']))
    <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition">Clear</a>
    @endif
  </form>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">User</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Phone</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Country</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Role</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Joined</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden xl:table-cell">Source</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($users as $user)
        <tr class="hover:bg-gray-50 transition group">
          <!-- User -->
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-gradient-to-br from-brand-400 to-accent-400 rounded-xl flex items-center justify-center text-white font-black text-sm flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-gray-900 text-sm truncate">{{ $user->name }}</div>
                <div class="text-gray-400 text-xs truncate">{{ $user->email }}</div>
              </div>
            </div>
          </td>
          <!-- Phone -->
          <td class="px-5 py-3.5 text-gray-600 text-xs hidden sm:table-cell">
            {{ $user->phone ?? '—' }}
          </td>
          <!-- Country -->
          <td class="px-5 py-3.5 hidden md:table-cell">
            <span class="text-xs text-gray-600">{{ $user->country_name ?? $user->country }}</span>
          </td>
          <!-- Role -->
          <td class="px-5 py-3.5">
            @php $role = $user->roles->first()?->name ?? 'student'; @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold
              {{ $role === 'super-admin' ? 'bg-red-100 text-red-700'    :
                ($role === 'admin'       ? 'bg-orange-100 text-orange-700' :
                ($role === 'vendor'      ? 'bg-purple-100 text-purple-700' :
                ($role === 'teacher'     ? 'bg-indigo-100 text-indigo-700' :
                'bg-blue-100 text-blue-700'))) }}">
              {{ ucfirst($role) }}
            </span>
          </td>
          <!-- Joined -->
          <td class="px-5 py-3.5 text-gray-400 text-xs hidden lg:table-cell">
            {{ $user->created_at?->format('d M Y') }}
          </td>
          <!-- Source -->
          <td class="px-5 py-3.5 hidden xl:table-cell">
            @php
              $via = $user->registered_via ?? 'direct';
              $vendorName = null;
              if ($via === 'vendor_portal' && $user->registered_vendor_id) {
                $vendorName = \App\Models\Vendor::find($user->registered_vendor_id)?->brand_name;
              }
            @endphp
            @if($via === 'vendor_portal')
              <div>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">🏪 Vendor Portal</span>
                @if($vendorName)<div class="text-xs text-gray-400 mt-0.5 truncate max-w-28">{{ $vendorName }}</div>@endif
              </div>
            @elseif($via === 'admin')
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">👤 Admin Added</span>
            @else
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">🌐 Direct</span>
            @endif
            @php $access = $user->portal_access ?? 'Skillspot_only'; @endphp
          </td>
          <!-- Actions -->
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5">
              <a href="{{ route('admin.users.edit', $user->id) }}"
                 class="p-1.5 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition" title="Edit">
                ✏️
              </a>
              @if(auth()->user()->hasRole('super-admin') && !$user->hasRole('super-admin') && $user->id !== auth()->id())
              <form method="POST" action="{{ route('admin.users.impersonate', $user->id) }}" class="inline">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg hover:bg-purple-50 text-gray-400 hover:text-purple-600 transition" title="Login as this user">👤</button>
              </form>
              @endif
              @if($user->id !== auth()->id())
              <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                    onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Delete">🗑️</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="px-5 py-14 text-center">
            <div class="text-4xl mb-2">👥</div>
            <p class="text-gray-500 font-medium">No users found</p>
            <p class="text-gray-400 text-xs mt-1">
              @if(request()->hasAny(['search','role','country']))
                Try adjusting your filters
              @else
                Users will appear here once they register
              @endif
            </p>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($users->hasPages())
  <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
    {{ $users->withQueryString()->links('pagination::tailwind') }}
  </div>
  @else
  <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
    Showing {{ $users->count() }} of {{ $users->total() }} users
  </div>
  @endif
</div>

@endsection
