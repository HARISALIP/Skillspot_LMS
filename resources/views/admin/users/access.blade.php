@extends('layouts.admin')
@section('page-title', 'Portal Access — ' . $user->name)
@section('page-sub', 'Manage vendor portal access for this student')
@section('admin-content')
<div class="space-y-6 max-w-3xl">

  @if(session('success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>@endif

  {{-- User info --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white font-black text-xl flex-shrink-0">{{ strtoupper(substr($user->name,0,1)) }}</div>
    <div class="flex-1">
      <div class="font-bold text-gray-900">{{ $user->name }}</div>
      <div class="text-sm text-gray-500">{{ $user->email }} {{ $user->phone ? '· '.$user->phone : '' }}</div>
      <div class="flex items-center gap-2 mt-1">
        @foreach($user->roles as $r)<span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-purple-100 text-purple-700">{{ $r->name }}</span>@endforeach
      </div>
    </div>
    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-brand-600 hover:underline">← Edit User</a>
  </div>

  {{-- Portal access type --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">🔑 Portal Access Type</h2>
      <p class="text-xs text-gray-400 mt-0.5">Controls where this student lands after login</p>
    </div>
    <div class="p-5">
      <form method="POST" action="{{ route('admin.users.access.portal', $user) }}" class="flex items-center gap-3 flex-wrap">
        @csrf
        @foreach(['Skillspot_only'=>['🎓','Skillspot.in Only'],'vendor_only'=>['🏪','Vendor Portal Only'],'both'=>['🔁','Both Portals']] as $val=>[$ico,$lbl])
        <label class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition
          {{ $user->portal_access === $val ? 'border-brand-400 bg-brand-50 text-brand-700 font-semibold' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300' }}">
          <input type="radio" name="portal_access" value="{{ $val }}" {{ $user->portal_access === $val ? 'checked' : '' }} class="sr-only">
          {{ $ico }} {{ $lbl }}
        </label>
        @endforeach
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition">Save</button>
      </form>
    </div>
  </div>

  {{-- Add vendor access --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">➕ Add Vendor Portal Access</h2>
    </div>
    <div class="p-5">
      <form method="POST" action="{{ route('admin.users.access.add', $user) }}" class="flex gap-2 flex-wrap">
        @csrf
        <select name="vendor_id" required class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="">— Select vendor —</option>
          @foreach($allVendors as $v)
            <option value="{{ $v->id }}" {{ $user->vendorAccess->contains('id',$v->id) ? 'disabled' : '' }}>
              {{ $v->brand_name }} ({{ $v->status }}) {{ $user->vendorAccess->contains('id',$v->id) ? '✓ Already added' : '' }}
            </option>
          @endforeach
        </select>
        <input type="text" name="note" placeholder="Note (optional)" class="border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-40">
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition">Add</button>
      </form>
    </div>
  </div>

  {{-- Current vendor access list --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-900">🏪 Vendor Portals ({{ $user->vendorAccess->count() }})</h2>
    </div>
    @if($user->vendorAccess->count())
    <div class="divide-y divide-gray-50">
      @foreach($user->vendorAccess as $v)
      <div class="px-5 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm font-black" style="background: {{ $v->primary_color ?? '#2563eb' }}">{{ strtoupper(substr($v->brand_name,0,1)) }}</div>
          <div>
            <div class="text-sm font-semibold text-gray-900">{{ $v->brand_name }}</div>
            <div class="text-xs text-gray-400">{{ $v->slug }} · {{ ucfirst($v->status) }}</div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <a href="{{ $v->portalUrl() }}" target="_blank" class="text-xs text-brand-600 hover:underline">Visit Portal</a>
          <form method="POST" action="{{ route('admin.users.access.remove', [$user, $v]) }}" onsubmit="return confirm('Remove access?')">
            @csrf @method('DELETE')
            <button class="text-xs text-red-500 hover:text-red-700 font-semibold transition">Revoke</button>
          </form>
        </div>
      </div>
      @endforeach
    </div>
    @else
    <div class="px-5 py-8 text-center text-gray-400 text-sm">No vendor portals assigned yet.</div>
    @endif
  </div>

</div>

<script>
// Radio visual selection
document.querySelectorAll('[name=portal_access]').forEach(r => {
  r.addEventListener('change', () => {
    document.querySelectorAll('[name=portal_access]').forEach(i => {
      i.closest('label').className = i.closest('label').className
        .replace('border-brand-400 bg-brand-50 text-brand-700 font-semibold','border-gray-200 bg-white text-gray-600 hover:border-gray-300');
    });
    r.closest('label').className = r.closest('label').className
      .replace('border-gray-200 bg-white text-gray-600 hover:border-gray-300','border-brand-400 bg-brand-50 text-brand-700 font-semibold');
  });
});
</script>
@endsection
