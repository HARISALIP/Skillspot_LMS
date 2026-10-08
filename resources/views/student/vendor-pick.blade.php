<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Portal — Skillspot.in LMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']},colors:{brand:{500:'#3b82f6',600:'#2563eb'},accent:{500:'#8b5cf6',600:'#7c3aed'}}}}}</script>
</head>
<body class="font-sans bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg">

  <div class="text-center mb-8">
    <div class="w-14 h-14 bg-gradient-to-br from-brand-500 to-accent-500 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-4">🎓</div>
    <h1 class="text-2xl font-black text-gray-900">Select Your Portal</h1>
    <p class="text-gray-500 text-sm mt-1">You have access to multiple portals. Choose one to continue.</p>
  </div>

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm mb-4 text-center">{{ session('success') }}</div>@endif

  <div class="space-y-3">

    {{-- Skillspot.in dashboard option (if both or Skillspot_only) --}}
    @if(auth()->user()->portal_access !== 'vendor_only')
    <a href="{{ route('student.dashboard') }}"
       class="w-full bg-white rounded-2xl border-2 border-gray-100 hover:border-brand-400 shadow-sm hover:shadow-md p-5 text-left transition-all group flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white font-black text-xl flex-shrink-0">I</div>
      <div class="flex-1">
        <div class="font-bold text-gray-900 group-hover:text-brand-600 transition">Skillspot.in LMS</div>
        <div class="text-xs text-gray-400 mt-0.5">Main student dashboard — all your courses</div>
      </div>
      <span class="text-gray-300 group-hover:text-brand-500 text-xl">→</span>
    </a>
    @endif

    {{-- Vendor portals --}}
    @foreach($vendors as $vendor)
    <form method="POST" action="{{ route('student.vendor.pick.submit') }}">
      @csrf
      <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
      <button type="submit" class="w-full bg-white rounded-2xl border-2 border-gray-100 hover:border-purple-400 shadow-sm hover:shadow-md p-5 text-left transition-all group flex items-center gap-4">
        @if($vendor->logo)
          <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-12 h-12 rounded-xl object-cover flex-shrink-0 border border-gray-200">
        @else
          <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-xl flex-shrink-0" style="background: linear-gradient(135deg, {{ $vendor->primary_color ?? '#7c3aed' }}, {{ $vendor->accent_color ?? '#2563eb' }})">
            {{ strtoupper(substr($vendor->brand_name,0,1)) }}
          </div>
        @endif
        <div class="flex-1">
          <div class="font-bold text-gray-900 group-hover:text-purple-600 transition">{{ $vendor->brand_name }}</div>
          @if($vendor->tagline)<div class="text-xs text-gray-400 mt-0.5 truncate">{{ $vendor->tagline }}</div>@endif
          <div class="text-xs text-purple-500 font-semibold mt-0.5">🏪 Vendor Portal</div>
        </div>
        <span class="text-gray-300 group-hover:text-purple-500 text-xl">→</span>
      </button>
    </form>
    @endforeach

  </div>

  <div class="text-center mt-6">
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="text-sm text-gray-400 hover:text-red-600 transition">Sign out</button>
    </form>
  </div>

</div>
</body>
</html>
