@extends('layouts.student')
@section('title','Progress — Skillspot.in')
@section('page-title','My Progress')
@section('page-sub','Track your learning journey')
@section('student-content')
<div class="text-center py-10">
  <div class="text-5xl mb-4">📊</div>
  <h3 class="text-lg font-black text-gray-900 mb-2">Progress tracking coming soon</h3>
  <p class="text-gray-500 mb-4">Check your enrolled courses for individual progress.</p>
  <a href="{{ route('student.courses') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition">My Courses →</a>
</div>
@endsection
