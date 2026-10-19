@extends('layouts.admin')

@section('title', $quiz->exists ? $quiz->title.' — Skillspot' : 'New Quiz — Skillspot')
@push('styles')<style>@include('admin.courses.creation-styles') .quiz-rich-editor .frappe-rich-editor__content .tiptap{min-height:5rem;max-height:none}</style>@endpush

@section('page-title')<span class="font-normal text-gray-500">Quizzes /</span> {{ $quiz->exists ? $quiz->title : 'New Quiz' }}@endsection
@section('page-actions')
  @if($quiz->exists)<span id="quiz-save-status" role="status" class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Saved</span><button id="save-quiz" type="button" class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white">Save quiz</button><form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('Delete this quiz?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Delete</button></form>@endif
@endsection

@section('admin-content')
@php($editing = $quiz->exists)
<form id="quizForm" data-new="{{ $editing ? '0' : '1' }}" method="POST" action="{{ $editing ? route('admin.quizzes.update', $quiz) : route('admin.quizzes.store') }}" class="-m-4 grid min-h-[calc(100vh-7rem)] grid-cols-1 bg-white lg:-m-6 lg:grid-cols-[7fr_3fr]">@csrf @if($editing) @method('PUT') @endif
  <section class="min-w-0 p-5 lg:overflow-y-auto">
    @if($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <div class="mb-5 flex items-center gap-3"><input type="search" placeholder="Search questions" {{ $editing ? '' : 'disabled' }} class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm disabled:cursor-not-allowed"><button id="add-question" type="button" {{ $editing ? '' : 'disabled' }} class="shrink-0 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"><i class="fa-solid fa-plus mr-1"></i>New question</button><button id="open-question-bank" type="button" {{ $editing ? '' : 'disabled' }} class="shrink-0 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"><i class="fa-solid fa-book-open mr-1"></i>Question bank</button></div>
    <div id="questions" class="space-y-4">
      @forelse(old('questions', $quiz->questions->map(fn($question) => ['question'=>$question->question, 'type'=>$question->question_type, 'marks'=>$question->marks, 'options'=>$question->options, 'explanations'=>$question->explanations, 'correct_answer'=>$question->correct_answer, 'correct_answers'=>$question->correct_answers, 'accepted_answers'=>$question->accepted_answers])->all()) as $i => $question)
        @include('admin.quizzes.question', ['i' => $i, 'question' => $question])
      @empty
        <div id="empty-questions" class="flex min-h-80 flex-col items-center justify-center text-center"><i class="fa-solid fa-pencil text-3xl text-gray-400"></i><h2 class="mt-4 text-lg font-semibold">{{ $editing ? 'No questions yet' : 'Name the quiz first' }}</h2><p class="mt-1 max-w-72 text-sm text-gray-500">{{ $editing ? 'Add your first question and edit choices, correct answers, and marks here.' : 'Fill in the title on the right. The quiz is created then, and questions can go in.' }}</p></div>
      @endforelse
    </div>
  </section>
  <aside id="quiz-details-panel" class="order-first min-w-0 border-b border-gray-200 lg:order-none lg:overflow-y-auto lg:border-b-0 lg:border-l">
    <div id="quiz-settings-panel" class="space-y-6 p-5">
    <div class="space-y-4"><h2 class="text-lg font-semibold">Details</h2>
      <label class="block text-sm font-medium">Title <span class="text-red-600">*</span><input id="quiz-title" name="title" required maxlength="255" value="{{ old('title', $quiz->title) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
      <label class="block text-sm font-medium">Maximum Attempts<input name="max_attempts" type="number" min="1" value="{{ old('max_attempts', $quiz->max_attempts) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
      <label class="block text-sm font-medium">Duration (in minutes)<input name="time_limit" type="number" min="1" value="{{ old('time_limit', $quiz->time_limit) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
      <label class="block text-sm font-medium">Passing Percentage <span class="text-red-600">*</span><input name="pass_score" required type="number" min="1" max="100" value="{{ old('pass_score', $quiz->pass_score ?? 100) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"></label>
      <label class="block text-sm font-medium">Total Questions<input id="total-questions" disabled value="{{ $quiz->questions->count() }}" class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2"></label>
      <label class="block text-sm font-medium">Total Marks<input id="total-marks" disabled value="{{ $quiz->questions->sum('marks') }}" class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2"></label>
    </div>
    <div class="space-y-4"><h2 class="text-lg font-semibold">Settings</h2>
      @foreach(['show_answers'=>'Show Answers|Display correct answers after each question is attempted.','show_submission_history'=>'Show Submission History|Allow users to view their past quiz attempts.','shuffle_questions'=>'Shuffle Questions|Randomize the order of questions for each attempt.','enable_negative_marking'=>'Enable Negative Marking|Deduct marks for incorrect answers.','enable_proctoring'=>'Enable Proctoring|Require camera access and monitor for violations during the quiz.','enable_scheduling'=>'Enable Scheduling|Restrict when learners can start and submit this quiz.'] as $name => $copy)
        @php([$label, $description] = explode('|', $copy))<label class="flex cursor-pointer items-start justify-between gap-3 text-sm"><span><strong class="block">{{ $label }}</strong><small class="text-gray-500">{{ $description }}</small></span><span class="relative mt-1 inline-flex h-4 w-7 shrink-0"><input class="setting-toggle peer sr-only" type="checkbox" name="{{ $name }}" value="1" {{ old($name, $quiz->$name) ? 'checked' : '' }} data-target="{{ $name }}"><span aria-hidden="true" class="absolute inset-0 rounded-full bg-gray-200 transition peer-checked:bg-blue-600 after:absolute after:left-0.5 after:top-0.5 after:h-3 after:w-3 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-3"></span></span></label>
        @if($name === 'shuffle_questions')<label data-for="shuffle_questions" class="hidden block text-sm">Limit Questions To<input name="limit_questions_to" type="number" min="1" value="{{ old('limit_questions_to', $quiz->limit_questions_to) }}" class="mt-1 w-full rounded-lg border-gray-300"></label>@endif
        @if($name === 'enable_negative_marking')<label data-for="enable_negative_marking" class="hidden block text-sm">Marks to Deduct<input name="marks_to_cut" type="number" min="1" value="{{ old('marks_to_cut', $quiz->marks_to_cut) }}" class="mt-1 w-full rounded-lg border-gray-300"></label>@endif
        @if($name === 'enable_proctoring')<label data-for="enable_proctoring" class="hidden block text-sm">Max Violations<input name="max_violations" type="number" min="1" value="{{ old('max_violations', $quiz->max_violations) }}" class="mt-1 w-full rounded-lg border-gray-300"></label>@endif
        @if($name === 'enable_scheduling')<div data-for="enable_scheduling" class="hidden space-y-3"><label class="block text-sm">Schedule Start<input name="schedule_start" type="datetime-local" value="{{ old('schedule_start', optional($quiz->schedule_start)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300"></label><label class="block text-sm">Schedule End<input name="schedule_end" type="datetime-local" value="{{ old('schedule_end', optional($quiz->schedule_end)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300"></label></div>@endif
      @endforeach
    </div>
    <p id="quiz-autosave-error" class="hidden text-sm text-red-700" role="alert"></p>
    </div>
    @if($editing)
      <section id="question-bank-panel" class="hidden flex h-full flex-col">
        <div class="flex items-center gap-3 border-b border-gray-200 p-5 pb-3">
          <input id="question-bank-search" type="search" placeholder="Search" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm">
          <select id="question-bank-type" class="rounded-lg border-gray-300 text-sm"><option value="">All types</option><option value="single">Single choice</option><option value="multiple">Multiple choice</option><option value="user_input">User input</option><option value="open">Open ended</option></select>
          <button id="close-question-bank" type="button" aria-label="Close question bank" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100">×</button>
        </div>
        <div id="question-bank-list" class="flex-1 space-y-2 overflow-y-auto p-5">
          @forelse($questionBank as $question)
            <label data-type="{{ $question->question_type }}" data-question="{{ strtolower(strip_tags($question->question)) }}" class="bank-question-row flex cursor-pointer gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50"><input class="bank-question mt-1" type="checkbox" value="{{ $question->id }}"><span class="min-w-0"><strong class="block truncate text-sm">{{ \Illuminate\Support\Str::limit(strip_tags($question->question), 120) }}</strong><small class="text-gray-500">{{ $question->quiz->title }} · {{ $question->marks }} marks</small></span></label>
          @empty
            <p class="text-sm text-gray-500">No questions found.</p>
          @endforelse
        </div>
      </section>
    @endif
  </aside>
</form>
<template id="question-template">@include('admin.quizzes.question', ['i' => '__INDEX__', 'question' => ['type'=>'single','marks'=>1,'options'=>['',''],'correct_answer'=>0]])</template>
@php($questionBankData = $questionBank->map(fn ($question) => ['id' => $question->id, 'question' => $question->question, 'type' => $question->question_type, 'marks' => $question->marks, 'options' => $question->options, 'explanations' => $question->explanations, 'correct_answer' => $question->correct_answer, 'correct_answers' => $question->correct_answers, 'accepted_answers' => $question->accepted_answers])->values())
@vite('resources/js/quiz-question-editor.js')
@push('scripts')<script>
const quizForm = document.querySelector('#quizForm'), questions = document.querySelector('#questions'), template = document.querySelector('#question-template');
const bankQuestions = @json($questionBankData);
const editing = quizForm.dataset.new === '0';
const status = document.querySelector('#quiz-save-status');
const saveError = document.querySelector('#quiz-autosave-error');
let saving = false;

function setSaveStatus(state, message = '') {
  if (!status) return;
  status.classList.remove('bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-700', 'bg-red-50', 'text-red-700');
  const states = { saved: ['Saved', 'bg-emerald-50', 'text-emerald-700'], pending: ['Not saved', 'bg-amber-50', 'text-amber-700'], error: ['Not saved', 'bg-red-50', 'text-red-700'] };
  status.textContent = states[state][0];
  status.classList.add(states[state][1], states[state][2]);
  saveError.textContent = message;
  saveError.classList.toggle('hidden', !message);
}

function syncMarks() {
  const count = document.querySelectorAll('.quiz-question').length;
  document.querySelector('#total-questions').value = count;
  document.querySelector('#total-marks').value = [...document.querySelectorAll('.question-marks')].reduce((sum, input) => sum + (Number(input.value) || 0), 0);
  document.querySelector('#empty-questions')?.classList.toggle('hidden', count > 0);
}

function toggleQuestion(row) {
  const type = row.querySelector('.question-type').value;
  row.querySelector('.choice-fields').classList.toggle('hidden', !['single', 'multiple'].includes(type));
  row.querySelector('.user-input-fields').classList.toggle('hidden', type !== 'user_input');
}

function toggleSetting(input) {
  document.querySelectorAll('[data-for="' + input.dataset.target + '"]').forEach(el => el.classList.toggle('hidden', !input.checked));
}

function addQuestion(source = null) {
  const i = questions.querySelectorAll('.quiz-question').length;
  questions.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', i));
  const row = questions.lastElementChild;
  if (source) {
    row.querySelector('textarea[name$="[question]"]').value = source.question || '';
    row.querySelector('.question-type').value = source.type || 'single';
    row.querySelector('.question-marks').value = source.marks || 1;
    row.querySelectorAll('input[name*="[options]"]').forEach((input, index) => input.value = source.options?.[index] || '');
    row.querySelectorAll('input[name*="[explanations]"]').forEach((input, index) => input.value = source.explanations?.[index] || '');
    row.querySelector(`input[name$="[correct_answer]"][value="${source.correct_answer ?? 0}"]`).checked = true;
    row.querySelectorAll('input[name*="[correct_answers]"]').forEach(input => input.checked = (source.correct_answers || []).map(String).includes(input.value));
    row.querySelector('textarea[name*="[accepted_answers]"]').value = (source.accepted_answers || []).join('\n');
  }
  toggleQuestion(row);
  document.dispatchEvent(new CustomEvent('quiz-question-added', { detail: row }));
  syncMarks();
  row.scrollIntoView({ block: 'center', behavior: 'smooth' });
  markUnsaved();
}

async function saveQuiz() {
  if (!editing || saving || !quizForm.checkValidity()) return;
  saving = true;
  try {
    const response = await fetch(quizForm.action, {
      method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': quizForm.querySelector('[name="_token"]').value },
      credentials: 'same-origin', body: new FormData(quizForm),
    });
    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      throw new Error(data.message || 'Your changes could not be saved.');
    }
    setSaveStatus('saved');
    const title = quizForm.querySelector('#quiz-title').value.trim();
    const pageTitle = document.querySelector('.admin-page-header h1');
    if (pageTitle?.lastChild) pageTitle.lastChild.nodeValue = ' ' + title;
    document.title = title + ' � Skillspot';
  } catch (error) {
    setSaveStatus('error', error.message);
  } finally { saving = false; }
}

