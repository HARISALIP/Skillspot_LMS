<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.text_direction') === 'auto' ? 'auto' : config('app.text_direction', 'ltr') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/all.min.css') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @include('partials.turbo')
  <style>
    body[data-theme="dark"]{background:#202020!important;color:#e5e5e5}body[data-theme="dark"] .bg-white,body[data-theme="dark"] .bg-gray-50,body[data-theme="dark"] .bg-slate-50{background-color:#202020!important}body[data-theme="dark"] .bg-gray-100,body[data-theme="dark"] .bg-slate-100{background-color:#292929!important}body[data-theme="dark"] .text-gray-900,body[data-theme="dark"] .text-slate-900,body[data-theme="dark"] .text-black{color:#f5f5f5!important}body[data-theme="dark"] .text-gray-800,body[data-theme="dark"] .text-slate-800{color:#e5e5e5!important}body[data-theme="dark"] .text-gray-700,body[data-theme="dark"] .text-slate-700{color:#d4d4d4!important}body[data-theme="dark"] .text-gray-600,body[data-theme="dark"] .text-slate-600,body[data-theme="dark"] .text-gray-500,body[data-theme="dark"] .text-slate-500,body[data-theme="dark"] .text-gray-400,body[data-theme="dark"] .text-slate-400{color:#a3a3a3!important}body[data-theme="dark"] .border-gray-50,body[data-theme="dark"] .border-gray-100,body[data-theme="dark"] .border-gray-200,body[data-theme="dark"] .border-slate-100,body[data-theme="dark"] .border-slate-200{border-color:#3b3b3b!important}body[data-theme="dark"] input,body[data-theme="dark"] select,body[data-theme="dark"] textarea{background-color:#292929!important;border-color:#454545!important;color:#f5f5f5!important}body[data-theme="dark"] input::placeholder,body[data-theme="dark"] textarea::placeholder{color:#8f8f8f!important}body[data-theme="dark"] option{background:#292929;color:#f5f5f5}
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
  <main class="h-screen overflow-y-auto p-3 md:p-5">@yield('admin-content')</main>
  <script>document.body.dataset.theme=localStorage.getItem('skillspot-theme')==='light'?'light':(localStorage.getItem('skillspot-theme')==='system'&&!window.matchMedia('(prefers-color-scheme: dark)').matches?'light':'dark')</script>
  <script>
    // Keep every management action inside the settings frame instead of returning to the full admin shell.
    if (window.self !== window.top) {
      document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const url = new URL(link.href, window.location.origin);
        if (url.origin === window.location.origin && url.pathname.startsWith('/admin/')) {
          url.searchParams.set('embed', '1');
          link.href = url.href;
        }
      });
      document.addEventListener('submit', event => {
        const form = event.target;
        if (form.method.toLowerCase() !== 'get') return;
        const url = new URL(form.action || window.location.href, window.location.origin);
        if (url.pathname.startsWith('/admin/')) form.action = `${url.pathname}?embed=1`;
      });
    }
  </script>
  @include('partials.ui-icon-normalizer')
  @stack('scripts')
</body>
</html>
