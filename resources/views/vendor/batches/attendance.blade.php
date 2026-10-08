@extends('layouts.vendor')
@section('title','Attendance — {{ $lesson->title }}')
@section('header','Mark Attendance')
@section('content')
<div class="space-y-6">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h2 class="font-semibold text-gray-900">{{ $lesson->title }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $course->title }} · {{ $lesson->live_scheduled_at?->format('d M Y, h:i A') }}</p>
      </div>
      <a href="{{ route('vendor.batches.show', $course) }}" class="text-sm text-gray-500 hover:text-brand-600 transition">← Back</a>
    </div>

    <div class="text-xs text-amber-600 bg-amber-50 px-4 py-2 border-b border-amber-200">👁️ View only — attendance marking is done by admin/teacher.</div>
      <form method="POST" action="#" onsubmit="return false;">
      @csrf
      <div class="divide-y divide-gray-50">
        @forelse($enrolled as $e)
        @php $status = $existing[$e->user_id] ?? 'present'; @endphp
        <div class="px-5 py-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white text-xs font-black">
              {{ strtoupper(substr($e->user->name,0,1)) }}
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ $e->user->name }}</div>
              <div class="text-xs text-gray-400">{{ $e->user->email }}</div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            @foreach(['present'=>['bg-green-100 text-green-700 border-green-300','✅ Present'],
                      'late'   =>['bg-yellow-100 text-yellow-700 border-yellow-300','⏰ Late'],
                      'absent' =>['bg-red-100 text-red-600 border-red-300','❌ Absent']] as $val=>[$cls,$lbl])
            <label class="cursor-pointer">
              <input type="radio" disabled name="attendance[{{ $e->user_id }}]" value="{{ $val }}" {{ $status===$val?'checked':'' }} class="sr-only peer">
              <span class="peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-brand-500 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-semibold {{ $cls }} transition">{{ $lbl }}</span>
            </label>
            @endforeach
          </div>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-gray-400">No students enrolled.</div>
        @endforelse
      </div>
      @if($enrolled->count() > 0)
      <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
        <div class="flex gap-2">
          <button type="button" onclick="markAll('present')" class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg font-semibold transition">✅ All Present</button>
          <button type="button" onclick="markAll('absent')" class="text-xs bg-red-100 hover:bg-red-200 text-red-600 px-3 py-1.5 rounded-lg font-semibold transition">❌ All Absent</button>
        </div>
        <button type="submit" disabled class="opacity-40 cursor-not-allowed bg-brand-600 hover:bg-brand-700 text-white px-6 py-2 rounded-xl text-sm font-semibold transition">Save Attendance</button>
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
