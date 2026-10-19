@extends('layouts.admin')

@section('page-title', 'Quizzes')
@section('page-actions')
  <a href="{{ route('admin.quizzes.submissions') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-file-lines mr-1"></i>Submissions</a>
  <a href="{{ route('admin.quizzes.questions') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium"><i class="fa-solid fa-circle-question mr-1"></i>Questions</a>
  <a href="{{ route('admin.quizzes.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Create</a>
@endsection

@section('admin-content')
<div class="space-y-5">
  @if(session('success')) <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div> @endif
  <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-semibold">{{ $quizzes->total() }} Quizzes</h2><form><label class="sr-only" for="quiz-search">Search</label><input id="quiz-search" name="search" value="{{ request('search') }}" placeholder="Search" class="w-44 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm"></form></div>
  <div class="overflow-hidden rounded-xl bg-white">
    <table class="w-full text-left text-sm"><thead class="bg-gray-100 text-xs text-gray-500"><tr><th class="w-10 px-2 py-3"><input type="checkbox" disabled aria-label="Select quizzes"></th><th class="px-4 py-3"><i class="fa-solid fa-file-lines mr-2"></i>Title</th><th class="px-4 py-3"><i class="fa-solid fa-hashtag mr-2"></i>Total Marks</th><th class="px-4 py-3"><i class="fa-solid fa-percent mr-2"></i>Passing Percentage</th><th class="px-4 py-3"><i class="fa-solid fa-repeat mr-2"></i>Max Attempts</th><th class="px-4 py-3"><i class="fa-solid fa-eye mr-2"></i>Show Answers</th><th class="px-4 py-3"><i class="fa-solid fa-clock mr-2"></i>Updated On</th></tr></thead><tbody class="divide-y divide-gray-100">
      @forelse($quizzes as $quiz)<tr tabindex="0" role="link" data-edit-url="{{ route('admin.quizzes.edit', $quiz) }}" class="quiz-row cursor-pointer hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><td class="px-2 py-3"><input type="checkbox" disabled aria-label="Select {{ $quiz->title }}"></td><td class="px-4 py-3 font-medium"><a class="hover:underline" href="{{ route('admin.quizzes.edit', $quiz) }}">{{ $quiz->title }}</a></td><td class="px-4 py-3">{{ $quiz->questions_sum_marks ?? 0 }}</td><td class="px-4 py-3">{{ $quiz->pass_score }}</td><td class="px-4 py-3">{{ $quiz->max_attempts ?? 0 }}</td><td class="px-4 py-3"><input type="checkbox" disabled {{ $quiz->show_answers ? 'checked' : '' }} aria-label="Show answers"></td><td class="px-4 py-3 text-gray-500">{{ $quiz->updated_at?->format('d M Y') }}</td></tr>@empty
      <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No quizzes found.</td></tr>@endforelse
    </tbody></table>
  </div>
  {{ $quizzes->links() }}
</div>
@push('scripts')<script>
document.querySelectorAll('.quiz-row').forEach(row => {
  row.addEventListener('click', event => { if (!event.target.closest('a,input')) window.location.href = row.dataset.editUrl })
  row.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = row.dataset.editUrl } })
})
</script>@endpush
@endsection
