@extends('layouts.vendor')
@section('title','Branding — Vendor Panel')
@section('header','Branding & Profile')
@section('content')
<div class="max-w-2xl space-y-6">
  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <!-- Preview -->
  <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
    @if($vendor->banner_image)
      <img src="{{ Storage::disk('public')->url($vendor->banner_image) }}" class="w-full h-32 object-cover">
    @else
      <div class="w-full h-32" style="background: linear-gradient(135deg, {{ $vendor->primary_color }}, {{ $vendor->accent_color }})"></div>
    @endif
    <div class="bg-white p-5 flex items-center gap-4">
      @if($vendor->logo)
        <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-white shadow-md -mt-10">
      @else
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-black -mt-10 border-2 border-white shadow-md" style="background: {{ $vendor->primary_color }}">{{ strtoupper(substr($vendor->brand_name,0,1)) }}</div>
      @endif
      <div>
        <div class="font-bold text-gray-900 text-lg">{{ $vendor->brand_name }}</div>
        <div class="text-sm text-gray-500">{{ $vendor->tagline }}</div>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
    @csrf @method('PUT')

    <div class="grid grid-cols-2 gap-4">
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name *</label>
        <input type="text" name="brand_name" value="{{ old('brand_name', $vendor->brand_name) }}" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
        <input type="text" name="tagline" value="{{ old('tagline', $vendor->tagline) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('description', $vendor->description) }}</textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
        <input type="url" name="website" value="{{ old('website', $vendor->website) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
        <div class="flex items-center gap-2">
          <input type="color" name="primary_color" value="{{ old('primary_color', $vendor->primary_color ?? '#2563eb') }}" class="w-10 h-10 rounded-lg border border-gray-200 p-1 cursor-pointer">
          <input type="text" value="{{ old('primary_color', $vendor->primary_color ?? '#2563eb') }}" class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono" oninput="document.querySelector('[name=primary_color]').value=this.value">
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
        <div class="flex items-center gap-2">
          <input type="color" name="accent_color" value="{{ old('accent_color', $vendor->accent_color ?? '#7c3aed') }}" class="w-10 h-10 rounded-lg border border-gray-200 p-1 cursor-pointer">
          <input type="text" value="{{ old('accent_color', $vendor->accent_color ?? '#7c3aed') }}" class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono" oninput="document.querySelector('[name=accent_color]').value=this.value">
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
        @if($vendor->logo)<img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-12 h-12 rounded-xl object-cover mb-2 border border-gray-200">@endif
        <input type="file" name="logo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Banner Image</label>
        @if($vendor->banner_image)<img src="{{ Storage::disk('public')->url($vendor->banner_image) }}" class="w-full h-16 rounded-xl object-cover mb-2 border border-gray-200">@endif
        <input type="file" name="banner_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium">
      </div>
    </div>

    <div class="pt-2">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">Save Branding</button>
    </div>
  </form>
</div>
@endsection
