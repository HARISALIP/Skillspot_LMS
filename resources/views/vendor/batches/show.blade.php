@extends('layouts.vendor')
@section('title','{{ $course->title }} — Batch')
@section('header','{{ $course->title }}')
@section('content')
<div class="space-y-6">
  {{-- View-only notice --}}
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 mb-2">
    <span class="text-xl flex-shrink-0">👁️</span>
    <div>
      <div class="font-semibold text-amber-900 text-sm">View Only Mode</div>
      <div class="text-xs text-amber-700 mt-0.5">All batch management (students, attendance, certificates) is handled by admin.</div>
    </div>
  </div>



  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>@endif

  {{-- Batch info strip --}}
  <div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 text-white flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="font-bold text-lg">{{ $course->batch_name ?? $course->title }}</div>
      <div class="text-white/70 text-sm mt-0.5">
        {{ $course->batch_start_date?->format('d M Y') ?? '—' }} → {{ $course->batch_end_date?->format('d M Y') ?? '—' }}
        · {{ $enrolled->count() }} students enrolled
        @if($course->max_students > 0) · Max {{ $course->max_students }} seats @endif
      </div>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('vendor.batches.report', $course) }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">📊 Progress Report</a>
      <span class="bg-white/20 px-4 py-2 rounded-xl text-sm font-semibold {{ $course->registration_open ? 'text-green-200' : 'text-red-200' }}">
        {{ $course->registration_open ? '🟢 Open' : '🔴 Closed' }}
      </span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Enrolled Students --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">👥 Enrolled Students ({{ $enrolled->count() }})</h2>
      </div>
      <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
        @forelse($enrolled as $e)
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-gray-900">{{ $e->user->name }}</div>
            <div class="text-xs text-gray-400">{{ $e->user->email }}</div>
          </div>
          <div class="flex items-center gap-2">
            @if(in_array($e->user_id, $certs))
              <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">🎓 Certified</span>
            @else
              <form method="POST" action="{{ route('vendor.batches.certificate', [$course, $e]) }}" onsubmit="return confirm('Issue certificate to {{ $e->user->name }}?')">
                @csrf
                <button class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-2 py-1 rounded-lg font-semibold transition">Issue Cert</button>
              </form>
            @endif
            <form method="POST" action="{{ route('vendor.courses.revoke-student', [$course, $e->user]) }}" onsubmit="return confirm('Remove student?')">
              @csrf @method('DELETE')
              <button class="text-xs text-gray-400 hover:text-red-600 transition">✕</button>
            </form>
          </div>
        </div>
        @empty
        <div class="px-5 py-6 text-center text-gray-400 text-sm">No students enrolled yet.</div>
        @endforelse
      </div>
    </div>

  </div>

  {{-- Live Classes --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">📡 Live Classes & Attendance</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 font-semibold text-gray-600">Class</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Scheduled</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Present</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Absent</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Rate</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @forelse($lessons as $lesson)
          @php $att = $attendanceSummary[$lesson->id] ?? ['present'=>0,'absent'=>0,'late'=>0,'total'=>0]; $total = max($att['total'],1); $rate = round(($att['present']/$total)*100); @endphp
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 font-medium text-gray-900">{{ $lesson->title }}</td>
            <td class="px-4 py-3 text-center text-gray-500 text-xs">{{ $lesson->live_scheduled_at?->format('d M, h:i A') ?? '—' }}</td>
            <td class="px-4 py-3 text-center"><span class="text-green-600 font-semibold">{{ $att['present'] }}</span></td>
            <td class="px-4 py-3 text-center"><span class="text-red-500 font-semibold">{{ $att['absent'] }}</span></td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-green-500 rounded-full" style="width:{{ $rate }}%"></div></div>
                <span class="text-xs font-semibold text-gray-600">{{ $rate }}%</span>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('vendor.batches.attendance', [$course, $lesson]) }}" class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg font-medium">View Attendance</a>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No live classes scheduled yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
