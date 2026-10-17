<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Session expired — Skillspot</title>
  @include('partials.turbo')
</head>
<body class="min-h-screen bg-gray-50 px-4 py-12 font-sans text-gray-900">
  <main class="mx-auto flex min-h-[70vh] max-w-lg flex-col justify-center">
    <p class="text-sm font-semibold text-brand-700">419 · Session expired</p>
    <h1 class="mt-3 text-3xl font-semibold">Please sign in again</h1>
    <p class="mt-3 text-gray-600">For your security, the previous form was not submitted.</p>
    <a href="{{ route('login') }}" class="mt-7 inline-flex min-h-11 w-fit items-center rounded-lg bg-brand-700 px-5 font-semibold text-white hover:bg-brand-800">Go to sign in</a>
  </main>
</body>
</html>
