@extends('layouts.admin')
@section('title','Enrollments — Skillspot.in Admin')
@section('page-title','Enrollments')
@section('page-sub','Manage student course access, expiry and restrictions')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  @foreach([
    ['🎓','Total',    $stats['total'],   'bg-blue-50',  'text-blue-700'],
    ['✅','Active',   $stats['active'],  'bg-green-50', 'text-green-700'],
    ['⚠️','Expiring', $stats['expiring'],'bg-orange-50','text-orange-700'],
    ['❌','Expired',  $stats['expired'], 'bg-red-50',   'text-red-700'],
  ] as [$icon,$label,$val,$bg,$text])
  <div class="{{ $bg }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $icon }}</div>
    <div class="text-2xl font-black {{ $text }}">{{ $val }}</div>
    <div class="text-xs font-semibold text-gray-600">{{ $label }}</div>
  </div>
  @endforeach
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

  {{-- Header --}}
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
    <h3 class="font-black text-gray-900 text-sm">All Enrollments</h3>
    <a href="{{ route('admin.enrollments.create') }}"
       class="flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
      ➕ Enroll Student
    </a>
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('admin.enrollments') }}"
        class="flex flex-wrap gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
    <div class="relative flex-1 min-w-[180px]">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student or course…"
             class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
    </div>
    <select name="course_id" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Courses</option>
      @foreach($courses as $course)
      <option value="{{ $course->id }}" {{ request('course_id')==$course->id?'selected':'' }}>
        {{ Str::limit($course->title,35) }}
      </option>
      @endforeach
    </select>
    <select name="access_type" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Access</option>
      <option value="lifetime" {{ request('access_type')==='lifetime'?'selected':'' }}>♾️ Lifetime</option>
      <option value="limited"  {{ request('access_type')==='limited' ?'selected':'' }}>📅 Limited</option>
    </select>
    <select name="expiry" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Expiry</option>
      <option value="expiring" {{ request('expiry')==='expiring'?'selected':'' }}>⚠️ Expiring (7d)</option>
      <option value="expired"  {{ request('expiry')==='expired' ?'selected':'' }}>❌ Expired</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold">Search</button>
    @if(request()->hasAny(['search','course_id','access_type','expiry']))
    <a href="{{ route('admin.enrollments') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Clear</a>
    @endif
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Student</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Course</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Access</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Progress</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Enrolled</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($enrollments as $en)
        @php
          $isExpired  = $en->expires_at && $en->expires_at->isPast();
          $isExpiring = $en->expires_at && !$isExpired && $en->expires_at->diffInDays(now()) <= 7;
        @endphp
        <tr class="hover:bg-gray-50 transition {{ $isExpired ? 'opacity-60' : '' }}">
          {{-- Student --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-accent-400 rounded-xl flex items-center justify-center text-white font-black text-xs flex-shrink-0">
                {{ strtoupper(substr($en->user->name??'?',0,1)) }}
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-gray-900 text-xs truncate">{{ $en->user->name ?? '—' }}</div>
                <div class="text-gray-400 text-xs truncate">{{ $en->user->email ?? '' }}</div>
              </div>
            </div>
          </td>
          {{-- Course --}}
          <td class="px-5 py-3.5 hidden md:table-cell">
            <div class="text-xs font-semibold text-gray-700 max-w-[180px] truncate">{{ $en->course->title ?? '—' }}</div>
            <div class="text-xs text-gray-400 capitalize">{{ $en->course->category ?? '' }}</div>
          </td>
          {{-- Access --}}
          <td class="px-5 py-3.5">
            @if($en->access_type === 'lifetime')
              <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-lg font-bold">♾️ Lifetime</span>
            @else
              <div>
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg font-bold
                  {{ $isExpired ? 'bg-red-100 text-red-700' : ($isExpiring ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                  📅 {{ $isExpired ? 'Expired' : 'Limited' }}
                </span>
                @if($en->expires_at)
                <div class="text-xs {{ $isExpired?'text-red-500':($isExpiring?'text-orange-600':'text-gray-500') }} mt-0.5 font-medium">
                  {{ $isExpired ? 'Expired '.$en->expires_at->diffForHumans() : 'Expires '.$en->expires_at->format('d M Y') }}
                </div>
                @endif
              </div>
            @endif
          </td>
          {{-- Progress --}}
          <td class="px-5 py-3.5 hidden sm:table-cell">
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-gray-200 rounded-full h-1.5 overflow-hidden w-16">
                <div class="bg-brand-500 h-full rounded-full" style="width:{{ $en->progress }}%"></div>
              </div>
              <span class="text-xs text-gray-600 font-semibold">{{ $en->progress }}%</span>
            </div>
          </td>
          {{-- Date --}}
          <td class="px-5 py-3.5 text-gray-400 text-xs hidden lg:table-cell">
            {{ $en->created_at?->format('d M Y') }}
            @if($en->payment_method === 'manual')
            <div class="text-brand-600 font-semibold">Manual</div>
            @endif
          </td>
          {{-- Actions --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5">
              <a href="{{ route('admin.enrollments.edit', $en->id) }}"
                 class="p-1.5 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition" title="Edit">✏️</a>
              @if($en->access_type === 'limited')
              <button onclick="openExtend({{ $en->id }},'{{ addslashes($en->user->name??'') }}')"
                      class="p-1.5 rounded-lg hover:bg-green-50 text-gray-400 hover:text-green-600 transition" title="Extend">⏰</button>
              @endif
              <form method="POST" action="{{ route('admin.enrollments.destroy',$en->id) }}"
                    onsubmit="return confirm('Remove access for {{ addslashes($en->user->name??'') }}?')">
                @csrf @method('DELETE')
                <button class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition" title="Revoke">🚫</button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-14 text-center text-gray-400">
          <div class="text-4xl mb-2">🎓</div>
          <p class="font-medium">No enrollments yet</p>
          <a href="{{ route('admin.enrollments.create') }}" class="text-brand-600 text-sm font-semibold hover:underline mt-1 inline-block">Enroll a student →</a>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($enrollments->hasPages())
  <div class="px-5 py-4 border-t border-gray-100">{{ $enrollments->withQueryString()->links('pagination::tailwind') }}</div>
  @else
  <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">{{ $enrollments->total() }} enrollments</div>
  @endif
</div>

{{-- Extend modal --}}
<div id="extendModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
    <h3 class="font-black text-gray-900 mb-1">⏰ Extend Access</h3>
    <p class="text-xs text-gray-400 mb-4" id="extendName">Student</p>
    <form method="POST" id="extendForm" class="space-y-4">
      @csrf @method('PATCH')
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Extend by (days)</label>
        <div class="grid grid-cols-4 gap-2 mb-2">
          @foreach([30,60,90,180] as $d)
          <button type="button" onclick="document.getElementById('extendDays').value={{ $d }}"
                  class="py-2 bg-gray-100 hover:bg-brand-100 hover:text-brand-700 rounded-xl text-xs font-bold transition">
            {{ $d }}d
          </button>
          @endforeach
        </div>
        <input type="number" name="days" id="extendDays" value="30" min="1" max="1825" required
               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <p class="text-xs text-gray-400 mt-1">Enter custom days or pick above</p>
      </div>
      <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl text-sm">Extend ✅</button>
        <button type="button" onclick="document.getElementById('extendModal').classList.add('hidden')"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm">Cancel</button>
      </div>
    </form>
  </div>
</div>

@endsection
@push('scripts')
<script>
function openExtend(id, name) {
  document.getElementById('extendName').textContent = name;
  document.getElementById('extendForm').action = '/admin/enrollments/' + id + '/extend';
  document.getElementById('extendModal').classList.remove('hidden');
}
document.getElementById('extendModal')?.addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
