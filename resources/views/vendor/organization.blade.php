@extends('layouts.vendor')
@section('title', 'Manage Organization — Skillspot')
@section('header', $vendor->brand_name)
@section('page-sub', 'Staff, classes and student access')

@section('content')
<div class="max-w-6xl min-w-0 space-y-6">
  @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  <nav aria-label="Organization sections" class="flex gap-2 overflow-x-auto pb-1 text-sm">
    <a href="#classes" data-turbo="false" data-scroll-section class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-3 font-semibold text-brand-700">Classes</a>
    <a href="#staff" data-turbo="false" data-scroll-section class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-3 font-semibold text-brand-700">Staff</a>
    <a href="#import" data-turbo="false" data-scroll-section class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-3 font-semibold text-brand-700">Import</a>
    <a href="#students" data-turbo="false" data-scroll-section class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-3 font-semibold text-brand-700">Students</a>
  </nav>

  <section id="classes" tabindex="-1" class="min-w-0 scroll-mt-4 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold">Classes and batches</h2>
    <p class="mt-1 text-sm text-gray-500">Students may belong to several classes. Courses can target any combination.</p>
    <form method="POST" action="{{ route('vendor.organization.classes.store') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">@csrf
      <input type="hidden" name="_form" value="class">
      <div class="min-w-0 flex-1"><label for="new-class-name" class="mb-1 block text-sm font-semibold">Class name <span class="text-red-600">*</span></label>
        <input id="new-class-name" name="name" required maxlength="120" value="{{ old('_form') === 'class' ? old('name') : '' }}" placeholder="Class 10 A" aria-invalid="{{ $errors->getBag('class')->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->getBag('class')->has('name') ? 'class-name-error' : '' }}" class="w-full min-w-0 rounded-xl border p-3">
        @error('name', 'class')<p id="class-name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
      </div>
      <button class="min-h-11 shrink-0 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white">Add class</button>
    </form>
    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
      @forelse($classes as $class)
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-xl border p-3 text-sm"><div class="min-w-0 break-words"><strong>{{ $class->name }}</strong><div class="text-gray-500">{{ $class->students_count }} students · {{ $class->courses_count }} courses</div></div>
          <form method="POST" action="{{ route('vendor.organization.classes.delete', $class) }}" onsubmit="return confirm('Remove this class? Student class assignments will be cleared.')">@csrf @method('DELETE')<button class="min-h-11 px-2 text-red-700" aria-label="Remove {{ $class->name }}">Remove</button></form></div>
      @empty <p class="text-sm text-gray-500">No classes yet. Add one above to target courses and assign students.</p> @endforelse
    </div>
  </section>

  <section id="staff" tabindex="-1" class="min-w-0 scroll-mt-4 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold">Organization staff</h2>
    <form method="POST" action="{{ route('vendor.organization.staff.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1.2fr_1fr_auto] lg:items-end">@csrf
      <input type="hidden" name="_form" value="staff">
      <div class="min-w-0"><label for="staff-name" class="mb-1 block text-sm font-semibold">Name <span class="text-red-600">*</span></label><input id="staff-name" name="name" required value="{{ old('_form') === 'staff' ? old('name') : '' }}" class="w-full rounded-xl border p-3" aria-invalid="{{ $errors->getBag('staff')->has('name') ? 'true' : 'false' }}">@error('name', 'staff')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
      <div class="min-w-0"><label for="staff-email" class="mb-1 block text-sm font-semibold">Email <span class="text-red-600">*</span></label><input id="staff-email" type="email" name="email" required value="{{ old('_form') === 'staff' ? old('email') : '' }}" class="w-full rounded-xl border p-3" aria-invalid="{{ $errors->getBag('staff')->has('email') ? 'true' : 'false' }}">@error('email', 'staff')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
      <div class="min-w-0"><label for="staff-role" class="mb-1 block text-sm font-semibold">Access <span class="text-red-600">*</span></label><select id="staff-role" name="role" class="w-full rounded-xl border p-3"><option value="admin" @selected(old('_form') !== 'staff' || old('role') === 'admin')>Organization admin</option><option value="teacher" @selected(old('_form') === 'staff' && old('role') === 'teacher')>Teacher</option></select>@error('role', 'staff')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
      <button class="min-h-11 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white">Invite or assign</button>
    </form>
    <div class="mt-4 divide-y">
        @foreach($staff as $person)<div class="flex min-w-0 flex-wrap items-center justify-between gap-2 py-2 text-sm"><div class="min-w-0 break-all">{{ $person->name }} · {{ $person->email }}
        <span class="text-gray-500">{{ $vendor->managers()->where('users.id', $person->id)->exists() ? 'Admin' : 'Teacher' }}</span>@if($failedInvitations->contains($person->id)) <span class="rounded bg-red-50 px-2 py-1 font-semibold text-red-700">Invitation failed</span>@endif</div>
        <div class="flex flex-wrap gap-3"><form method="POST" action="{{ route('vendor.organization.invitations.resend', $person) }}">@csrf<button class="min-h-11 text-brand-700">{{ $failedInvitations->contains($person->id) ? 'Retry invitation' : 'Resend invite' }}</button></form>
          @if($vendor->user_id !== $person->id)<form method="POST" action="{{ route('vendor.organization.staff.delete', $person) }}" onsubmit="return confirm('Remove this staff member from the organization? Their course access will be revoked.')">@csrf @method('DELETE')<button class="min-h-11 text-red-700">Remove</button></form>@endif</div></div>@endforeach
    </div>
  </section>

  <section id="import" tabindex="-1" class="min-w-0 scroll-mt-4 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold">Import students</h2>
    <p class="mt-1 text-sm text-gray-500">Up to 500 rows per file. Use class names separated by semicolons. Existing student accounts are reused.</p>
    <div class="mt-2 flex flex-wrap gap-2 text-sm"><a class="inline-flex min-h-11 items-center rounded-xl border px-3 font-semibold text-brand-700" href="{{ route('vendor.organization.students.template', 'xlsx') }}" download="organization-students.xlsx" data-turbo="false">Download Excel template</a><a class="inline-flex min-h-11 items-center rounded-xl border px-3 font-semibold text-brand-700" href="{{ route('vendor.organization.students.template', 'csv') }}" download="organization-students.csv" data-turbo="false">Download CSV template</a></div>
    <form method="POST" action="{{ route('vendor.organization.students.preview') }}" enctype="multipart/form-data" class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end">@csrf
      <div class="min-w-0"><label for="student-import-file" class="mb-1 block text-sm font-semibold">Student spreadsheet <span class="text-red-600">*</span></label><input id="student-import-file" type="file" name="file" accept=".xlsx,.csv" required class="w-full min-w-0 rounded-xl border p-3" aria-invalid="{{ $errors->has('file') ? 'true' : 'false' }}">@error('file')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div><button class="min-h-11 rounded-xl bg-brand-600 px-4 py-3 font-semibold text-white">Preview import</button>
    </form>
    @if(session('import_preview'))
      @php($invalidCount = collect(session('import_preview'))->filter(fn ($row) => $row['errors'])->count())
      <p role="status" class="mt-4 text-sm font-semibold {{ $invalidCount ? 'text-red-700' : 'text-gray-700' }}">{{ count(session('import_preview')) }} rows previewed. {{ $invalidCount ? $invalidCount.' need changes. Correct the file and preview it again.' : 'Review each row before confirming.' }}</p>
      <div class="mt-2 max-h-80 overflow-y-auto rounded-xl border">
        <div class="divide-y sm:hidden">
          @foreach(session('import_preview') as $row)
            <article class="p-3 text-sm">
              <p class="font-semibold text-gray-900">Row {{ $row['row'] }} · {{ $row['name'] }}</p>
              <p class="mt-1 break-all text-gray-600">{{ $row['email'] }}</p>
              <p class="mt-1 text-gray-600">Classes: {{ implode(', ', $row['classes']) ?: 'None' }}</p>
              <p class="mt-2 font-semibold {{ $row['errors'] ? 'text-red-700' : 'text-green-700' }}">{{ $row['errors'] ? implode('; ', $row['errors']) : ($row['existing'] ? 'Existing account' : 'New invitation') }}</p>
            </article>
          @endforeach
        </div>
        <table class="hidden w-full text-left text-sm sm:table"><caption class="sr-only">Student import preview and validation results</caption><thead class="sticky top-0 bg-gray-50"><tr><th class="p-2">Row</th><th class="p-2">Name</th><th class="p-2">Email</th><th class="p-2">Classes</th><th class="p-2">Status</th></tr></thead><tbody>
          @foreach(session('import_preview') as $row)<tr class="border-t"><td class="p-2">{{ $row['row'] }}</td><td class="p-2">{{ $row['name'] }}</td><td class="p-2 break-all">{{ $row['email'] }}</td><td class="p-2">{{ implode(', ', $row['classes']) }}</td><td class="p-2 {{ $row['errors'] ? 'text-red-700' : 'text-green-700' }}">{{ $row['errors'] ? implode('; ', $row['errors']) : ($row['existing'] ? 'Existing account' : 'New invitation') }}</td></tr>@endforeach
        </tbody></table>
      </div>
      @if(!collect(session('import_preview'))->contains(fn ($row) => $row['errors']))<form method="POST" action="{{ route('vendor.organization.students.import') }}" class="mt-3">@csrf<button class="min-h-11 w-full rounded-xl bg-green-700 px-4 py-3 font-semibold text-white sm:w-auto">Confirm import</button></form>@endif
    @endif
    @if(session('import_failed'))<p class="mt-3 text-sm text-red-700">Invitations failed for: {{ implode(', ', session('import_failed')) }}. Use Resend invite below.</p>@endif
  </section>

  <section id="students" tabindex="-1" class="min-w-0 scroll-mt-4 rounded-2xl bg-white p-4 shadow-sm sm:p-6">
    <h2 class="text-lg font-bold">Students</h2>
    <p class="mt-1 text-sm text-gray-500">Changing a class can remove access to its courses. Save each student after editing.</p>
    <div class="mt-4 divide-y">
      @forelse($students as $student)<div class="min-w-0 py-3 text-sm"><div class="flex flex-wrap justify-between gap-2"><strong class="min-w-0 break-all">{{ $student->name }} · {{ $student->email }} @if($failedInvitations->contains($student->id))<span class="rounded bg-red-50 px-2 py-1 text-red-700">Invitation failed</span>@endif</strong><form method="POST" action="{{ route('vendor.organization.invitations.resend', $student) }}">@csrf<button class="min-h-11 text-brand-700">{{ $failedInvitations->contains($student->id) ? 'Retry invitation' : 'Resend invite' }}</button></form></div>
        <form method="POST" action="{{ route('vendor.organization.students.classes', $student) }}" class="mt-2 flex flex-wrap items-center gap-3">@csrf @method('PUT')
          @foreach($classes as $class)<label class="inline-flex min-h-11 items-center gap-2 rounded-lg border px-3"><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" {{ $student->organizationClasses->contains('id', $class->id) ? 'checked' : '' }}>{{ $class->name }}</label>@endforeach
          <button class="min-h-11 rounded-lg border border-brand-600 px-4 font-semibold text-brand-700">Save classes</button>
        </form></div>@empty <p class="py-4 text-sm text-gray-500">No students assigned. Import a spreadsheet above to invite the first group.</p> @endforelse
    </div>
    {{ $students->links() }}
  </section>
</div>
@endsection
