<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Skillspot.in LMS')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','sans-serif'] },
          colors: {
            brand: { 50:'#eff6ff',100:'#dbeafe',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',900:'#1e3a8a' },
            accent: { 100:'#ede9fe',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',900:'#4c1d95' }
          }
        }
      }
    }
  </script>
  <style>
    .gradient-bg { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #312e81 100%); }
    .blob1 { position:fixed; width:400px; height:400px; background:#2563eb22; border-radius:50%; filter:blur(80px); top:-100px; right:-100px; pointer-events:none; }
    .blob2 { position:fixed; width:300px; height:300px; background:#7c3aed22; border-radius:50%; filter:blur(80px); bottom:-80px; left:-80px; pointer-events:none; }
  </style>
  @stack('styles')
</head>
<body class="font-sans gradient-bg min-h-screen flex flex-col">
  <div class="blob1"></div>
  <div class="blob2"></div>

  <!-- Top bar -->
  <div class="relative z-10 flex items-center justify-between px-5 py-4 md:px-10">
    <a href="/" class="flex items-center gap-2.5">
      <div class="w-9 h-9 bg-gradient-to-br from-brand-400 to-accent-500 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg">I</div>
      <span class="text-white font-bold text-xl">Skillspot.in <span class="text-brand-400">LMS</span></span>
    </a>
    <div class="text-sm">@yield('top-link')</div>
  </div>

  <!-- Main -->
  <div class="relative z-10 flex-1 flex items-center justify-center px-4 py-6 md:py-10">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">
      @yield('content')
    </div>
  </div>

  <!-- Footer -->
  <div class="relative z-10 text-center text-gray-500 text-xs py-5">
    © {{ date('Y') }} Skillspot.in LMS &nbsp;·&nbsp;
    <a href="#" class="hover:text-white transition">Privacy</a> &nbsp;·&nbsp;
    <a href="#" class="hover:text-white transition">Terms</a>
  </div>

  @stack('scripts')
</body>
</html>
