<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Course workspace — Skillspot</title>
  @vite(['resources/css/app.css', 'resources/js/frappe-workspace.js'])
</head>
<body class="bg-[#f7f7f7] text-[#1f272e]">
  <div id="frappe-course-workspace" data-course-id="{{ $course?->id }}" data-vendor-id="{{ $vendorId ?? '' }}"></div>
</body>
</html>
