@extends('layouts.student')
@section('title','My Certificates — Skillspot.in')
@section('page-title','My Certificates')
@section('page-sub','Your earned certificates')

@section('student-content')
@if($certificates->count())
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
  @foreach($certificates as $cert)
  <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-2xl border-2 border-yellow-200 shadow-sm p-6 card-hover">
    <div class="flex items-start justify-between mb-4">
      <span class="text-4xl">📜</span>
      <span class="text-xs bg-green-100 text-green-700 font-bold px-2.5 py-1 rounded-full">✅ Verified</span>
    </div>
    <h3 class="font-black text-gray-900 text-base mb-1 line-clamp-2">{{ $cert->course?->title }}</h3>
    <p class="text-xs text-gray-500 mb-4">Issued {{ $cert->issued_at?->format('d M Y') ?? $cert->created_at?->format('d M Y') }}</p>
    @if($cert->certificate_number)
    <p class="text-xs text-gray-400 font-mono mb-4">ID: {{ $cert->certificate_number }}</p>
    @endif
    <div class="flex gap-2">
      <a href="#" class="flex-1 text-center py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-bold rounded-xl text-xs transition">
        ⬇️ Download
      </a>
      <a href="#" class="flex-1 text-center py-2 bg-white border border-yellow-300 text-yellow-700 font-bold rounded-xl text-xs hover:bg-yellow-50 transition">
        🔗 Share
      </a>
    </div>
  </div>
  @endforeach
</div>
@else
<div class="text-center py-20">
  <div class="text-6xl mb-4">🏆</div>
  <h3 class="text-xl font-black text-gray-900 mb-2">No certificates yet</h3>
  <p class="text-gray-500 mb-6">Complete a course to earn your verified certificate!</p>
  <a href="{{ route('student.courses') }}" class="inline-block bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-2xl transition">My Courses →</a>
</div>
@endif
@endsection
