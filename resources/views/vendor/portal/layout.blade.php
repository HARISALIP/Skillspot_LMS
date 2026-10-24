@extends('layouts.student')
@section('page-title', $vendor->brand_name)
@section('page-sub', 'Organization learning')
@push('styles')<style>.hero-bg{background:linear-gradient(135deg,{{ $vendor->primary_color ?? '#2563eb' }},{{ $vendor->accent_color ?? '#7c3aed' }})}</style>@endpush
@section('student-content')
  @if(session('success'))<div role="status" class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>@endif
  @yield('content')
@endsection
