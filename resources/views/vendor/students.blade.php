@extends('layouts.vendor')
@section('title','Students — Vendor Panel')
@section('header','Students')
@section('content')
@php $isTeacher = auth()->user()->hasRole(['teacher','admin','super-admin']); @endphp
<div class="space-y-6 max-w-4xl">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>@endif

  {{-- Teacher: Assign Student Form --}}
  @if($isTeacher)
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h2 class="font-semibold text-gray-900 mb-1">➕ Assign Student</h2>
    <p class="text-xs text-gray-400 mb-4">Enter student's registered email to grant portal access. Optionally enroll them in a course.</p>
    <form method="POST" action="{{ route('vendor.students.grant') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      @csrf
      <div class="sm:col-span-1">
        <label class="block text-xs font-medium text-gray-600 mb-1">Student Email *</label>
        <input type="email" name="email" required placeholder="student@email.com"
               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Enroll in Course <span class="text-gray-400">(optional)</span></label>
        <select name="course_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="">— Portal access only —</option>
          @foreach($courses as $c)
            <option value="{{ $c->id }}">{{ $c->title }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Note <span class="text-gray-400">(optional)</span></label>
        <div class="flex gap-2">
          <input type="text" name="note" placeholder="e.g. Batch A"
                 class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition whitespace-nowrap">Assign</button>
        </div>
      </div>
    </form>
    @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
  </div>
  @else
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
    <span class="text-xl flex-shrink-0">👁️</span>
    <div>
      <div class="font-semibold text-amber-900 text-sm">View Only</div>
      <div class="text-xs text-amber-700 mt-0.5">Student assignment is handled by your teacher. Contact them to add/remove students.</div>
    </div>
  </div>
  @endif

  {{-- Students List --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h2 class="font-semibold text-gray-900">👥 Assigned Students ({{ $students->count() }})</h2>
    </div>
    @if($students->count())
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 font-semibold text-gray-600">Student</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Enrolled Courses</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Portal Access</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Joined</th>
            @if($isTeacher)<th class="px-4 py-3"></th>@endif
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @foreach($students as $s)
          @php
            $enrolledCourses = \App\Models\Enrollment::where('user_id', $s->id)
              ->whereIn('course_id', $courses->pluck('id'))
              ->with('course:id,title')
              ->get();
            $pa = $s->portal_access ?? 'skillspot_only';
          @endphp
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3">
              <div class="font-semibold text-gray-900">{{ $s->name }}</div>
              <div class="text-xs text-gray-400">{{ $s->email }}</div>
              @if($s->phone)<div class="text-xs text-gray-400">{{ $s->phone }}</div>@endif
            </td>
            <td class="px-4 py-3 text-center">
              @if($enrolledCourses->count())
                <div class="flex flex-wrap gap-1 justify-center">
                  @foreach($enrolledCourses as $en)
                    <span class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full font-medium">{{ $en->course->title ?? '—' }}</span>
                  @endforeach
                </div>
              @else
                <span class="text-xs text-gray-400">Portal only</span>
              @endif
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                {{ $pa === 'vendor_only' ? 'bg-purple-100 text-purple-700' : ($pa === 'both' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700') }}">
                {{ $pa === 'vendor_only' ? '🏪 Vendor' : ($pa === 'both' ? '🔁 Both' : '🎓 ITForge') }}
              </span>
            </td>
            <td class="px-4 py-3 text-center text-xs text-gray-400">{{ $s->created_at?->format('d M Y') }}</td>
            @if($isTeacher)
            <td class="px-4 py-3 text-center">
              <form method="POST" action="{{ route('vendor.students.revoke', $s) }}" onsubmit="return confirm('Remove {{ $s->name }} from this portal?')">
                @csrf @method('DELETE')
                <button class="text-xs text-gray-400 hover:text-red-600 transition px-2 py-1">✕ Remove</button>
              </form>
            </td>
            @endif
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="px-5 py-10 text-center text-gray-400 text-sm">No students assigned yet.</div>
    @endif
  </div>

</div>
@endsection
