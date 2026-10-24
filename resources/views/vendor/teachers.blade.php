@extends('layouts.vendor')
@section('title','Teachers — Organization — Skillspot')
@section('header','Teachers')
@section('content')
@php $canManageTeachers = auth()->user()->hasRole(['admin','super-admin']) || $vendor->managers()->where('users.id', auth()->id())->exists(); @endphp
<div class="space-y-6 max-w-3xl">

  {{-- Info banner --}}
  <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-start gap-3">
    <span class="text-2xl">👩‍🏫</span>
    <div>
      <div class="font-semibold text-purple-900 text-sm">Organization roles</div>
      <div class="text-xs text-purple-700 mt-0.5">Organization admins manage staff and student access. Teachers manage course content and classes assigned to them.</div>
    </div>
  </div>

  @if($canManageTeachers)
  <form method="POST" action="{{ route('vendor.teachers.add') }}" class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:flex-row sm:items-end">
    @csrf
    <label class="min-w-0 flex-1">
      <span class="mb-1 block text-xs font-medium text-gray-600">Add an existing teacher</span>
      <select name="user_id" required class="min-h-11 w-full rounded-xl border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <option value="">Select teacher</option>
        @foreach($allTeacherUsers as $availableTeacher)
          <option value="{{ $availableTeacher->id }}">{{ $availableTeacher->name }} · {{ $availableTeacher->email }}</option>
        @endforeach
      </select>
    </label>
    <button class="min-h-11 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700">Add teacher</button>
  </form>
  @endif

  <div class="grid grid-cols-1 gap-6">

    {{-- Current teachers --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">👩‍🏫 Current Teachers ({{ $teachers->count() }})</h2>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($teachers as $teacher)
        <div class="px-5 py-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-brand-500 flex items-center justify-center text-white text-xs font-black flex-shrink-0">
              {{ strtoupper(substr($teacher->name,0,1)) }}
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ $teacher->name }}</div>
              <div class="text-xs text-gray-400">{{ $teacher->email }}</div>
            </div>
          </div>
          @if($canManageTeachers)
          <form method="POST" action="{{ route('vendor.teachers.remove', $teacher) }}" onsubmit="return confirm('Remove teacher?')">
            @csrf @method('DELETE')
            <button class="min-h-11 rounded-lg px-3 text-xs text-gray-500 transition hover:bg-red-50 hover:text-red-600">Remove</button>
          </form>
          @endif
        </div>
        @empty
        <div class="px-5 py-8 text-center text-gray-400 text-sm">No teachers assigned yet.</div>
        @endforelse
      </div>
    </div>

  </div>

  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <h2 class="font-semibold text-gray-900">Teacher access</h2>
    <p class="mt-2 text-sm text-gray-600">Teachers can edit organization courses they publish or are explicitly assigned to while they remain on staff. Organization admins control staff and course assignments.</p>
    <a href="{{ route('vendor.organization.index') }}#staff" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand-700">Manage staff and classes →</a>
  </div>

</div>
@endsection
