<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']},colors:{brand:{50:'#eff6ff',100:'#dbeafe',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',900:'#1e3a8a'},accent:{100:'#ede9fe',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',900:'#4c1d95'}}}}}</script>
  <style>
    body[data-theme="dark"]{background:#202020!important;color:#e5e5e5}body[data-theme="dark"] .bg-white,body[data-theme="dark"] .bg-gray-50,body[data-theme="dark"] .bg-slate-50{background-color:#202020!important}body[data-theme="dark"] .bg-gray-100,body[data-theme="dark"] .bg-slate-100{background-color:#292929!important}body[data-theme="dark"] .text-gray-900,body[data-theme="dark"] .text-slate-900,body[data-theme="dark"] .text-black{color:#f5f5f5!important}body[data-theme="dark"] .text-gray-800,body[data-theme="dark"] .text-slate-800{color:#e5e5e5!important}body[data-theme="dark"] .text-gray-700,body[data-theme="dark"] .text-slate-700{color:#d4d4d4!important}body[data-theme="dark"] .text-gray-600,body[data-theme="dark"] .text-slate-600,body[data-theme="dark"] .text-gray-500,body[data-theme="dark"] .text-slate-500,body[data-theme="dark"] .text-gray-400,body[data-theme="dark"] .text-slate-400{color:#a3a3a3!important}body[data-theme="dark"] .border-gray-50,body[data-theme="dark"] .border-gray-100,body[data-theme="dark"] .border-gray-200,body[data-theme="dark"] .border-slate-100,body[data-theme="dark"] .border-slate-200{border-color:#3b3b3b!important}body[data-theme="dark"] input,body[data-theme="dark"] select,body[data-theme="dark"] textarea{background-color:#292929!important;border-color:#454545!important;color:#f5f5f5!important}body[data-theme="dark"] input::placeholder,body[data-theme="dark"] textarea::placeholder{color:#8f8f8f!important}body[data-theme="dark"] option{background:#292929;color:#f5f5f5}
  </style>
  @stack('styles')
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
  <main class="h-screen overflow-y-auto p-3 md:p-5">@yield('admin-content')</main>
  <script>document.body.dataset.theme=localStorage.getItem('skillspot-theme')==='light'?'light':(localStorage.getItem('skillspot-theme')==='system'&&!window.matchMedia('(prefers-color-scheme: dark)').matches?'light':'dark')</script>
  @stack('scripts')
</body>
</html>
