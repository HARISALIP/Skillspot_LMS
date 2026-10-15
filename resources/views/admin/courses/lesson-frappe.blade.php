<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $isEdit ? 'Edit lesson' : 'New lesson' }} — {{ $course->title }}</title>
  @vite(['resources/css/app.css', 'resources/js/frappe-workspace.js'])
</head>
<body class="frappe-lesson-embed">
  <main id="frappe-lesson-editor"
        data-course-id="{{ $course->id }}"
        data-section-id="{{ $section->id }}"
        data-lesson-id="{{ $isEdit ? $lesson->id : '' }}"></main>
</body>
</html>
