<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Skillspot.in LMS')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','sans-serif'] },
          colors: {
            brand:  { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a' },
            accent: { 50:'#f5f3ff',100:'#ede9fe',200:'#ddd6fe',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',700:'#6d28d9',900:'#4c1d95' }
          }
        }
      }
    }
  </script>
  <style>
    .gradient-bg { background: linear-gradient(135deg, #f8fafc 0%, #edf4ff 50%, #f5f3ff 100%); }
    .blob1 { position:fixed; width:400px; height:400px; background:#2563eb12; border-radius:50%; filter:blur(80px); top:-100px; right:-100px; pointer-events:none; }
    .blob2 { position:fixed; width:300px; height:300px; background:#7c3aed12; border-radius:50%; filter:blur(80px); bottom:-80px; left:-80px; pointer-events:none; }
  </style>
  @stack('styles')
</head>
<body class="font-sans gradient-bg min-h-screen flex flex-col antialiased text-slate-800">
  <div class="blob1"></div>
  <div class="blob2"></div>

  <!-- Top bar -->
  <div class="relative z-10 flex items-center justify-between px-5 py-4 md:px-10">
    <a href="/" class="flex items-center gap-2.5">
      <div class="w-9 h-9 bg-gradient-to-br from-brand-600 to-accent-600 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-md shadow-brand-500/20">S</div>
      <span class="text-slate-900 font-bold text-xl">Skillspot.in <span class="text-brand-600 font-extrabold">LMS</span></span>
    </a>
    <div class="text-sm font-medium">@yield('top-link')</div>
  </div>

  <!-- Main -->
  <div class="relative z-10 flex-1 flex items-center justify-center px-4 py-6 md:py-8">
    <div class="w-full max-w-[420px] bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 overflow-hidden">
      @yield('content')
    </div>
  </div>

  <!-- Footer -->
  <div class="relative z-10 text-center text-slate-400 text-xs py-4">
    © {{ date('Y') }} Skillspot.in LMS &nbsp;·&nbsp;
    <a href="/privacy" class="hover:text-brand-600 transition">Privacy</a> &nbsp;·&nbsp;
    <a href="/terms" class="hover:text-brand-600 transition">Terms</a>
  </div>

  @stack('scripts')
</body>
</html>
