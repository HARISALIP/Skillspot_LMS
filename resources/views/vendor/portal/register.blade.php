<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>:root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
  .btn-primary { background: var(--primary); } .btn-primary:hover { opacity: 0.9; }
  .hero-bg { background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); }</style>
</head>
<body class="font-sans bg-gray-50 antialiased min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
  <!-- Vendor brand -->
  <div class="text-center mb-6">
    @if($vendor->logo)
      <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-14 h-14 rounded-2xl object-cover mx-auto mb-3">
    @else
      <div class="w-14 h-14 hero-bg rounded-2xl flex items-center justify-center text-white text-2xl font-black mx-auto mb-3">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
    @endif
    <h1 class="text-xl font-black text-gray-900">{{ $vendor->brand_name }}</h1>
    @if($vendor->tagline)<p class="text-sm text-gray-500 mt-0.5">{{ $vendor->tagline }}</p>@endif
  </div>

  <!-- Course info -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4 text-center">
    <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Registering for</div>
    <div class="font-bold text-gray-900">{{ $course->title }}</div>
    @if($course->batch_name)<div class="text-xs text-purple-600 font-medium mt-0.5">{{ $course->batch_name }}</div>@endif
    @if($course->max_students)
      <div class="text-xs text-gray-400 mt-1">{{ max(0, $course->max_students - $course->enrolled_count) }} seats remaining</div>
    @endif
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <h2 class="font-bold text-gray-900 text-base mb-4">Create your account</h2>

    @if($errors->any())
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 mb-4 text-sm text-red-800"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('vendor.portal.register.submit', [$vendor->slug, $course->id]) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2" style="--tw-ring-color: var(--primary)">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
        <input type="password" name="password" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
        <input type="password" name="password_confirmation" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2">
      </div>
      <button type="submit" class="w-full btn-primary text-white py-3 rounded-xl font-bold text-sm transition mt-2">Register & Enroll</button>
    </form>
    <p class="text-center text-sm text-gray-400 mt-4">Already have an account? <a href="{{ route('login') }}" class="font-medium" style="color: var(--primary)">Login</a></p>
  </div>
  <p class="text-center text-xs text-gray-400 mt-4">
    <a href="{{ route('vendor.portal.home', $vendor->slug) }}" style="color: var(--primary)">← Back to {{ $vendor->brand_name }}</a>
  </p>
</div>
</body>
</html>
