@extends('layouts.admin')
@section('title','Organizations — Skillspot.in LMS')
@section('page-title','Organizations')
@section('admin-content')
<div class="p-2 sm:p-6">
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Organizations</h1>
      <p class="text-sm text-gray-500 mt-0.5">Manage schools, colleges, and their marketplace ownership</p>
    </div>
    <a href="{{ route('admin.vendors.create') }}" class="inline-flex min-h-11 items-center gap-2 self-start bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-semibold text-sm transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add Organization
    </a>
  </div>
  @if(auth()->user()->hasRole('super-admin'))<a href="{{ route('admin.vendors.access-audit') }}" class="mb-4 inline-block text-sm text-brand-700 underline">Review migrated course access</a>@endif

  @if(session('success'))
    <div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
  @endif

  <div class="space-y-3 md:hidden">
    @forelse($vendors as $vendor)
    <article class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
      <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="break-words font-bold text-gray-900">{{ $vendor->brand_name }}</h2><p class="break-all text-xs text-gray-500">{{ $vendor->slug }}</p></div><span class="shrink-0 rounded-full px-2 py-1 text-xs font-semibold {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($vendor->status) }}</span></div>
      <p class="mt-3 break-all text-sm text-gray-600">{{ $vendor->user->name ?? 'No owner' }} · {{ $vendor->user->email ?? '' }}</p>
      <p class="mt-1 text-xs text-gray-500">{{ $vendor->courses_count }} courses · {{ $vendor->enrollments_count }} students</p>
      <a href="{{ route('organization.dashboard', $vendor->slug) }}" class="mt-2 block break-all text-xs font-semibold text-brand-700">Learner URL →</a>
      <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.vendors.edit', $vendor) }}" class="inline-flex min-h-11 items-center rounded-lg bg-gray-100 px-4 text-sm font-semibold text-gray-700">Edit</a>
        @if(auth()->user()->hasRole('super-admin'))<form method="POST" action="{{ route('admin.vendors.enter', $vendor) }}">@csrf<button class="min-h-11 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white">Manage organization</button></form>@endif
      </div>
      <details class="mt-3 text-sm"><summary class="cursor-pointer py-2 font-semibold text-gray-600">More actions</summary><div class="flex flex-wrap gap-2 pt-2">
        <form method="POST" action="{{ route('admin.vendors.toggle-status', $vendor) }}">@csrf @method('PATCH')<button class="min-h-11 rounded-lg bg-amber-100 px-4 font-semibold text-amber-800">{{ $vendor->status === 'active' ? 'Suspend' : 'Activate' }}</button></form>
        <form method="POST" action="{{ route('admin.vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this organization and all its courses, enrollments, and access? This cannot be undone.')">@csrf @method('DELETE')<button class="min-h-11 rounded-lg bg-red-100 px-4 font-semibold text-red-700">Delete</button></form>
      </div></details>
    </article>
    @empty<p class="rounded-2xl bg-white p-5 text-sm text-gray-500">No organizations yet.</p>@endforelse
  </div>
  <div class="hidden overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-sm md:block">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Organization</th>
          <th class="text-left px-4 py-3 font-semibold text-gray-600">Owner</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Courses</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Students</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Status</th>
          <th class="text-left px-4 py-3 font-semibold text-gray-600">Learner URL</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($vendors as $vendor)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              @if($vendor->logo)
                <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-9 h-9 rounded-xl object-cover border border-gray-200" alt="">
              @else
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-base" style="background: {{ $vendor->primary_color ?? '#2563eb' }}">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
              @endif
              <div>
                <div class="font-semibold text-gray-900">{{ $vendor->brand_name }}</div>
                <div class="text-xs text-gray-400">{{ $vendor->slug }}</div>
              </div>
            </div>
          </td>
          <td class="px-4 py-3">
            <div class="text-gray-700">{{ $vendor->user->name ?? '—' }}</div>
            <div class="text-xs text-gray-400">{{ $vendor->user->email ?? '' }}</div>
          </td>
          <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $vendor->courses_count }}</td>
          <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $vendor->enrollments_count }}</td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-1 rounded-full text-xs font-semibold
              {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : ($vendor->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
              {{ ucfirst($vendor->status) }}
            </span>
          </td>
          <td class="px-4 py-3">
            <a href="{{ route('organization.dashboard', $vendor->slug) }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline text-xs font-mono">/dashboard/org/{{ $vendor->slug }}</a>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2 justify-end">
              <a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">Edit</a>
              @if(auth()->user()->hasRole('super-admin'))<form method="POST" action="{{ route('admin.vendors.enter', $vendor) }}" class="inline">@csrf<button class="text-xs bg-brand-100 text-brand-700 px-3 py-1.5 rounded-lg font-medium">Manage</button></form>@endif
              <form method="POST" action="{{ route('admin.vendors.toggle-status', $vendor) }}" class="inline">
                @csrf @method('PATCH')
                <button class="text-xs bg-{{ $vendor->status==='active'?'yellow':'green' }}-100 hover:bg-{{ $vendor->status==='active'?'yellow':'green' }}-200 text-{{ $vendor->status==='active'?'yellow':'green' }}-700 px-3 py-1.5 rounded-lg font-medium transition">
                  {{ $vendor->status==='active' ? 'Suspend' : 'Activate' }}
                </button>
              </form>
              <form method="POST" action="{{ route('admin.vendors.destroy', $vendor) }}" class="inline"
                    onsubmit="return confirm('⚠️ Delete organization \"{{ addslashes($vendor->brand_name) }}\"?\n\nThis will permanently delete:\n• All courses, lessons & sections\n• All enrollments & certificates\n• All student & teacher access\n• All batch attendance records\n\nThis cannot be undone!')">
                @csrf @method('DELETE')
                <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg font-medium transition">
                  🗑️ Delete
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No organizations yet. <a href="{{ route('admin.vendors.create') }}" class="text-brand-600 hover:underline">Add one</a>.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($vendors->hasPages())<div class="mt-4">{{ $vendors->links() }}</div>@endif
</div>
@endsection
