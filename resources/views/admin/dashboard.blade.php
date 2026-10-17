@extends('layouts.admin')
@section('title', 'Admin Dashboard — Skillspot.in LMS')
@section('page-title', 'Dashboard')
@section('page-sub', 'Welcome back, ' . (auth()->user()->name ?? 'Admin'))

@section('admin-content')

@if($needsSetup)
<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 p-5" aria-label="Academy setup">
  <div><h2 class="text-base font-semibold text-amber-950">Finish setting up your academy</h2><p class="mt-1 text-sm text-amber-900">Add the academy email in Settings so your platform is ready to communicate with learners.</p></div>
  <a href="{{ route('admin.settings', ['tab' => 'communication']) }}" class="inline-flex min-h-11 items-center rounded-lg bg-amber-900 px-4 text-sm font-semibold text-white hover:bg-amber-800">Open settings</a>
</section>
@endif

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <div><h2 class="text-lg font-semibold text-gray-900">At a glance</h2><p class="text-sm text-gray-600">Courses, learners, and work needing attention.</p></div>
  <a href="{{ route('admin.courses') }}" class="inline-flex min-h-11 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">Manage courses</a>
</div>
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  @foreach([
    ['label'=>'Published courses', 'value'=>$stats['published_courses'], 'detail'=>$stats['courses'].' total courses', 'icon'=>'fa-book-open', 'href'=>route('admin.courses')],
    ['label'=>'Enrollments', 'value'=>$stats['enrollments'], 'detail'=>$stats['users'].' users', 'icon'=>'fa-user-graduate', 'href'=>route('admin.enrollments')],
    ['label'=>'Reviews to process', 'value'=>$stats['reviews'], 'detail'=>'Submitted course reviews', 'icon'=>'fa-clipboard-check', 'href'=>route('admin.marketplace.reviews')],
    ['label'=>'Paid online this month', 'value'=>'₹'.number_format($stats['revenue']), 'detail'=>'Excludes manual payments', 'icon'=>'fa-indian-rupee-sign', 'href'=>null],
  ] as $s)
    @if($s['href'])<a href="{{ $s['href'] }}" class="block rounded-xl border border-gray-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-sm">@else<div class="rounded-xl border border-gray-200 bg-white p-5">@endif
      <i class="fa-solid {{ $s['icon'] }} text-brand-600" aria-hidden="true"></i>
      <div class="mt-4 text-2xl font-bold tabular-nums text-gray-900">{{ $s['value'] }}</div>
      <div class="mt-1 text-sm font-semibold text-gray-800">{{ $s['label'] }}</div>
      <div class="mt-1 text-xs text-gray-600">{{ $s['detail'] }}</div>
    @if($s['href'])</a>@else</div>@endif
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
          </tr>
          @empty
          <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400 text-sm">No users yet</td></tr>
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
        ['icon'=>'fa-user-plus','label'=>'Add New User',    'href'=>'/admin/users/create',   'color'=>'hover:bg-brand-50 hover:text-brand-700'],
        ['icon'=>'fa-book-open','label'=>'Manage Courses',  'href'=>route('admin.courses'),  'color'=>'hover:bg-purple-50 hover:text-purple-700'],
        ['icon'=>'fa-clipboard-check','label'=>'Review Courses',  'href'=>route('admin.marketplace.reviews'), 'color'=>'hover:bg-green-50 hover:text-green-700'],
        ['icon'=>'fa-certificate','label'=>'Manage Certificates','href'=>route('admin.certs'),'color'=>'hover:bg-yellow-50 hover:text-yellow-700'],
        ['icon'=>'fa-chart-line','label'=>'Marketplace Commissions','href'=>route('admin.marketplace.commissions'),'color'=>'hover:bg-indigo-50 hover:text-indigo-700'],
        ['icon'=>'fa-gear','label'=>'Platform Settings','href'=>'/admin/settings',     'color'=>'hover:bg-gray-100 hover:text-gray-700'],
      ]; @endphp
      @foreach($actions as $a)
      <a href="{{ $a['href'] }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 transition {{ $a['color'] }} group">
        <i class="fa-solid {{ $a['icon'] }} w-4 text-center"></i>
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
          <td class="px-5 py-3.5 text-gray-500 text-xs hidden md:table-cell">{{ $course->category ?: '—' }}</td>
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
