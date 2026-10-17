@extends('layouts.student')
@section('title', 'Teaching tools — '.$course->title)
@section('page-title', 'Teaching tools')
@section('page-sub', $course->title.' · assignments and announcements')
@section('student-content')
<div class="mx-auto max-w-5xl space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <a href="{{ auth()->user()->hasRole(['admin','super-admin']) ? route('admin.courses.edit',$course) : (auth()->user()->instructorProfile ? route('instructor.courses.show',$course) : route('vendor.courses')) }}" class="text-sm font-semibold text-brand-700">← Back to course</a>
    <a href="{{ route('instructor.courses.preview', $course) }}" class="rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700">Preview as learner</a>
  </div>
  @if(session('success'))<div role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>@endif
  @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
  <section class="rounded-2xl border border-gray-100 bg-white p-5">
    <h2 class="text-lg font-bold text-gray-900">Add an assignment</h2>
    <p class="mt-1 text-sm text-gray-500">Learners submit privately. Passing grades count toward progress and certificates.</p>
    @if($course->sections->isEmpty())<p class="mt-4 text-sm text-amber-700">Create a course section before adding an assignment.</p>@else
    <form method="POST" action="{{ route('courses.teaching.assignments.store', [$course, $course->sections->first()]) }}" id="assignment-create" class="mt-4 grid gap-3">@csrf
      <label class="text-sm font-semibold">Section<select name="section" aria-label="Assignment section" class="mt-1 w-full rounded-lg border-gray-200" onchange="document.getElementById('assignment-create').action=this.value">@foreach($course->sections as $section)<option value="{{ route('courses.teaching.assignments.store', [$course,$section]) }}">{{ $section->title }}</option>@endforeach</select></label>
      <label class="text-sm font-semibold">Title<input name="title" required maxlength="255" class="mt-1 w-full rounded-lg border-gray-200"></label>
      <label class="text-sm font-semibold">Instructions<textarea name="instructions" required rows="4" class="mt-1 w-full rounded-lg border-gray-200"></textarea></label>
      <div class="grid gap-3 sm:grid-cols-2"><label class="text-sm font-semibold">Passing score (%)<input name="pass_score" type="number" min="1" max="100" value="70" required class="mt-1 w-full rounded-lg border-gray-200"></label><label class="text-sm font-semibold">Due date (optional)<input name="due_at" type="datetime-local" class="mt-1 w-full rounded-lg border-gray-200"></label></div>
      <button class="justify-self-start rounded-lg bg-brand-600 px-4 py-2 text-sm font-bold text-white">Add assignment</button>
    </form>@endif
  </section>
  <section class="rounded-2xl border border-gray-100 bg-white p-5">
    <h2 class="text-lg font-bold text-gray-900">Assignments and submissions</h2>
    <div class="mt-4 space-y-5">@forelse($course->sections->flatMap->lessons->filter(fn($lesson) => $lesson->assignment) as $lesson)
      <article class="rounded-xl border border-gray-200 p-4"><h3 class="font-semibold text-gray-900">{{ $lesson->title }}</h3><p class="text-xs text-gray-500">{{ $lesson->section->title }} · Pass {{ $lesson->assignment->pass_score }}%</p>
        <form method="POST" action="{{ route('courses.teaching.assignments.update', [$course,$lesson->assignment]) }}" class="mt-3 grid gap-2">@csrf @method('PUT')<input name="title" required value="{{ $lesson->title }}" class="rounded-lg border-gray-200 text-sm"><textarea name="instructions" required rows="3" class="rounded-lg border-gray-200 text-sm">{{ $lesson->assignment->instructions }}</textarea><input name="pass_score" type="number" min="1" max="100" required value="{{ $lesson->assignment->pass_score }}" class="w-32 rounded-lg border-gray-200 text-sm"><input name="due_at" type="datetime-local" value="{{ $lesson->assignment->due_at?->format('Y-m-d\TH:i') }}" class="rounded-lg border-gray-200 text-sm"><button class="justify-self-start text-sm font-bold text-brand-700">Save assignment</button></form>
        <div class="mt-4 border-t border-gray-100 pt-3">@forelse($lesson->assignment->submissions as $submission)<div class="mb-3 rounded-lg bg-gray-50 p-3 text-sm"><p class="font-semibold">{{ $submission->user?->name }} · {{ ucfirst($submission->status) }}</p>@if($submission->answer)<p class="mt-1 whitespace-pre-wrap">{{ $submission->answer }}</p>@endif @if($submission->file_path)<a class="text-brand-700 underline" href="{{ route('courses.teaching.submissions.file', [$course,$submission]) }}">Download submitted file</a>@endif<form method="POST" action="{{ route('courses.teaching.submissions.grade', [$course,$submission]) }}" class="mt-2 flex flex-wrap gap-2">@csrf @method('PATCH')<label>Score <input name="score" type="number" min="0" max="100" required value="{{ $submission->score }}" class="w-24 rounded-lg border-gray-200"></label><label class="flex-1">Feedback <input name="feedback" value="{{ $submission->feedback }}" class="w-full rounded-lg border-gray-200"></label><button class="rounded-lg bg-brand-600 px-3 py-2 font-bold text-white">Save grade</button></form></div>@empty<p class="text-sm text-gray-500">No submissions yet.</p>@endforelse</div>
      </article>@empty<p class="text-sm text-gray-500">No assignments yet.</p>@endforelse</div>
  </section>
  <section class="rounded-2xl border border-gray-100 bg-white p-5"><h2 class="text-lg font-bold text-gray-900">Announcements</h2><p class="mt-1 text-sm text-gray-500">Shown to enrolled learners in this course or batch.</p><form method="POST" action="{{ route('courses.teaching.announcements.store',$course) }}" class="mt-4 grid gap-2">@csrf<label class="text-sm">Title<input name="title" required maxlength="255" class="mt-1 w-full rounded-lg border-gray-200"></label><label class="text-sm">Message<textarea name="body" required rows="3" class="mt-1 w-full rounded-lg border-gray-200"></textarea></label><button class="justify-self-start rounded-lg bg-brand-600 px-4 py-2 text-sm font-bold text-white">Post announcement</button></form><div class="mt-5 divide-y divide-gray-100">@forelse($course->announcements as $announcement)<div class="py-3"><h3 class="font-semibold">{{ $announcement->title }}</h3><p class="whitespace-pre-wrap text-sm text-gray-600">{{ $announcement->body }}</p><p class="mt-1 text-xs text-gray-400">{{ $announcement->created_at->format('d M Y') }}</p></div>@empty<p class="py-3 text-sm text-gray-500">No announcements yet.</p>@endforelse</div></section>
  <section class="rounded-2xl border border-gray-100 bg-white p-5">
    <h2 class="text-lg font-bold text-gray-900">Quizzes</h2>
    <p class="mt-1 text-sm text-gray-500">Single choice, multiple choice, and manually graded open answers.</p>
    @if($course->sections->isNotEmpty())<form method="POST" action="{{ route('courses.teaching.quizzes.store', [$course,$course->sections->first()]) }}" id="quiz-create" class="mt-4 grid gap-3 rounded-xl border border-gray-200 p-4">@csrf
      <h3 class="font-semibold">Create quiz with first question</h3>
      <label class="text-sm font-semibold">Section<select class="mt-1 w-full rounded-lg border-gray-200" onchange="document.getElementById('quiz-create').action=this.value">@foreach($course->sections as $section)<option value="{{ route('courses.teaching.quizzes.store', [$course,$section]) }}">{{ $section->title }}</option>@endforeach</select></label>
      <label class="text-sm font-semibold">Quiz title<input name="quiz_title" required maxlength="255" class="mt-1 w-full rounded-lg border-gray-200"></label>
      <label class="text-sm font-semibold">Passing score (%)<input name="quiz_pass_score" required type="number" min="1" max="100" value="70" class="mt-1 w-full rounded-lg border-gray-200"></label>
      @include('studio.quiz-question-fields')
      <button class="justify-self-start rounded-lg bg-brand-600 px-4 py-2 text-sm font-bold text-white">Create quiz</button>
    </form>@endif
    <div class="mt-5 space-y-5">@forelse($course->sections->flatMap->lessons->filter(fn($lesson) => $lesson->quiz) as $lesson)
      @php $quiz = $lesson->quiz; @endphp
      <article class="rounded-xl border border-gray-200 p-4"><h3 class="font-semibold">{{ $quiz->title }}</h3><p class="text-xs text-gray-500">{{ $lesson->section->title }} · {{ $quiz->questions->count() }} questions · Pass {{ $quiz->pass_score }}%</p>
        <ol class="mt-3 list-inside list-decimal space-y-1 text-sm">@foreach($quiz->questions as $question)<li>{{ $question->question }} <span class="text-gray-500">({{ $question->question_type }})</span></li>@endforeach</ol>
        <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold text-brand-700">Add another question</summary><form method="POST" action="{{ route('courses.teaching.quizzes.questions.store', [$course,$quiz]) }}" class="mt-3 grid gap-3">@csrf @include('studio.quiz-question-fields')<button class="justify-self-start rounded-lg bg-brand-600 px-4 py-2 text-sm font-bold text-white">Add question</button></form></details>
        <div class="mt-4 border-t border-gray-100 pt-3"><h4 class="text-sm font-semibold">Open answers awaiting grading</h4>@forelse($quiz->attempts->where('status','pending') as $attempt)
          <form method="POST" action="{{ route('courses.teaching.quizzes.attempts.grade', [$course,$attempt]) }}" class="mt-3 grid gap-3 rounded-lg bg-gray-50 p-3 text-sm">@csrf @method('PATCH')<strong>{{ $attempt->user?->name }}</strong>@foreach($quiz->questions->where('question_type','open') as $question)<div><p class="font-semibold">{{ $question->question }}</p><p class="whitespace-pre-wrap">{{ $attempt->answers[$question->id] ?? '' }}</p><label class="mt-1 block">Grade<select name="open_results[{{ $question->id }}]" class="ml-2 rounded-lg border-gray-200"><option value="1">Correct</option><option value="0">Incorrect</option></select></label></div>@endforeach<label>Feedback<input name="feedback" class="mt-1 w-full rounded-lg border-gray-200"></label><button class="justify-self-start rounded-lg bg-brand-600 px-4 py-2 font-bold text-white">Save quiz grade</button></form>
        @empty<p class="mt-2 text-sm text-gray-500">No open answers waiting.</p>@endforelse</div>
      </article>@empty<p class="mt-3 text-sm text-gray-500">No quizzes yet.</p>@endforelse</div>
  </section>
</div>
@endsection
