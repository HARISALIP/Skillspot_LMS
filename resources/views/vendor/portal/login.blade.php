<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; --accent: {{ $vendor->accent_color ?? '#7c3aed' }}; }
    body { font-family: 'Inter', sans-serif; }
    .hero-bg { background: linear-gradient(135deg, var(--primary), var(--accent)); }
    .btn-primary { background: var(--primary); }
    .btn-primary:hover { opacity: 0.9; }
    .input-focus:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 20%, transparent); }
    .link-primary { color: var(--primary); }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

  {{-- Left branding panel (hidden on mobile) --}}
  <div class="hidden lg:flex lg:w-1/2 hero-bg flex-col items-center justify-center p-12 text-white">
    @if($vendor->logo)
      <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-20 h-20 rounded-2xl object-cover mb-6 shadow-lg">
    @else
      <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center text-4xl font-black mb-6">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
    @endif
    <h1 class="text-3xl font-black text-center">{{ $vendor->brand_name }}</h1>
    @if($vendor->tagline)<p class="text-white/70 text-center mt-2 text-lg">{{ $vendor->tagline }}</p>@endif
    @if($vendor->description)<p class="text-white/60 text-center mt-4 text-sm max-w-sm leading-relaxed">{{ $vendor->description }}</p>@endif

    <div class="mt-10 text-white/50 text-xs text-center">
      Don't have an account?<br>
      <a href="{{ route('vendor.portal.register.general', $vendor->slug) }}" class="text-white font-semibold underline mt-1 inline-block">Register here →</a>
    </div>
  </div>

  {{-- Right login form --}}
  <div class="flex-1 flex items-center justify-center p-6">
    <div class="w-full max-w-md">

      {{-- Mobile logo --}}
      <div class="flex lg:hidden items-center gap-3 mb-8">
        @if($vendor->logo)
          <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-10 h-10 rounded-xl object-cover">
        @else
          <div class="w-10 h-10 hero-bg rounded-xl flex items-center justify-center text-white font-black">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
        @endif
        <span class="font-bold text-gray-900 text-lg">{{ $vendor->brand_name }}</span>
      </div>

      <h2 class="text-2xl font-black text-gray-900 mb-1">Welcome back 👋</h2>
      <p class="text-gray-500 text-sm mb-8">Sign in to access your courses and classes.</p>

      @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm mb-5">{{ session('success') }}</div>
      @endif

      @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('vendor.portal.login.submit', $vendor->slug) }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email or Phone</label>
          <input type="text" name="login" value="{{ old('login') }}" required autofocus
                 placeholder="your@email.com or phone number"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-sm font-semibold text-gray-700">Password</label>
            <span class="text-xs text-gray-400">Contact admin to reset</span>
          </div>
          <input type="password" name="password" required
                 placeholder="••••••••"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" name="remember" id="remember" class="w-4 h-4 rounded" style="accent-color: var(--primary)">
          <label for="remember" class="text-sm text-gray-600">Keep me signed in</label>
        </div>

        <x-human-verify :formKey="'portal_login_' . $vendor->slug"/>

        <button type="submit" class="btn-primary w-full text-white font-bold py-3 rounded-xl transition text-sm mt-2">
          Sign In →
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        New here?
        <a href="{{ route('vendor.portal.register.general', $vendor->slug) }}" class="link-primary font-semibold hover:underline">Create an account</a>
      </p>

      <div class="mt-8 pt-6 border-t border-gray-100 text-center">
        <a href="{{ route('vendor.portal.home', $vendor->slug) }}" class="text-xs text-gray-400 hover:text-gray-600 transition">← Back to {{ $vendor->brand_name }}</a>
      </div>

    </div>
  </div>

@stack('scripts')
</body>
</html>
