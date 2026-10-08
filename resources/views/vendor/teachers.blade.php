@extends('layouts.vendor')
@section('title','Teachers — Vendor Panel')
@section('header','Teachers')
@section('content')
<div class="space-y-6 max-w-3xl">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 text-red-800 border border-red-200 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>@endif

  {{-- Info banner --}}
  <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex items-start gap-3">
    <span class="text-2xl">👩‍🏫</span>
    <div>
      <div class="font-semibold text-purple-900 text-sm">Teacher Role</div>
      <div class="text-xs text-purple-700 mt-0.5">Teachers can schedule classes, manage courses, upload recordings and documents. Vendors have view-only access.</div>
    </div>
  </div>

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
          <form method="POST" action="{{ route('vendor.teachers.remove', $teacher) }}" onsubmit="return confirm('Remove teacher?')">
            @csrf @method('DELETE')
            <button class="text-xs text-gray-400 hover:text-red-600 transition px-2 py-1">✕ Remove</button>
          </form>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-gray-400 text-sm">No teachers assigned yet.</div>
        @endforelse
      </div>
    </div>

  </div>

  {{-- Permissions table --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">🔑 Access Permissions</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 font-semibold text-gray-600">Action</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Vendor</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600">Teacher</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @foreach([
            ['View Dashboard & Reports','✅','✅'],
            ['View Students & Attendance','✅','✅'],
            ['View Courses & Batches','✅','✅'],
            ['View Live Classes','✅','✅'],
            ['Schedule New Live Class','❌','✅'],
            ['Create / Edit Courses','❌','✅'],
            ['Add / Remove Students','❌','✅'],
            ['Upload Recordings & Docs','❌','✅'],
            ['Issue Certificates','❌','✅'],
            ['Mark Attendance','❌','✅'],
            ['Manage Teachers','✅','❌'],
          ] as [$action,$vendor,$teacher])
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-gray-700">{{ $action }}</td>
            <td class="px-4 py-3 text-center text-lg">{{ $vendor }}</td>
            <td class="px-4 py-3 text-center text-lg">{{ $teacher }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
