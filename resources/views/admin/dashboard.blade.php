@extends('layouts.admin')
@section('title', 'Admin Dashboard — Skillspot.in LMS')
@section('page-title', 'Dashboard')
@section('page-sub', 'Welcome back, ' . (auth()->user()->name ?? 'Admin'))

@section('admin-content')

<!-- Stats grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @php
  $stats = [
    ['label'=>'Total Users',    'value'=>$stats['users']       ?? 0, 'icon'=>'👥', 'color'=>'from-blue-500 to-brand-600',   'sub'=>'+12 this week'],
    ['label'=>'Admins',         'value'=>$stats['admins']      ?? 0, 'icon'=>'🛡️', 'color'=>'from-purple-500 to-accent-600','sub'=>'Platform managers'],
    ['label'=>'Total Courses',  'value'=>$stats['courses']     ?? 0, 'icon'=>'📚', 'color'=>'from-green-500 to-emerald-600','sub'=>$stats['published_courses'] ?? 0 . ' published'],
    ['label'=>'Revenue',        'value'=>'₹'.number_format($stats['revenue'] ?? 0), 'icon'=>'💰', 'color'=>'from-orange-500 to-amber-500','sub'=>'This month'],
  ];
  @endphp

  @foreach($stats as $s)
  <div class="bg-white rounded-2xl p-4 md:p-5 border border-gray-100 shadow-sm">
    <div class="flex items-start justify-between mb-3">
      <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br {{ $s['color'] }} rounded-xl flex items-center justify-center text-xl md:text-2xl shadow-sm flex-shrink-0">{{ $s['icon'] }}</div>
    </div>
    <div class="text-xl md:text-2xl font-black text-gray-900">{{ $s['value'] }}</div>
    <div class="text-xs md:text-sm font-semibold text-gray-700 mt-0.5">{{ $s['label'] }}</div>
    <div class="text-xs text-gray-400 mt-1">{{ $s['sub'] }}</div>
  </div>
  @endforeach
</div>

<!-- Second row stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  @php
  $stats2 = [
    ['label'=>'Enrollments',    'value'=>$stats['enrollments']  ?? 0, 'icon'=>'🎓', 'bg'=>'bg-blue-50',   'text'=>'text-blue-700'],
    ['label'=>'Certificates',   'value'=>$stats['certs']        ?? 0, 'icon'=>'📜', 'bg'=>'bg-green-50',  'text'=>'text-green-700'],
    ['label'=>'Active Licenses','value'=>$stats['licenses']     ?? 0, 'icon'=>'🔑', 'bg'=>'bg-purple-50', 'text'=>'text-purple-700'],
    ['label'=>'Open Reviews',   'value'=>$stats['reviews']      ?? 0, 'icon'=>'⭐', 'bg'=>'bg-yellow-50', 'text'=>'text-yellow-700'],
  ];
  @endphp
  @foreach($stats2 as $s)
  <div class="{{ $s['bg'] }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $s['icon'] }}</div>
    <div class="text-xl font-black {{ $s['text'] }}">{{ $s['value'] }}</div>
    <div class="text-xs font-semibold text-gray-600">{{ $s['label'] }}</div>
  </div>
  @endforeach
</div>

<!-- Recent activity + Quick actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Recent users -->
  <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900 text-sm">Recent Users</h3>
      <a href="/admin/users" class="text-xs text-brand-600 font-semibold hover:text-brand-700 transition">View all →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Role</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Joined</th>
            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @forelse($recentUsers ?? [] as $user)
          <tr class="hover:bg-gray-50 transition">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-accent-400 rounded-xl flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                  {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                  <div class="font-semibold text-gray-900 text-xs">{{ $user->name }}</div>
                  <div class="text-gray-400 text-xs">{{ $user->email }}</div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 hidden sm:table-cell">
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                {{ $user->hasRole('super-admin') ? 'bg-red-100 text-red-700' : ($user->hasRole('admin') ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                {{ $user->roles->first()?->name ?? 'student' }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-gray-500 text-xs hidden md:table-cell">{{ $user->created_at?->diffForHumans() }}</td>
            <td class="px-5 py-3.5">
              <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Active
              </span>
            </td>
          </tr>
          @empty
          <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">No users yet</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Quick actions -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-bold text-gray-900 text-sm mb-4">Quick Actions</h3>
    <div class="space-y-2.5">
      @php $actions = [
        ['icon'=>'➕','label'=>'Add New User',    'href'=>'/admin/users/create',   'color'=>'hover:bg-brand-50 hover:text-brand-700'],
        ['icon'=>'📚','label'=>'Manage Courses',  'href'=>route('admin.courses'),  'color'=>'hover:bg-purple-50 hover:text-purple-700'],
        ['icon'=>'📚','label'=>'Review Courses',  'href'=>'/admin/courses',        'color'=>'hover:bg-green-50 hover:text-green-700'],
        ['icon'=>'🔑','label'=>'Issue License',   'href'=>'/admin/licenses/create','color'=>'hover:bg-yellow-50 hover:text-yellow-700'],
        ['icon'=>'📊','label'=>'View Reports',    'href'=>'/admin/reports',        'color'=>'hover:bg-indigo-50 hover:text-indigo-700'],
        ['icon'=>'⚙️','label'=>'Platform Settings','href'=>'/admin/settings',     'color'=>'hover:bg-gray-100 hover:text-gray-700'],
      ]; @endphp
      @foreach($actions as $a)
      <a href="{{ $a['href'] }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 transition {{ $a['color'] }} group">
        <span class="text-base">{{ $a['icon'] }}</span>
        <span class="text-sm font-medium">{{ $a['label'] }}</span>
        <svg class="w-3.5 h-3.5 ml-auto opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
      </a>
      @endforeach
    </div>
  </div>
</div>

<!-- Recent courses -->
<div class="mt-6 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
    <h3 class="font-bold text-gray-900 text-sm">Recent Courses</h3>
    <a href="/admin/courses" class="text-xs text-brand-600 font-semibold hover:text-brand-700 transition">View all →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Course</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden md:table-cell">Category</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Price</th>
          <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($recentCourses ?? [] as $course)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-5 py-3.5">
            <div class="font-semibold text-gray-900 text-xs">{{ $course->title }}</div>
            <div class="text-gray-400 text-xs capitalize">{{ $course->level }} · {{ $course->category }}</div>
          </td>
          <td class="px-5 py-3.5 text-gray-500 text-xs hidden md:table-cell">{{ $course->vendor?->name ?? '—' }}</td>
          <td class="px-5 py-3.5 font-semibold text-gray-900 text-xs hidden sm:table-cell">
            {{ $course->is_free ? 'Free' : '₹'.number_format($course->price) }}
          </td>
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg
              {{ $course->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
              {{ $course->is_published ? '✓ Live' : '⏸ Draft' }}
            </span>
          </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400 text-sm">No courses yet</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection
