@extends('layouts.admin')

@section('page-title', 'Quiz Submissions')
@section('page-actions')
  <a href="{{ route('admin.quizzes.questions') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-circle-question mr-1"></i>Questions</a>
  <a href="{{ route('admin.quizzes') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-list mr-1"></i>Quizzes</a>
@endsection

@section('admin-content')
<div class="space-y-5"><h2 class="text-lg font-semibold">{{ $attempts->total() }} Submissions</h2><div class="overflow-hidden rounded-xl bg-white"><table class="w-full text-left text-sm"><thead class="bg-gray-100 text-xs text-gray-500"><tr><th class="px-4 py-3">Learner</th><th class="px-4 py-3">Quiz</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Submitted On</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($attempts as $attempt)<tr><td class="px-4 py-3">{{ $attempt->user?->name }}</td><td class="px-4 py-3">{{ $attempt->quiz?->title }}</td><td class="px-4 py-3">{{ $attempt->score }}</td><td class="px-4 py-3">{{ $attempt->status ?? ($attempt->passed ? 'Passed' : 'Not passed') }}</td><td class="px-4 py-3 text-gray-500">{{ $attempt->created_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No submissions found.</td></tr>@endforelse</tbody></table></div>{{ $attempts->links() }}</div>
@endsection
