@extends('layouts.admin')
@section('title', 'Organization access migration review')
@section('admin-content')
<div class="p-6">
  <h1 class="text-2xl font-bold">Historical access review</h1>
  <p class="mt-2 text-sm text-gray-600">These enrollments kept access during migration, but their original channel could not be proven.</p>
  <div class="mt-6 overflow-x-auto rounded-2xl bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Enrollment</th><th class="p-3">Student</th><th class="p-3">Course</th><th class="p-3">Reason</th></tr></thead><tbody>
    @forelse($issues as $issue)<tr class="border-t"><td class="p-3">{{ $issue->enrollment_id }}</td><td class="p-3">{{ $issue->email }}</td><td class="p-3">{{ $issue->title }}</td><td class="p-3">{{ $issue->reason }}</td></tr>
    @empty<tr><td class="p-4" colspan="4">No ambiguous historical access found.</td></tr>@endforelse
  </tbody></table></div>
  <div class="mt-4">{{ $issues->links() }}</div>
</div>
@endsection
