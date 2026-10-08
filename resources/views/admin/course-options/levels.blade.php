@extends('layouts.admin')
@section('title','Course Levels — Skillspot.in Admin')
@section('page-title','Course Levels')
@section('page-sub','Manage levels: Beginner, Intermediate, Advanced etc.')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 text-xl">×</button>
</div>
@endif
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
      <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
        <h3 class="font-black text-gray-900 text-sm">➕ Add Level</h3>
      </div>
      <form method="POST" action="{{ route('admin.courses.levels.store') }}" class="p-5 space-y-4">
        @csrf
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Name <span class="text-red-400">*</span></label>
          <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Advanced"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Description</label>
          <input type="text" name="description" value="{{ old('description') }}" placeholder="e.g. Strong foundation required"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
        </div>
        <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-sm transition">
          Add Level
        </button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
        <h3 class="font-black text-gray-900 text-sm">All Levels ({{ $items->count() }})</h3>
      </div>
      <div class="divide-y divide-gray-50">
        @forelse($items as $item)
        <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition group">
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900 text-sm">{{ $item->name }}</div>
            @if($item->description)
            <div class="text-xs text-gray-400">{{ $item->description }}</div>
            @endif
            <div class="text-xs text-gray-400 font-mono">{{ $item->slug }}</div>
          </div>
          <form method="POST" action="{{ route('admin.courses.levels.toggle', $item->id) }}" class="flex-shrink-0">
            @csrf @method('PATCH')
            <button type="submit"
                    class="text-xs font-bold px-2.5 py-1 rounded-lg transition
                           {{ $item->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
              {{ $item->is_active ? '✅ Active' : '⏸ Off' }}
            </button>
          </form>
          <button onclick="openEditLevel({{ $item->id }},'{{ addslashes($item->name) }}','{{ addslashes($item->description ?? '') }}')"
                  class="p-1.5 rounded-lg hover:bg-brand-50 text-gray-400 hover:text-brand-600 transition opacity-0 group-hover:opacity-100">✏️</button>
          <form method="POST" action="{{ route('admin.courses.levels.destroy', $item->id) }}"
                onsubmit="return confirm('Delete level?')" class="opacity-0 group-hover:opacity-100">
            @csrf @method('DELETE')
            <button class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition">🗑️</button>
          </form>
        </div>
        @empty
        <div class="px-5 py-10 text-center text-gray-400 text-sm">No levels yet.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<div id="editLevelModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
    <h3 class="font-black text-gray-900 mb-4">Edit Level</h3>
    <form method="POST" id="editLevelForm" class="space-y-4">
      @csrf @method('PUT')
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Name</label>
        <input type="text" name="name" id="editLevelName" required
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Description</label>
        <input type="text" name="description" id="editLevelDesc"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
      </div>
      <div class="flex gap-3">
        <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-sm">Save</button>
        <button type="button" onclick="document.getElementById('editLevelModal').classList.add('hidden')"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-sm">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
function openEditLevel(id, name, desc) {
  document.getElementById('editLevelName').value = name;
  document.getElementById('editLevelDesc').value = desc;
  document.getElementById('editLevelForm').action = '/admin/courses/levels/' + id;
  document.getElementById('editLevelModal').classList.remove('hidden');
}
document.getElementById('editLevelModal')?.addEventListener('click', function(e) { if(e.target===this) this.classList.add('hidden'); });
</script>
@endpush
