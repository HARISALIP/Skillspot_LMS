@extends('layouts.admin')
@section('page-title','Attendance — ' . $lesson->title)
@section('page-sub','Admin · Live Class Attendance')
@section('admin-content')
<div class="space-y-6">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  {{-- Header --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Live Class</div>
      <h2 class="text-lg font-bold text-gray-900">{{ $lesson->title }}</h2>
      <div class="text-sm text-gray-500 mt-0.5">
        {{ $lesson->section->course->title ?? '—' }}
        · {{ $lesson->live_scheduled_at?->format('d M Y, h:i A') ?? 'TBD' }}
        · {{ $lesson->live_duration_min ?? 60 }} min
      </div>
    </div>
    <div class="flex gap-3">
      <div class="text-center px-4 py-2 bg-green-50 rounded-xl"><div class="text-xl font-black text-green-600">{{ $summary['present'] }}</div><div class="text-xs text-green-600 font-semibold">Present</div></div>
      <div class="text-center px-4 py-2 bg-yellow-50 rounded-xl"><div class="text-xl font-black text-yellow-600">{{ $summary['late'] }}</div><div class="text-xs text-yellow-600 font-semibold">Late</div></div>
      <div class="text-center px-4 py-2 bg-red-50 rounded-xl"><div class="text-xl font-black text-red-500">{{ $summary['absent'] }}</div><div class="text-xs text-red-500 font-semibold">Absent</div></div>
      <div class="text-center px-4 py-2 bg-gray-100 rounded-xl"><div class="text-xl font-black text-gray-700">{{ $summary['total'] }}</div><div class="text-xs text-gray-500 font-semibold">Total</div></div>
    </div>
  </div>

  {{-- Attendance form --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="font-semibold text-gray-900">Mark / Edit Attendance</h3>
      <a href="{{ route('admin.live') }}" class="text-sm text-gray-400 hover:text-brand-600 transition">← Back to Live Classes</a>
    </div>

    <form method="POST" action="{{ route('admin.live.attendance.save', $lesson) }}">
      @csrf
      <div class="divide-y divide-gray-50">
        @forelse($enrolled as $e)
        @php
          $rec    = $existing[$e->user_id] ?? null;
          $status = $rec?->status ?? null;
          $auto   = $rec && str_contains($rec->note ?? '', 'auto-marked');
        @endphp
        <div class="px-5 py-4 flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
              {{ strtoupper(substr($e->user->name,0,1)) }}
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ $e->user->name }}</div>
              <div class="text-xs text-gray-400 flex items-center gap-2">
                {{ $e->user->email }}
                @if($auto)<span class="text-blue-500 font-semibold">🤖 Auto-marked on join</span>@elseif($rec)<span class="text-gray-400">by {{ $rec->markedBy?->name ?? 'admin' }}</span>@endif
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            @foreach(['present'=>['bg-green-100 text-green-700 border-green-300','✅ Present'],
                      'late'   =>['bg-yellow-100 text-yellow-700 border-yellow-300','⏰ Late'],
                      'absent' =>['bg-red-100 text-red-600 border-red-300','❌ Absent']] as $val=>[$cls,$lbl])
            <label class="cursor-pointer">
              <input type="radio" name="attendance[{{ $e->user_id }}]" value="{{ $val }}"
                     {{ $status === $val ? 'checked' : ($status === null && $val === 'absent' ? '' : '') }}
                     class="sr-only peer">
              <span class="peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-brand-500 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $cls }} transition">{{ $lbl }}</span>
            </label>
            @endforeach
          </div>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-gray-400">No students enrolled in this course.</div>
        @endforelse
      </div>

      @if($enrolled->count() > 0)
      <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
        <div class="flex gap-2">
          <button type="button" onclick="markAll('present')" class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg font-semibold transition">✅ All Present</button>
          <button type="button" onclick="markAll('absent')" class="text-xs bg-red-100 hover:bg-red-200 text-red-600 px-3 py-1.5 rounded-lg font-semibold transition">❌ All Absent</button>
        </div>
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-xl text-sm font-semibold transition">Save Attendance</button>
      </div>
      @endif
    </form>
  </div>
</div>

<script>
function markAll(status) {
  document.querySelectorAll('input[type=radio][value='+status+']').forEach(r => r.checked = true);
}
</script>
@endsection
