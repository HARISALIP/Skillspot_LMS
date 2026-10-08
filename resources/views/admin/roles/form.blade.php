@extends('layouts.admin')
@section('title', isset($role) ? 'Edit Role — Skillspot.in Admin' : 'Create Role — Skillspot.in Admin')
@section('page-title', isset($role) ? 'Edit Role' : 'Create Role')
@section('page-sub', isset($role) ? 'Update name and permissions for this role' : 'Define a new custom role with specific permissions')

@section('admin-content')

@php
  use App\Http\Controllers\Admin\RolesController;
  $isSystem  = isset($role) && in_array($role->name, RolesController::SYSTEM_ROLES);
  $rolePerms = $rolePerms ?? [];
@endphp

<div class="max-w-3xl">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 bg-gray-50/50">
      <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-accent-600 rounded-2xl flex items-center justify-center text-2xl">🎭</div>
      <div>
        <h3 class="font-black text-gray-900">{{ isset($role) ? 'Edit: '.str_replace('-',' ',ucfirst($role->name)) : 'New Custom Role' }}</h3>
        <p class="text-xs text-gray-400">
          @if($isSystem) System role — name locked, permissions editable
          @elseif(isset($role)) Custom role — name and permissions editable
          @else Define role name and assign permissions
          @endif
        </p>
      </div>
    </div>

    @if($errors->any())
    <div class="mx-6 mt-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
      @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
    @endif

    <form method="POST"
          action="{{ isset($role) ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}"
          id="roleForm">
      @csrf
      @isset($role) @method('PUT') @endisset

      <div class="p-6 space-y-6">

        <!-- Role name -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Role Name</label>
          @if($isSystem)
            <div class="flex items-center gap-3 px-4 py-3 bg-gray-100 rounded-xl border border-gray-200">
              <span class="text-lg">🔒</span>
              <span class="font-bold text-gray-700 capitalize">{{ str_replace('-',' ',$role->name) }}</span>
              <span class="text-xs text-gray-400 ml-auto">System role — name locked</span>
            </div>
            <input type="hidden" name="name" value="{{ $role->name }}">
          @else
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🎭</span>
            <input type="text" name="name"
                   value="{{ old('name', isset($role) ? $role->name : '') }}"
                   required placeholder="e.g. instructor, moderator, content-editor"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }} bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <p class="text-xs text-gray-400 mt-1">Lowercase, hyphens allowed. e.g. <code class="bg-gray-100 px-1 rounded">content-editor</code></p>
          @endif
        </div>

        <!-- Permissions -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Permissions</label>
            <div class="flex items-center gap-2">
              <button type="button" onclick="toggleAll(true)"
                      class="text-xs text-brand-600 hover:text-brand-700 font-semibold hover:underline transition">Select All</button>
              <span class="text-gray-300">|</span>
              <button type="button" onclick="toggleAll(false)"
                      class="text-xs text-gray-500 hover:text-gray-700 font-semibold hover:underline transition">Clear All</button>
            </div>
          </div>

          <div class="space-y-4">
            @foreach($permissionGroups as $group => $perms)
            <div class="border border-gray-200 rounded-2xl overflow-hidden">
              <!-- Group header -->
              <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200 cursor-pointer select-none"
                   onclick="toggleGroup('{{ Str::slug($group) }}')">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-gray-800 text-sm">{{ $group }}</span>
                  <span class="text-xs text-gray-400">{{ count($perms) }} permissions</span>
                </div>
                <div class="flex items-center gap-2">
                  <button type="button"
                          onclick="event.stopPropagation(); toggleGroupCheck('{{ Str::slug($group) }}')"
                          class="text-xs text-brand-600 font-semibold hover:underline">Toggle all</button>
                  <svg class="w-4 h-4 text-gray-400 group-toggle-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                </div>
              </div>
              <!-- Permissions -->
              <div id="group-{{ Str::slug($group) }}" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($perms as $perm)
                @php $hasIt = in_array($perm, $rolePerms); @endphp
                <label class="flex items-center gap-2.5 cursor-pointer group/perm">
                  <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                         {{ $hasIt ? 'checked' : '' }}
                         class="perm-cb group-cb-{{ Str::slug($group) }} w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                  <span class="text-sm text-gray-700 group-hover/perm:text-gray-900 transition">{{ $perm }}</span>
                </label>
                @endforeach
              </div>
            </div>
            @endforeach
          </div>

          <!-- Selected count -->
          <div class="mt-3 text-xs text-gray-500">
            <span id="selectedCount">{{ count($rolePerms) }}</span> permissions selected
          </div>
        </div>

      </div><!-- /p-6 -->

      <!-- Actions -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <a href="{{ route('admin.roles') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">← Back to Roles</a>
        <button type="submit"
                class="flex items-center gap-2 bg-gradient-to-r from-purple-600 to-accent-600 hover:from-purple-700 hover:to-accent-700 active:scale-[0.98] text-white font-black px-7 py-2.5 rounded-xl transition shadow-md text-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          {{ isset($role) ? 'Update Role' : 'Create Role' }}
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
  function toggleGroup(slug) {
    const el = document.getElementById('group-' + slug);
    el.classList.toggle('hidden');
  }

  function toggleGroupCheck(slug) {
    const boxes = document.querySelectorAll('.group-cb-' + slug);
    const anyUnchecked = [...boxes].some(b => !b.checked);
    boxes.forEach(b => b.checked = anyUnchecked);
    updateCount();
  }

  function toggleAll(state) {
    document.querySelectorAll('.perm-cb').forEach(b => b.checked = state);
    updateCount();
  }

  function updateCount() {
    const count = document.querySelectorAll('.perm-cb:checked').length;
    document.getElementById('selectedCount').textContent = count;
  }

  document.querySelectorAll('.perm-cb').forEach(b => {
    b.addEventListener('change', updateCount);
  });
</script>
@endpush
