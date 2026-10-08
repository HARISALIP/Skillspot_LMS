@extends('layouts.admin')
@section('title', 'Roles & Permissions — Skillspot.in Admin')
@section('page-title', 'Roles & Permissions')
@section('page-sub', 'Manage roles and what each role can access')

@section('admin-content')

@php
  use App\Http\Controllers\Admin\RolesController;
  $systemRoles = RolesController::SYSTEM_ROLES;
@endphp

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  <span class="text-xl">✅</span> {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600 text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  <span class="text-xl">❌</span> {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600 text-xl">×</button>
</div>
@endif

<!-- Actions bar -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div class="flex flex-wrap gap-2">
    <a href="{{ route('admin.roles.create') }}"
       class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition shadow-sm">
      ➕ Create Role
    </a>
    <form method="POST" action="{{ route('admin.roles.seed') }}">
      @csrf
      <button type="submit"
              onclick="return confirm('Seed all permissions and assign defaults to super-admin & admin?')"
              class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition shadow-sm">
        🌱 Seed Permissions
      </button>
    </form>
  </div>
  <div class="text-xs text-gray-400 bg-gray-100 px-3 py-2 rounded-xl">
    🔒 System roles: <strong>{{ implode(', ', $systemRoles) }}</strong> — cannot be deleted
  </div>
</div>

<!-- Roles grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
  @forelse($roles as $role)
  @php
    $isSystem  = in_array($role->name, $systemRoles);
    $rolePerms = $role->permissions->pluck('name');
    $colors    = [
      'super-admin' => ['ring'=>'ring-red-200',   'bg'=>'from-red-500 to-rose-600',     'badge'=>'bg-red-100 text-red-700'],
      'admin'       => ['ring'=>'ring-orange-200', 'bg'=>'from-orange-500 to-amber-500', 'badge'=>'bg-orange-100 text-orange-700'],
      'student'     => ['ring'=>'ring-blue-200',   'bg'=>'from-brand-500 to-blue-600',   'badge'=>'bg-blue-100 text-blue-700'],
    ];
    $color = $colors[$role->name] ?? ['ring'=>'ring-purple-200','bg'=>'from-purple-500 to-accent-600','badge'=>'bg-purple-100 text-purple-700'];
  @endphp
  <div class="bg-white rounded-2xl border-2 {{ $color['ring'] }} shadow-sm overflow-hidden">

    <!-- Role header -->
    <div class="bg-gradient-to-r {{ $color['bg'] }} px-5 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white font-black text-lg">
          {{ strtoupper(substr($role->name,0,1)) }}
        </div>
        <div>
          <div class="text-white font-black capitalize">{{ str_replace('-',' ',$role->name) }}</div>
          <div class="text-white/70 text-xs">{{ $role->users_count }} {{ Str::plural('user',$role->users_count) }}</div>
        </div>
      </div>
      <div class="flex items-center gap-1.5">
        @if($isSystem)
        <span class="text-xs bg-white/20 text-white px-2 py-1 rounded-lg font-semibold">System</span>
        @endif
      </div>
    </div>

    <!-- Permissions -->
    <div class="p-5">
      @if($rolePerms->count())
      <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">
        {{ $rolePerms->count() }} Permission{{ $rolePerms->count() > 1 ? 's' : '' }}
      </div>
      <div class="flex flex-wrap gap-1.5 mb-4 max-h-28 overflow-y-auto">
        @foreach($rolePerms->sort() as $perm)
        <span class="text-xs {{ $color['badge'] }} px-2 py-0.5 rounded-lg font-medium">{{ $perm }}</span>
        @endforeach
      </div>
      @else
      <div class="text-sm text-gray-400 italic mb-4">No permissions assigned</div>
      @endif

      <!-- Actions -->
      <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
        <a href="{{ route('admin.roles.edit', $role->id) }}"
           class="flex-1 text-center py-2 rounded-xl bg-gray-100 hover:bg-brand-50 hover:text-brand-700 text-gray-700 text-xs font-bold transition">
          ✏️ Edit Permissions
        </a>
        @if(!$isSystem)
        <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}"
              onsubmit="return confirm('Delete role &quot;{{ $role->name }}&quot;?')">
          @csrf @method('DELETE')
          <button class="py-2 px-3 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold transition">🗑️</button>
        </form>
        @endif
      </div>
    </div>
  </div>
  @empty
  <div class="col-span-3 text-center py-14 text-gray-400">No roles found</div>
  @endforelse
</div>

@endsection
