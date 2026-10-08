<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — {{ $vendor->brand_name }}</title>
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

  {{-- Left branding panel --}}
  <div class="hidden lg:flex lg:w-1/2 hero-bg flex-col items-center justify-center p-12 text-white">
    @if($vendor->logo)
      <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-20 h-20 rounded-2xl object-cover mb-6 shadow-lg">
    @else
      <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center text-4xl font-black mb-6">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
    @endif
    <h1 class="text-3xl font-black text-center">{{ $vendor->brand_name }}</h1>
    @if($vendor->tagline)<p class="text-white/70 text-center mt-2 text-lg">{{ $vendor->tagline }}</p>@endif

    @if($courses->count())
    <div class="mt-8 space-y-2 w-full max-w-xs">
      <p class="text-white/60 text-xs uppercase tracking-widest font-semibold">Available Courses</p>
      @foreach($courses->take(4) as $c)
      <div class="bg-white/10 rounded-xl px-4 py-2.5 flex items-center gap-3">
        <div class="w-2 h-2 rounded-full bg-white/60 flex-shrink-0"></div>
        <div>
          <div class="text-sm font-semibold text-white">{{ $c->title }}</div>
          @if($c->batch_name)<div class="text-xs text-white/60">{{ $c->batch_name }}</div>@endif
        </div>
      </div>
      @endforeach
    </div>
    @endif

    <div class="mt-10 text-white/50 text-xs text-center">
      Already have an account?<br>
      <a href="{{ route('vendor.portal.login', $vendor->slug) }}" class="text-white font-semibold underline mt-1 inline-block">Sign in →</a>
    </div>
  </div>

  {{-- Right register form --}}
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

      <h2 class="text-2xl font-black text-gray-900 mb-1">Create your account 🎉</h2>
      <p class="text-gray-500 text-sm mb-8">Join {{ $vendor->brand_name }} and start learning today.</p>

      @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-5">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('vendor.portal.register.general.submit', $vendor->slug) }}" class="space-y-4">
        @csrf

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
          <input type="text" name="name" value="{{ old('name') }}" required autofocus
                 placeholder="Your full name"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-400">*</span></label>
          <input type="email" name="email" value="{{ old('email') }}" required
                 placeholder="your@email.com"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
          <input type="tel" name="phone" value="{{ old('phone') }}"
                 placeholder="+91 98765 43210"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        @if($courses->count())
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Course <span class="text-gray-400 font-normal">(optional)</span></label>
          <select name="course_id" class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
            <option value="">— Select a course (optional) —</option>
            @foreach($courses as $c)
              <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>
                {{ $c->title }}{{ $c->batch_name ? ' — '.$c->batch_name : '' }}
              </option>
            @endforeach
          </select>
        </div>
        @endif

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password <span class="text-red-400">*</span></label>
          <input type="password" name="password" required
                 placeholder="Min. 8 characters"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Confirm Password <span class="text-red-400">*</span></label>
          <input type="password" name="password_confirmation" required
                 placeholder="Repeat password"
                 class="input-focus w-full border border-gray-200 rounded-xl px-4 py-3 text-sm bg-white transition">
        </div>

        <x-human-verify :formKey="'portal_reg_' . $vendor->slug"/>

        <button type="submit" class="btn-primary w-full text-white font-bold py-3 rounded-xl transition text-sm mt-2">
          Create Account →
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Already have an account?
        <a href="{{ route('vendor.portal.login', $vendor->slug) }}" class="link-primary font-semibold hover:underline">Sign in</a>
      </p>

      <div class="mt-8 pt-6 border-t border-gray-100 text-center">
        <a href="{{ route('vendor.portal.home', $vendor->slug) }}" class="text-xs text-gray-400 hover:text-gray-600 transition">← Back to {{ $vendor->brand_name }}</a>
      </div>

    </div>
  </div>

@stack('scripts')
</body>
</html>
