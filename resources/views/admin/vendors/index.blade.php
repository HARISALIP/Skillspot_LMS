@extends('layouts.admin')
@section('title','Vendors — Skillspot.in LMS')
@section('admin-content')
<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Vendors</h1>
      <p class="text-sm text-gray-500 mt-0.5">Manage LMS vendors and their branding</p>
    </div>
    <a href="{{ route('admin.vendors.create') }}" class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-semibold text-sm transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add Vendor
    </a>
  </div>

  @if(session('success'))
    <div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
  @endif

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Vendor</th>
          <th class="text-left px-4 py-3 font-semibold text-gray-600">Owner</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Courses</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Students</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Status</th>
          <th class="text-left px-4 py-3 font-semibold text-gray-600">Portal URL</th>
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
            <a href="{{ url('/v/'.$vendor->slug) }}" target="_blank" class="text-brand-600 hover:underline text-xs font-mono">/v/{{ $vendor->slug }}</a>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2 justify-end">
              <a href="{{ route('admin.vendors.edit', $vendor) }}" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition">Edit</a>
              <form method="POST" action="{{ route('admin.vendors.toggle-status', $vendor) }}" class="inline">
                @csrf @method('PATCH')
                <button class="text-xs bg-{{ $vendor->status==='active'?'yellow':'green' }}-100 hover:bg-{{ $vendor->status==='active'?'yellow':'green' }}-200 text-{{ $vendor->status==='active'?'yellow':'green' }}-700 px-3 py-1.5 rounded-lg font-medium transition">
                  {{ $vendor->status==='active' ? 'Suspend' : 'Activate' }}
                </button>
              </form>
              <form method="POST" action="{{ route('admin.vendors.destroy', $vendor) }}" class="inline"
                    onsubmit="return confirm('⚠️ Delete vendor \"{{ addslashes($vendor->brand_name) }}\"?\n\nThis will permanently delete:\n• All courses, lessons & sections\n• All enrollments & certificates\n• All student & teacher access\n• All batch attendance records\n\nThis cannot be undone!')">
                @csrf @method('DELETE')
                <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg font-medium transition">
                  🗑️ Delete
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No vendors yet. <a href="{{ route('admin.vendors.create') }}" class="text-brand-600 hover:underline">Add one</a>.</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($vendors->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">{{ $vendors->links() }}</div>
    @endif
  </div>
</div>
@endsection
