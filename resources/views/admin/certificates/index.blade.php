@extends('layouts.admin')
@section('title','Certificates — Skillspot.in Admin')
@section('page-title','Certificates')
@section('page-sub','Manage issued student certificates')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ❌ {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

  {{-- ── Left: Stats + Issue form ────────────────────────────────── --}}
  <div class="space-y-5">
    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-3">
      @foreach([
        ['📜','Total Issued', $stats['total'],      'bg-yellow-50','text-yellow-700'],
        ['📅','This Month',   $stats['this_month'], 'bg-green-50', 'text-green-700'],
        ['📚','Courses',      $stats['courses'],    'bg-blue-50',  'text-blue-700'],
      ] as [$icon,$label,$val,$bg,$text])
      <div class="{{ $bg }} rounded-2xl p-4 border border-transparent flex items-center gap-3">
        <span class="text-2xl">{{ $icon }}</span>
        <div>
          <div class="text-xl font-black {{ $text }}">{{ $val }}</div>
          <div class="text-xs font-semibold text-gray-500">{{ $label }}</div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- Issue certificate --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
        <h3 class="font-black text-gray-900 text-sm">➕ Issue Certificate</h3>
      </div>
      <form method="POST" action="{{ route('admin.certs.issue') }}" class="p-5 space-y-4">
        @csrf
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Student <span class="text-red-400">*</span></label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
            <input type="text" id="certStudentSearch" placeholder="Search student…" autocomplete="off"
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <div id="certStudentDropdown" class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-xl border border-gray-200 shadow-xl z-20 max-h-48 overflow-y-auto"></div>
          </div>
          <input type="hidden" name="user_id" id="certUserId">
          <div id="certStudentSelected" class="hidden mt-1.5 text-xs text-brand-600 font-semibold"></div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Course <span class="text-red-400">*</span></label>
          <select name="course_id" required
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <option value="">Select course…</option>
            @foreach($courses as $c)
            <option value="{{ $c->id }}">{{ Str::limit($c->title,40) }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2.5 rounded-xl text-sm transition">
          📜 Issue Certificate
        </button>
      </form>
    </div>
  </div>

  {{-- ── Right: Certificates table ───────────────────────────────── --}}
  <div class="lg:col-span-3">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h3 class="font-black text-gray-900 text-sm">All Certificates ({{ $certificates->total() }})</h3>
      </div>

      {{-- Filters --}}
      <form method="GET" action="{{ route('admin.certs') }}"
            class="flex flex-wrap gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
        <div class="relative flex-1 min-w-[160px]">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student, cert ID…"
                 class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
        </div>
        <select name="course_id" onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
          <option value="">All Courses</option>
          @foreach($courses as $c)
          <option value="{{ $c->id }}" {{ request('course_id')==$c->id?'selected':'' }}>{{ Str::limit($c->title,30) }}</option>
          @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold">Search</button>
        @if(request()->hasAny(['search','course_id']))
        <a href="{{ route('admin.certs') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Clear</a>
        @endif
      </form>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Student</th>
              <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Course</th>
              <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Certificate ID</th>
              <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Issued</th>
              <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            @forelse($certificates as $cert)
            <tr class="hover:bg-gray-50 transition">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl flex items-center justify-center text-white font-black text-xs flex-shrink-0">
                    {{ strtoupper(substr($cert->user->name??'?',0,1)) }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-semibold text-gray-900 text-xs truncate">{{ $cert->user->name ?? '—' }}</div>
                    <div class="text-gray-400 text-xs truncate hidden sm:block">{{ $cert->user->email ?? '' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-5 py-3.5 hidden md:table-cell">
                <div class="text-xs text-gray-700 max-w-[160px] truncate">{{ $cert->course->title ?? '—' }}</div>
              </td>
              <td class="px-5 py-3.5 hidden sm:table-cell">
                <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg">{{ $cert->certificate_number }}</span>
              </td>
              <td class="px-5 py-3.5 hidden lg:table-cell text-xs text-gray-400">
                {{ $cert->issued_at?->format('d M Y') ?? $cert->created_at?->format('d M Y') }}
              </td>
              <td class="px-5 py-3.5">
                <form method="POST" action="{{ route('admin.certs.revoke', $cert->id) }}"
                      onsubmit="return confirm('Revoke this certificate?')">
                  @csrf @method('DELETE')
                  <button class="text-xs text-red-500 hover:text-red-700 font-semibold hover:underline transition">🗑 Revoke</button>
                </form>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-14 text-center text-gray-400">
              <div class="text-4xl mb-2">📜</div>
              <p class="font-medium">No certificates issued yet</p>
            </td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($certificates->hasPages())
      <div class="px-5 py-4 border-t border-gray-100">{{ $certificates->withQueryString()->links('pagination::tailwind') }}</div>
      @else
      <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">{{ $certificates->total() }} certificates</div>
      @endif
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script>
const searchUrl = '{{ route('admin.enrollments.search-users') }}';
let t;
document.getElementById('certStudentSearch')?.addEventListener('input', function() {
  clearTimeout(t);
  const q = this.value.trim();
  if (q.length < 2) { document.getElementById('certStudentDropdown').classList.add('hidden'); return; }
  t = setTimeout(async () => {
    const res   = await fetch(searchUrl + '?q=' + encodeURIComponent(q));
    const users = await res.json();
    const dd    = document.getElementById('certStudentDropdown');
    dd.innerHTML = '';
    if (!users.length) {
      dd.innerHTML = '<div class="px-4 py-3 text-xs text-gray-400">No students found</div>';
    } else {
      users.forEach(u => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-brand-50 cursor-pointer text-sm';
        div.innerHTML = `<div class="w-7 h-7 bg-gradient-to-br from-brand-400 to-accent-400 rounded-lg flex items-center justify-center text-white font-black text-xs">${u.name.charAt(0).toUpperCase()}</div><div><div class="font-semibold text-gray-900">${u.name}</div><div class="text-xs text-gray-400">${u.email}</div></div>`;
        div.onclick = () => {
          document.getElementById('certUserId').value = u.id;
          document.getElementById('certStudentSearch').value = u.name;
          document.getElementById('certStudentSelected').textContent = 'Selected: ' + u.name;
          document.getElementById('certStudentSelected').classList.remove('hidden');
          dd.classList.add('hidden');
        };
        dd.appendChild(div);
      });
    }
    dd.classList.remove('hidden');
  }, 300);
});
document.addEventListener('click', e => {
  if (!e.target.closest('#certStudentSearch')) document.getElementById('certStudentDropdown').classList.add('hidden');
});
</script>
@endpush
