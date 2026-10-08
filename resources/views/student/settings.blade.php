@extends('layouts.student')
@section('title','Settings — Skillspot.in')
@section('page-title','Settings')
@section('page-sub','Account preferences')
@section('student-content')
<div class="text-center py-10">
  <div class="text-5xl mb-4">⚙️</div>
  <h3 class="text-lg font-black text-gray-900 mb-2">Settings</h3>
  <p class="text-gray-500 mb-4">Manage your profile and password from the Profile section.</p>
  <a href="{{ route('student.profile') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl transition">Go to Profile →</a>
</div>
@endsection
