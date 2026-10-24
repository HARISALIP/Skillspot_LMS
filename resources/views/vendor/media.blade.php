@extends('layouts.vendor')
@section('title','Organization Media — Skillspot')
@section('header','Media')
@section('content')
<div class="space-y-5">
  <div><h2 class="text-xl font-bold text-gray-900">Organization media</h2><p class="text-sm text-gray-600">Files attached to {{ $vendor->brand_name }} courses. Add new files from the shared course editor.</p></div>
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($files as $file)
      @php($course = $courses->firstWhere('id', (int) data_get($file->used_in, 'course_id')))
      <article class="min-w-0 rounded-2xl border border-gray-100 bg-white p-4"><div class="flex items-start gap-3"><i class="fa-solid {{ str_starts_with($file->mime_type, 'video/') ? 'fa-video' : (str_starts_with($file->mime_type, 'image/') ? 'fa-image' : 'fa-file') }} mt-1 text-brand-600" aria-hidden="true"></i><div class="min-w-0"><h3 class="truncate text-sm font-semibold text-gray-900">{{ $file->original_name }}</h3><p class="mt-1 text-xs text-gray-500">{{ $course?->title }} · {{ $file->human_size }}</p></div></div>@if($course)<a href="{{ route('instructor.courses.show', $course) }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand-700">Open course →</a>@endif</article>
    @empty<div class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-600">No course media uploaded yet.</div>@endforelse
  </div>
  {{ $files->links() }}
</div>
@endsection
