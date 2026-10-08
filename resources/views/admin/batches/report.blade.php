@extends('layouts.admin')
@section('title','Progress Report — {{ $course->title }}')
@section('header','Progress Report')
@section('admin-content')
<div class="space-y-6">

  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-gray-900">{{ $course->title }}</h2>
      <p class="text-sm text-gray-500">{{ $course->batch_name }} · {{ $report->count() }} students · {{ $lessons->count() }} live classes</p>
    </div>
    <a href="{{ route('admin.batches.show', $course) }}" class="text-sm text-gray-500 hover:text-brand-600 transition">← Back to Batch</a>
  </div>

  {{-- Summary cards --}}
  @php
    $avgAttendance = $report->count() ? round($report->avg('pct')) : 0;
    $avgProgress   = $report->count() ? round($report->avg('progress')) : 0;
    $fullAttend    = $report->where('pct', 100)->count();
  @endphp
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
      <div class="text-3xl font-black text-brand-600">{{ $avgAttendance }}%</div>
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mt-1">Avg Attendance</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
      <div class="text-3xl font-black text-purple-600">{{ $avgProgress }}%</div>
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mt-1">Avg Course Progress</div>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-center">
      <div class="text-3xl font-black text-green-600">{{ $fullAttend }}</div>
      <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mt-1">100% Attendance</div>
    </div>
  </div>

  {{-- Student report table --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">Student-wise Report</h2>
      <p class="text-xs text-gray-400 mt-0.5">Attendance & course progress per student</p>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 font-semibold text-gray-600">Student</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Attended</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Late</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Total Classes</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Attendance %</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Course Progress</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @forelse($report as $row)
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="font-semibold text-gray-900">{{ $row['user']->name }}</div>
              <div class="text-xs text-gray-400">{{ $row['user']->email }}</div>
            </td>
            <td class="px-4 py-3 text-center font-semibold text-green-600">{{ $row['attended'] }}</td>
            <td class="px-4 py-3 text-center font-semibold text-yellow-600">{{ $row['late'] }}</td>
            <td class="px-4 py-3 text-center text-gray-500">{{ $row['total'] }}</td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full rounded-full {{ $row['pct'] >= 75 ? 'bg-green-500' : ($row['pct'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width:{{ $row['pct'] }}%"></div>
                </div>
                <span class="text-xs font-semibold {{ $row['pct'] >= 75 ? 'text-green-600' : ($row['pct'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">{{ $row['pct'] }}%</span>
              </div>
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-brand-500 rounded-full" style="width:{{ $row['progress'] }}%"></div>
                </div>
                <span class="text-xs font-semibold text-brand-600">{{ $row['progress'] }}%</span>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No data yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
