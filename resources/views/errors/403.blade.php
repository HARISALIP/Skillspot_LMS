<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Access denied — Skillspot</title>
  @include('partials.turbo')
</head>
<body class="min-h-screen bg-gray-50 px-4 py-12 font-sans text-gray-900">
  @php
    $home = auth()->check()
      ? (auth()->user()->hasRole(['admin', 'super-admin'])
        ? route('admin.dashboard')
        : (auth()->user()->hasRole(['teacher', 'vendor']) ? route('vendor.dashboard') : route('student.dashboard')))
      : route('home');
    $previous = url()->previous();
    $canGoBack = str_starts_with($previous, url('/')) && $previous !== url()->current();
  @endphp
  <main class="mx-auto flex min-h-[70vh] max-w-lg flex-col justify-center">
    <p class="text-sm font-semibold text-brand-700">403 · Access denied</p>
    <h1 class="mt-3 text-3xl font-semibold">You cannot open this page</h1>
    <p class="mt-3 text-gray-600">Your account does not have permission for this area.</p>
    <div class="mt-7 flex flex-wrap gap-3">
      @if ($canGoBack)
        <a href="{{ $previous }}" class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-5 font-semibold hover:bg-gray-100">Go back</a>
      @endif
      <a href="{{ $home }}" class="inline-flex min-h-11 items-center rounded-lg bg-brand-700 px-5 font-semibold text-white hover:bg-brand-800">Go to dashboard</a>
    </div>
  </main>
</body>
</html>
