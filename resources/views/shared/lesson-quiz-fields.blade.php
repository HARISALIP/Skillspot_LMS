@php
  $quizRows = old('quiz_questions') ?? ($lesson->quiz?->questions->map(fn($q) => [
    'question' => $q->question,
    'options' => $q->options,
    'correct_answer' => $q->correct_answer,
  ])->all() ?: array_fill(0, 3, ['question' => '', 'options' => ['', '', '', ''], 'correct_answer' => 0]));
@endphp
<div id="fieldQuiz" class="{{ old('type', $lesson->type ?? 'video') === 'quiz' ? '' : 'hidden' }} space-y-4">
  <div class="rounded-xl bg-violet-50 border border-violet-100 p-4">
    <div class="font-bold text-violet-900 text-sm">📝 Graded quiz</div>
    <p class="text-xs text-violet-700 mt-1">Students answer every question and must pass before this lesson counts as complete.</p>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <label class="sm:col-span-2 text-xs font-bold text-gray-600">Quiz title
      <input name="quiz_title" value="{{ old('quiz_title', $lesson->quiz?->title ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Module knowledge check">
    </label>
    <label class="text-xs font-bold text-gray-600">Pass score (%)
      <input name="quiz_pass_score" type="number" min="1" max="100" value="{{ old('quiz_pass_score', $lesson->quiz?->pass_score ?? 70) }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
    </label>
  </div>
  <div id="quizQuestions" class="space-y-4">
    @foreach($quizRows as $i => $row)
    <div class="quiz-question rounded-xl border border-gray-200 p-4 space-y-3">
      <div class="flex justify-between items-center"><strong class="text-sm text-gray-800">Question {{ $loop->iteration }}</strong><button type="button" onclick="this.closest('.quiz-question').remove()" class="text-xs text-red-600">Remove</button></div>
      <input name="quiz_questions[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Write a clear question" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach(['A','B','C','D'] as $j => $letter)
        <input name="quiz_questions[{{ $i }}][options][{{ $j }}]" value="{{ $row['options'][$j] ?? '' }}" placeholder="{{ $letter }}. Answer option" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
        @endforeach
      </div>
      <label class="text-xs text-gray-600 font-semibold">Correct answer
        <select name="quiz_questions[{{ $i }}][correct_answer]" class="ml-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
          @foreach(['A','B','C','D'] as $j => $letter)
          <option value="{{ $j }}" {{ (string)($row['correct_answer'] ?? '0') === (string)$j ? 'selected' : '' }}>{{ $letter }}</option>
          @endforeach
        </select>
      </label>
    </div>
    @endforeach
  </div>
  <button type="button" onclick="addQuizQuestion()" class="text-sm font-bold text-brand-600">+ Add question</button>
</div>

@push('scripts')
<script>
let nextQuizQuestionIndex = {{ $quizRows ? max(array_keys($quizRows)) + 1 : 0 }};
function addQuizQuestion() {
  const i = nextQuizQuestionIndex++;
  const row = document.createElement('div');
  row.className = 'quiz-question rounded-xl border border-gray-200 p-4 space-y-3';
  row.innerHTML = `<div class="flex justify-between items-center"><strong class="text-sm text-gray-800">Question ${i + 1}</strong><button type="button" onclick="this.closest('.quiz-question').remove()" class="text-xs text-red-600">Remove</button></div>
    <input name="quiz_questions[${i}][question]" placeholder="Write a clear question" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">${['A','B','C','D'].map((letter, j) => `<input name="quiz_questions[${i}][options][${j}]" placeholder="${letter}. Answer option" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">`).join('')}</div>
    <label class="text-xs text-gray-600 font-semibold">Correct answer <select name="quiz_questions[${i}][correct_answer]" class="ml-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">${['A','B','C','D'].map((letter, j) => `<option value="${j}">${letter}</option>`).join('')}</select></label>`;
  document.getElementById('quizQuestions').appendChild(row);
}
</script>
@endpush
