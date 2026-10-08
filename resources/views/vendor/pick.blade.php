<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Vendor — ITForge LMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']},colors:{brand:{50:'#eff6ff',100:'#dbeafe',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8'},accent:{500:'#8b5cf6',600:'#7c3aed'}}}}}</script>
</head>
<body class="font-sans bg-gradient-to-br from-blue-50 to-purple-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg">

  {{-- Header --}}
  <div class="text-center mb-8">
    <div class="w-14 h-14 bg-gradient-to-br from-brand-500 to-accent-500 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-4">V</div>
    <h1 class="text-2xl font-black text-gray-900">Select Vendor</h1>
    <p class="text-gray-500 text-sm mt-1">You manage multiple vendors. Choose one to continue.</p>
  </div>

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm mb-4 text-center">{{ session('success') }}</div>@endif

  {{-- Vendor cards --}}
  <div class="space-y-3">
    @foreach($vendors as $vendor)
    <form method="POST" action="{{ route('vendor.switch') }}">
      @csrf
      <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
      <button type="submit" class="w-full bg-white rounded-2xl border-2 border-gray-100 hover:border-brand-400 shadow-sm hover:shadow-md p-5 text-left transition-all group">
        <div class="flex items-center gap-4">
          {{-- Avatar --}}
          @if($vendor->logo)
            <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-12 h-12 rounded-xl object-cover border border-gray-200 flex-shrink-0">
          @else
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-lg flex-shrink-0" style="background: linear-gradient(135deg, {{ $vendor->primary_color ?? '#2563eb' }}, {{ $vendor->accent_color ?? '#7c3aed' }})">
              {{ strtoupper(substr($vendor->brand_name,0,1)) }}
            </div>
          @endif

          {{-- Info --}}
          <div class="flex-1 min-w-0">
            <div class="font-bold text-gray-900 group-hover:text-brand-600 transition">{{ $vendor->brand_name }}</div>
            @if($vendor->tagline)<div class="text-xs text-gray-400 truncate mt-0.5">{{ $vendor->tagline }}</div>@endif
            <div class="flex items-center gap-3 mt-1.5">
              <span class="text-xs text-gray-500">📚 {{ $vendor->courses_count }} courses</span>
              <span class="text-xs text-gray-500">👥 {{ $vendor->enrollments_count }} students</span>
              <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ ucfirst($vendor->status) }}
              </span>
            </div>
          </div>

          {{-- Arrow --}}
          <div class="text-gray-300 group-hover:text-brand-500 transition text-xl flex-shrink-0">→</div>
        </div>
      </button>
    </form>
    @endforeach
  </div>

  {{-- Logout --}}
  <div class="text-center mt-6">
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="text-sm text-gray-400 hover:text-red-600 transition">Sign out</button>
    </form>
  </div>

</div>
</body>
</html>
