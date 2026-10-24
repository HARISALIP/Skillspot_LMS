@extends('layouts.admin')
@section('page-title')@yield('header', 'Organization')@endsection
@section('admin-content')
  @if(session('success'))<div role="status" class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>@endif
  @if(session('error'))<div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif
  @yield('content')
@endsection