function markUnsaved() {
  if (editing) {
  setSaveStatus('pending');
  }
}

document.querySelector('#add-question').onclick = () => addQuestion();
document.addEventListener('click', event => {
  const remove = event.target.closest('.remove-question');
  if (!remove) return;
  remove.closest('.quiz-question').remove();
  syncMarks();
  markUnsaved();
});
document.addEventListener('change', event => {
  if (!quizForm.contains(event.target)) return;
  if (event.target.matches('.question-type')) toggleQuestion(event.target.closest('.quiz-question'));
  if (event.target.matches('.setting-toggle')) toggleSetting(event.target);
  syncMarks();
  if (event.target.name) markUnsaved();
});
quizForm.addEventListener('input', event => { if (event.target.name) markUnsaved(); });
document.addEventListener('click', event => {
  if (!event.target.closest('.save-question')) return;
  setSaveStatus('pending');
  saveQuiz();
});
document.querySelector('#save-quiz')?.addEventListener('click', () => { setSaveStatus('pending'); saveQuiz(); });
quizForm.addEventListener('submit', event => event.preventDefault());

document.querySelectorAll('.quiz-question').forEach(toggleQuestion);
document.querySelectorAll('.setting-toggle').forEach(toggleSetting);
syncMarks();

const bankButton = document.querySelector('#open-question-bank'), bankPanel = document.querySelector('#question-bank-panel'), settingsPanel = document.querySelector('#quiz-settings-panel');
function updateBankButton() {
  const selected = document.querySelectorAll('.bank-question:checked').length;
  bankButton.textContent = bankPanel && !bankPanel.classList.contains('hidden') ? `Add questions${selected ? ` (${selected})` : ''}` : 'Question bank';
  bankButton.disabled = bankPanel && !bankPanel.classList.contains('hidden') && !selected;
}
function openBank() { settingsPanel.classList.add('hidden'); bankPanel.classList.remove('hidden'); updateBankButton(); }
function closeBank() { bankPanel.classList.add('hidden'); settingsPanel.classList.remove('hidden'); updateBankButton(); }
bankButton?.addEventListener('click', () => {
  if (bankPanel.classList.contains('hidden')) return openBank();
  document.querySelectorAll('.bank-question:checked').forEach(input => {
    const question = bankQuestions.find(item => item.id === Number(input.value));
    if (question) addQuestion(question);
  });
  closeBank();
});
document.querySelector('#close-question-bank')?.addEventListener('click', closeBank);
document.querySelector('#question-bank-list')?.addEventListener('change', updateBankButton);
function filterBank() {
  const term = document.querySelector('#question-bank-search').value.toLowerCase(), type = document.querySelector('#question-bank-type').value;
  document.querySelectorAll('.bank-question-row').forEach(row => row.classList.toggle('hidden', !!((term && !row.dataset.question.includes(term)) || (type && row.dataset.type !== type))));
}
document.querySelector('#question-bank-search')?.addEventListener('input', filterBank);
document.querySelector('#question-bank-type')?.addEventListener('change', filterBank);

if (!editing) {
  let creating = false;
  document.querySelector('#quiz-title').addEventListener('blur', async () => {
    if (creating || !quizForm.querySelector('#quiz-title').value.trim()) return;
    creating = true;
    const response = await fetch(quizForm.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': quizForm.querySelector('[name="_token"]').value }, credentials: 'same-origin', body: new FormData(quizForm) });
    if (response.ok) { location.href = (await response.json()).edit_url; return; }
    creating = false;
    alert('Could not create the quiz. Check the required fields and try again.');
  });
}
</script>@endpush
@endsection
