@extends('layouts.admin')
@section('page-title', $course->title)
@section('page-sub', 'Batch management — students, attendance & certificates')
@section('admin-content')
<div class="space-y-6">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>@endif

  {{-- Header --}}
  <div class="bg-gradient-to-r from-brand-600 to-accent-600 rounded-2xl p-5 text-white flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="font-bold text-lg">{{ $course->batch_name ?? $course->title }}</div>
      <div class="text-white/70 text-sm">
        {{ $course->vendor?->brand_name ?? 'Skillspot.in' }}
        · {{ $course->batch_start_date?->format('d M Y') ?? '—' }} → {{ $course->batch_end_date?->format('d M Y') ?? '—' }}
        · {{ $enrolled->count() }} students
      </div>
    </div>
    <div class="flex gap-2">
      <a href="{{ route('admin.batches.report', $course) }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">📊 Report</a>
      <a href="{{ route('admin.courses.edit', $course) }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">✏️ Edit Course</a>
      <a href="{{ route('admin.batches') }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">← Back</a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Add students --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">➕ Add Students</h2>
      </div>
      <div class="p-5 space-y-4">
        <form method="POST" action="{{ route('admin.batches.bulk-allow', $course) }}">
          @csrf
          <label class="block text-xs font-semibold text-gray-500 mb-1.5">Bulk Add (emails, comma/newline separated)</label>
          <textarea name="emails" rows="3" placeholder="student1@email.com&#10;student2@email.com" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
          <button type="submit" class="mt-2 w-full bg-brand-600 hover:bg-brand-700 text-white py-2.5 rounded-xl text-sm font-semibold transition">Add Students</button>
        </form>
        <form method="POST" action="{{ route('admin.batches.preallow', $course) }}" class="border-t border-gray-100 pt-4">
          @csrf
          <label class="block text-xs font-semibold text-gray-500 mb-1.5">Pre-allow (auto-grant when they register)</label>
          <div class="flex gap-2 mb-2">
            <select name="contact_type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm">
              <option value="email">Email</option>
              <option value="phone">Phone</option>
            </select>
          </div>
          <textarea name="contacts" rows="2" placeholder="email or phone…" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
          <button type="submit" class="mt-2 w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-xl text-sm font-semibold transition">Pre-allow</button>
        </form>
      </div>
    </div>

    {{-- Enrolled students --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">👥 Students ({{ $enrolled->count() }})</h2>
      </div>
      <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
        @forelse($enrolled as $e)
        <div class="px-5 py-3 flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-gray-900">{{ $e->user->name }}</div>
            <div class="text-xs text-gray-400">{{ $e->user->email }}</div>
          </div>
          <div class="flex items-center gap-2">
            @if(in_array($e->user_id, $certs))
              <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold">🎓 Cert</span>
            @else
              <form method="POST" action="{{ route('admin.batches.certificate', [$course, $e]) }}" onsubmit="return confirm('Issue certificate?')">
                @csrf
                <button class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-2 py-1 rounded-lg font-semibold transition">Issue Cert</button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.batches.revoke', [$course, $e->user]) }}" onsubmit="return confirm('Remove student?')">
              @csrf @method('DELETE')
              <button class="text-xs text-red-500 hover:text-red-700 transition">✕</button>
            </form>
          </div>
        </div>
        @empty
        <div class="px-5 py-6 text-center text-gray-400 text-sm">No students yet.</div>
        @endforelse
      </div>
    </div>

  </div>

  {{-- Live Classes & Attendance --}}
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
            <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $lesson->live_scheduled_at?->format('d M, h:i A') ?? '—' }}</td>
            <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $att['present'] }}</td>
            <td class="px-4 py-3 text-center text-red-500 font-semibold">{{ $att['absent'] }}</td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-green-500 rounded-full" style="width:{{ $rate }}%"></div></div>
                <span class="text-xs font-semibold text-gray-600">{{ $rate }}%</span>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <a href="{{ route('admin.batches.attendance', [$course, $lesson]) }}" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-lg font-semibold transition">Mark Attendance</a>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No live classes yet. <a href="{{ route('admin.live') }}" class="text-brand-600 hover:underline">Schedule one</a>.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
