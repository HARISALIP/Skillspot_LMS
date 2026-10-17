<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Something went wrong — Skillspot</title>
  @include('partials.turbo')
</head>
<body class="min-h-screen bg-gray-50 px-4 py-12 font-sans text-gray-900">
  @php
    $home = auth()->check()
      ? (auth()->user()->hasRole(['admin', 'super-admin'])
        ? route('admin.dashboard')
        : (auth()->user()->hasRole(['teacher', 'vendor']) ? route('vendor.dashboard') : route('student.dashboard')))
      : route('home');
  @endphp
  <main class="mx-auto flex min-h-[70vh] max-w-lg flex-col justify-center">
    <p class="text-sm font-semibold text-brand-700">500 · Temporary problem</p>
    <h1 class="mt-3 text-3xl font-semibold">We could not load this page</h1>
    <p class="mt-3 text-gray-600">Please try again. If it keeps happening, contact your academy administrator.</p>
    <div class="mt-7 flex flex-wrap gap-3">
      <a href="{{ url()->current() }}" class="inline-flex min-h-11 items-center rounded-lg bg-brand-700 px-5 font-semibold text-white hover:bg-brand-800">Try again</a>
      <a href="{{ $home }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-5 font-semibold hover:bg-gray-100">Go to dashboard</a>
    </div>
  </main>
</body>
</html>
