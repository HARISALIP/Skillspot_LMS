@extends('layouts.vendor')
@section('title','Courses — Vendor Panel')
@section('header','Courses')
@section('content')
<div class="space-y-4">
  @unless(isset($isVendorOnly) && $isVendorOnly)
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h2 class="font-semibold text-gray-900">Your organization courses</h2>
      <p class="mt-1 text-sm text-gray-500">Create drafts, edit saved content, and preview the learner experience.</p>
    </div>
    <a href="{{ route('vendor.courses.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-600 px-4 text-sm font-bold text-white hover:bg-brand-700">Create course</a>
  </div>
  @endunless
  @if(isset($isVendorOnly) && $isVendorOnly)
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
    <span class="text-xl">👁️</span>
    <div>
      <div class="font-semibold text-amber-900 text-sm">View Only</div>
      <div class="text-xs text-amber-700 mt-0.5">You can view course details and student progress. Contact your teacher to make changes.</div>
    </div>
  </div>
  @endif

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="divide-y divide-gray-100 md:hidden">
      @forelse($courses as $course)
      <article class="p-4 space-y-3">
        <h2 class="font-semibold text-gray-900">{{ $course->title }}</h2>
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600">
          <span>{{ ucfirst(str_replace('_',' ',$course->course_type)) }}</span>
          <span>{{ $course->enrollments_count }} enrolled{{ $course->max_students ? ' / '.$course->max_students : '' }}</span>
          <span>{{ $course->registration_open ? 'Registration open' : 'Registration closed' }}</span>
        </div>
        <div class="grid grid-cols-2 gap-2">
          @unless(isset($isVendorOnly) && $isVendorOnly)
          <a href="{{ route('vendor.courses.edit', $course) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-600 px-3 text-sm font-semibold text-white">Edit course</a>
          <a href="{{ route('instructor.courses.preview', ['course' => $course, 'from' => 'vendor']) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-700">Preview</a>
          @endunless
          <a href="{{ route('vendor.courses.students', $course) }}" class="col-span-2 inline-flex min-h-11 items-center justify-center rounded-xl bg-gray-50 text-sm font-semibold text-brand-700">View students</a>
        </div>
      </article>
      @empty
      <div class="p-6 text-sm text-gray-600">No courses yet. @if(isset($isVendorOnly) && $isVendorOnly) Ask an administrator to assign courses to this organization. @else Create the first course to begin authoring. @endif</div>
      @endforelse
    </div>
    <table class="hidden w-full text-sm md:table">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 font-semibold text-gray-600">Course</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Type</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Enrolled</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Max Seats</th>
          <th class="text-center px-4 py-3 font-semibold text-gray-600">Reg. Open</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($courses as $course)
        <tr class="hover:bg-gray-50">
          <td class="px-5 py-3">
            <div class="font-medium text-gray-900">{{ $course->title }}</div>
            @if($course->batch_name)<div class="text-xs text-purple-600 font-medium mt-0.5">Batch: {{ $course->batch_name }}</div>@endif
          </td>
          <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">{{ ucfirst(str_replace('_',' ',$course->course_type)) }}</span></td>
          <td class="px-4 py-3 text-center font-semibold">{{ $course->enrollments_count }}</td>
          <td class="px-4 py-3 text-center text-gray-500">{{ $course->max_students ?: '∞' }}</td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $course->registration_open ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
              {{ $course->registration_open ? 'Open' : 'Closed' }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2 justify-end">
              @unless(isset($isVendorOnly) && $isVendorOnly)
              <a href="{{ route('vendor.courses.edit', $course) }}"
                 class="text-xs bg-brand-100 hover:bg-brand-200 text-brand-700 font-semibold px-3 py-1.5 rounded-lg transition">Edit</a>
              <a href="{{ route('instructor.courses.preview', ['course' => $course, 'from' => 'vendor']) }}"
                 class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-1.5 rounded-lg transition">Preview</a>
              @endunless
              <a href="{{ route('vendor.courses.students', $course->id) }}"
                 class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold px-3 py-1.5 rounded-lg transition">
                👥 Students
              </a>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No courses. Ask admin to assign courses to your vendor.</td></tr>
        @endforelse
      </tbody>
    </table>
    @if($courses->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $courses->links() }}</div>@endif
  </div>
</div>
@endsection
