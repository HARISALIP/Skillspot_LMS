@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
  @vite('resources/css/app.css')
@endif
