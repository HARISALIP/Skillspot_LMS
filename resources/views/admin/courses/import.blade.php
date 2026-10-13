@extends('layouts.admin')

@section('title', 'Import Course — Skillspot.in Admin')
@section('page-title', 'Import Course')
@section('page-sub', 'Import a Frappe LMS course ZIP')

@section('admin-content')
<div class="mx-auto max-w-2xl">
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm md:p-8">
    <h2 class="text-lg font-bold text-gray-900">Import Course from ZIP</h2>
    <p class="mt-1 text-sm text-gray-500">Upload a Frappe LMS course export. The course is created as a draft so you can review it before publishing.</p>

    <form method="POST" action="{{ route('admin.courses.import.store') }}" enctype="multipart/form-data" class="mt-6">
      @csrf
      <label for="archive" class="flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center transition hover:border-brand-300 hover:bg-brand-50">
        <i class="fa-solid fa-file-zipper text-3xl text-brand-500"></i>
        <span class="mt-3 text-sm font-semibold text-gray-800">Choose a course ZIP</span>
        <span class="mt-1 text-xs text-gray-500">ZIP files up to 50 MB, containing course.json</span>
        <input id="archive" name="archive" type="file" accept=".zip,application/zip" class="sr-only" required>
      </label>
      @error('archive')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

      <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.courses') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</a>
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Import Course</button>
      </div>
    </form>
  </div>
</div>
@endsection
