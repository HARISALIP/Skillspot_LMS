@extends('layouts.admin')
@section('title', (isset($vendor) ? 'Edit' : 'Add') . ' Vendor — Skillspot.in LMS')
@section('admin-content')
<div class="p-6 max-w-3xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">{{ isset($vendor) ? 'Edit Vendor' : 'Add Vendor' }}</h1>
  </div>

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4 text-sm text-red-800">
      <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ isset($vendor) ? route('admin.vendors.update',$vendor) : route('admin.vendors.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if(isset($vendor)) @method('PUT') @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
      <h2 class="font-semibold text-gray-900 text-base">Basic Info</h2>
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Brand Name *</label>
          <input type="text" name="brand_name" value="{{ old('brand_name', $vendor->brand_name ?? '') }}" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
          <input type="text" name="tagline" value="{{ old('tagline', $vendor->tagline ?? '') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('description', $vendor->description ?? '') }}</textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Owner User *</label>
          <select name="user_id" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            @foreach($users as $u)
              <option value="{{ $u->id }}" {{ old('user_id', $vendor->user_id ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
          <select name="status" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            @foreach(['pending','active','suspended'] as $s)
              <option value="{{ $s }}" {{ old('status', $vendor->status ?? 'pending') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
          <input type="text" name="plan" value="{{ old('plan', $vendor->plan ?? 'free') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" name="email" value="{{ old('email', $vendor->email ?? '') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $vendor->phone ?? '') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
          <input type="url" name="website" value="{{ old('website', $vendor->website ?? '') }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
      <h2 class="font-semibold text-gray-900 text-base">Branding</h2>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
          <div class="flex items-center gap-2">
            <input type="color" name="primary_color" value="{{ old('primary_color', $vendor->primary_color ?? '#2563eb') }}" class="w-10 h-10 rounded-lg border border-gray-200 p-1 cursor-pointer">
            <input type="text" id="primaryColorText" value="{{ old('primary_color', $vendor->primary_color ?? '#2563eb') }}" class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono" oninput="document.querySelector('[name=primary_color]').value=this.value">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
          <div class="flex items-center gap-2">
            <input type="color" name="accent_color" value="{{ old('accent_color', $vendor->accent_color ?? '#7c3aed') }}" class="w-10 h-10 rounded-lg border border-gray-200 p-1 cursor-pointer">
            <input type="text" id="accentColorText" value="{{ old('accent_color', $vendor->accent_color ?? '#7c3aed') }}" class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono" oninput="document.querySelector('[name=accent_color]').value=this.value">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
          @if(isset($vendor) && $vendor->logo)
            <img src="{{ Storage::disk('public')->url($vendor->logo) }}" class="w-16 h-16 rounded-xl object-cover mb-2 border border-gray-200">
          @endif
          <input type="file" name="logo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium hover:file:bg-brand-100">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Banner Image</label>
          @if(isset($vendor) && $vendor->banner_image)
            <img src="{{ Storage::disk('public')->url($vendor->banner_image) }}" class="w-full h-20 rounded-xl object-cover mb-2 border border-gray-200">
          @endif
          <input type="file" name="banner_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium hover:file:bg-brand-100">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
          @if(isset($vendor) && $vendor->favicon)
            <img src="{{ Storage::disk('public')->url($vendor->favicon) }}" class="w-8 h-8 rounded mb-2 border border-gray-200">
          @endif
          <input type="file" name="favicon" accept="image/*" class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-medium hover:file:bg-brand-100">
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition">
        {{ isset($vendor) ? 'Update Vendor' : 'Create Vendor' }}
      </button>
      <a href="{{ route('admin.vendors') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Cancel</a>
    </div>
  </form>
</div>
@endsection
