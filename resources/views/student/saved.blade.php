@extends('layouts.student')
@section('title','Saved — Skillspot.in')
@section('page-title','Saved Courses')
@section('page-sub','Your bookmarked courses')
@section('student-content')
<div class="text-center py-10">
  <div class="text-5xl mb-4">🔖</div>
  <h3 class="text-lg font-black text-gray-900 mb-2">No saved courses yet</h3>
  <p class="text-gray-500 mb-4">Browse our catalog and bookmark courses you want to take.</p>
  <a href="{{ route('student.browse') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition">Browse Courses →</a>
</div>
@endsection
