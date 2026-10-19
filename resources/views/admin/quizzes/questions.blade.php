@extends('layouts.admin')

@section('page-title', 'Questions')
@section('page-actions')
  <a href="{{ route('admin.quizzes.submissions') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-file-lines mr-1"></i>Submissions</a>
  <a href="{{ route('admin.quizzes') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-list mr-1"></i>Quizzes</a>
@endsection

@section('admin-content')
<div class="space-y-5">
  <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">{{ $questions->total() }} Questions</h2><form><label class="sr-only" for="question-search">Search</label><input id="question-search" name="search" value="{{ request('search') }}" placeholder="Search" class="w-44 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm"></form></div>
  <div class="overflow-hidden rounded-xl bg-white"><table class="w-full text-left text-sm"><thead class="bg-gray-100 text-xs text-gray-500"><tr><th class="px-4 py-3">Question</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Marks</th><th class="px-4 py-3">Quiz</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($questions as $question)<tr><td class="max-w-xl truncate px-4 py-3">{{ strip_tags($question->question) }}</td><td class="px-4 py-3">{{ str_replace('_', ' ', $question->question_type) }}</td><td class="px-4 py-3">{{ $question->marks }}</td><td class="px-4 py-3"><a class="font-medium hover:underline" href="{{ route('admin.quizzes.edit', $question->quiz) }}">{{ $question->quiz->title }}</a></td></tr>@empty<tr><td colspan="4" class="px-4 py-12 text-center text-gray-500">No questions found.</td></tr>@endforelse</tbody></table></div>
  {{ $questions->links() }}
</div>
@endsection
