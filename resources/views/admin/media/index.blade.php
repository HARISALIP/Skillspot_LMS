@extends('layouts.admin')
@section('title','Media Manager — Skillspot.in Admin')
@section('page-title','Media Manager')
@section('page-sub','Upload and manage all files stored on Cloudflare R2')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ❌ {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 text-xl">×</button>
</div>
@endif

{{-- Stats row --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  @php
    function fmtSize($bytes) {
      if($bytes>=1073741824) return number_format($bytes/1073741824,2).' GB';
      if($bytes>=1048576)    return number_format($bytes/1048576,1).' MB';
      if($bytes>=1024)       return round($bytes/1024,1).' KB';
      return $bytes.' B';
    }
  @endphp
  @foreach([
    ['🖼️','Images',  $stats['images'], 'bg-blue-50','text-blue-700'],
    ['🎬','Videos',  $stats['videos'], 'bg-purple-50','text-purple-700'],
    ['📄','Docs',    $stats['docs'],   'bg-orange-50','text-orange-700'],
    ['📁','Total',   $stats['total'],  'bg-green-50','text-green-700'],
    ['💾','Storage', fmtSize($stats['size']), 'bg-gray-50','text-gray-700'],
  ] as [$icon,$label,$val,$bg,$text])
  <div class="{{ $bg }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $icon }}</div>
    <div class="text-xl font-black {{ $text }}">{{ $val }}</div>
    <div class="text-xs font-semibold text-gray-500">{{ $label }}</div>
  </div>
  @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

  {{-- ── Upload panel ─────────────────────────────────────────────── --}}
  <div class="xl:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden sticky top-6">
      <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
        <h3 class="font-black text-gray-900 text-sm">⬆️ Upload File</h3>
      </div>
      <div class="p-5">
        {{-- Drop zone --}}
        <div id="dropZone"
             class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center cursor-pointer hover:border-brand-400 hover:bg-brand-50 transition mb-4"
             onclick="document.getElementById('fileInput').click()"
             ondragover="event.preventDefault();this.classList.add('border-brand-500','bg-brand-50')"
             ondragleave="this.classList.remove('border-brand-500','bg-brand-50')"
             ondrop="handleDrop(event)">
          <div class="text-4xl mb-2">☁️</div>
          <p class="text-sm font-semibold text-gray-600">Drop files here</p>
          <p class="text-xs text-gray-400 mt-1">or click to browse</p>
          <p class="text-xs text-gray-400 mt-2">Images, Videos, PDFs, Docs<br>Max {{ \App\Models\Setting::get('max_upload_mb',500) }}MB</p>
        </div>
        <input type="file" id="fileInput" class="hidden" multiple
               accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt"
               onchange="uploadFiles(this.files)">

        {{-- Folder select --}}
        <div class="mb-4">
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Upload to folder</label>
          <select id="uploadFolder" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="general">general</option>
            <option value="courses">courses</option>
            <option value="thumbnails">thumbnails</option>
            <option value="videos">videos</option>
            <option value="documents">documents</option>
            <option value="settings">settings</option>
          </select>
        </div>

        {{-- Visibility toggle --}}
        <label class="flex items-center gap-3 mb-4 cursor-pointer">
          <input type="checkbox" id="isPublic" checked class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
          <div>
            <div class="text-sm font-semibold text-gray-800">Public file</div>
            <div class="text-xs text-gray-400">Unchecked = private (signed URL)</div>
          </div>
        </label>

        {{-- Progress area --}}
        <div id="uploadProgress" class="hidden space-y-2"></div>
      </div>
    </div>
  </div>

  {{-- ── File grid ─────────────────────────────────────────────────── --}}
  <div class="xl:col-span-3">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

      {{-- Filters --}}
      <form method="GET" action="{{ route('admin.media') }}"
            class="flex flex-wrap gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
        <div class="relative flex-1 min-w-[150px]">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
          <input type="text" name="search" value="{{ request('search') }}" placeholder="Search files…"
                 class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
        </div>
        <select name="type" onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
          <option value="">All Types</option>
          @foreach(['image','video','pdf','document','other'] as $t)
          <option value="{{ $t }}" {{ request('type')===$t?'selected':'' }}>{{ ucfirst($t) }}s</option>
          @endforeach
        </select>
        <select name="folder" onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
          <option value="">All Folders</option>
          @foreach($folders as $folder)
          <option value="{{ $folder }}" {{ request('folder')===$folder?'selected':'' }}>{{ $folder }}</option>
          @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold">Search</button>
        @if(request()->hasAny(['search','type','folder']))
        <a href="{{ route('admin.media') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Clear</a>
        @endif
      </form>

      {{-- Grid --}}
      <div class="p-4">
        @if($files->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-4 2xl:grid-cols-6 gap-3" id="fileGrid">
          @foreach($files as $file)
          <div class="group relative bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden hover:border-brand-300 hover:shadow-md transition cursor-pointer"
               onclick="showFileDetail({{ $file->id }}, '{{ addslashes($file->original_name) }}', '{{ addslashes($file->public_url) }}', '{{ $file->type }}', '{{ $file->human_size }}', '{{ $file->extension }}', '{{ $file->created_at?->format('d M Y') }}')">

            {{-- Thumbnail --}}
            <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
              @if($file->type === 'image' && $file->public_url)
                <img src="{{ $file->public_url }}" alt="{{ $file->original_name }}"
                     class="w-full h-full object-cover" loading="lazy"
                     onerror="this.parentElement.innerHTML='<span class=\'text-4xl\'>🖼️</span>'">
              @elseif($file->type === 'video')
                <div class="flex flex-col items-center">
                  <span class="text-4xl">🎬</span>
                  <span class="text-xs text-gray-500 mt-1">{{ strtoupper($file->extension) }}</span>
                </div>
              @else
                <div class="flex flex-col items-center">
                  <span class="text-4xl">{{ $file->icon }}</span>
                  <span class="text-xs text-gray-500 mt-1">{{ strtoupper($file->extension) }}</span>
                </div>
              @endif
            </div>

            {{-- Info --}}
            <div class="p-2">
              <p class="text-xs font-semibold text-gray-700 truncate">{{ $file->original_name }}</p>
              <p class="text-xs text-gray-400">{{ $file->human_size }}</p>
            </div>

            {{-- Delete btn (hover) --}}
            <form method="POST" action="{{ route('admin.media.destroy', $file->id) }}"
                  onsubmit="return confirm('Delete file?')"
                  class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition"
                  onclick="event.stopPropagation()">
              @csrf @method('DELETE')
              <button class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs flex items-center justify-center shadow">×</button>
            </form>

            {{-- Public indicator --}}
            @if(!$file->is_public)
            <div class="absolute top-1.5 left-1.5 bg-gray-800/70 text-white text-xs px-1.5 py-0.5 rounded-lg">🔒</div>
            @endif
          </div>
          @endforeach
        </div>
        @else
        <div class="text-center py-16 text-gray-400">
          <div class="text-5xl mb-3">📂</div>
          <p class="font-medium">No files yet</p>
          <p class="text-sm mt-1">Upload files using the panel on the left</p>
        </div>
        @endif
      </div>

      {{-- Pagination --}}
      @if($files->hasPages())
      <div class="px-5 py-4 border-t border-gray-100">{{ $files->withQueryString()->links('pagination::tailwind') }}</div>
      @else
      <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">{{ $files->total() }} files</div>
      @endif
    </div>
  </div>
</div>

{{-- File detail modal --}}
<div id="fileDetailModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="font-black text-gray-900" id="modalFileName">File Details</h3>
      <button onclick="document.getElementById('fileDetailModal').classList.add('hidden')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6">
      <div id="modalPreview" class="mb-4 text-center"></div>
      <div class="space-y-2 text-sm mb-5">
        <div class="flex gap-3"><span class="text-gray-400 w-20 flex-shrink-0">Type</span><span id="modalType" class="font-semibold text-gray-900"></span></div>
        <div class="flex gap-3"><span class="text-gray-400 w-20 flex-shrink-0">Size</span><span id="modalSize" class="font-semibold text-gray-900"></span></div>
        <div class="flex gap-3"><span class="text-gray-400 w-20 flex-shrink-0">Extension</span><span id="modalExt" class="font-semibold text-gray-900 uppercase"></span></div>
        <div class="flex gap-3"><span class="text-gray-400 w-20 flex-shrink-0">Uploaded</span><span id="modalDate" class="font-semibold text-gray-900"></span></div>
      </div>
      {{-- URL copy --}}
      <div class="mb-4">
        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">File URL</label>
        <div class="flex gap-2">
          <input type="text" id="modalUrl" readonly
                 class="flex-1 px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 text-gray-700 text-xs font-mono focus:outline-none">
          <button onclick="copyUrl()"
                  class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition" id="copyBtn">📋 Copy</button>
        </div>
      </div>
      <div class="flex gap-3">
        <a id="modalOpenBtn" href="#" target="_blank"
           class="flex-1 text-center py-2.5 bg-brand-50 hover:bg-brand-100 text-brand-700 rounded-xl text-sm font-semibold transition">
          🔗 Open File
        </a>
        <button onclick="document.getElementById('fileDetailModal').classList.add('hidden')"
                class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-semibold transition">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
const uploadUrl = '{{ route('admin.media.upload') }}';
const csrf      = '{{ csrf_token() }}';

// Drop handler
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').classList.remove('border-brand-500','bg-brand-50');
  uploadFiles(e.dataTransfer.files);
}

// Upload files
async function uploadFiles(fileList) {
  const folder   = document.getElementById('uploadFolder').value;
  const isPublic = document.getElementById('isPublic').checked ? '1' : '0';
  const progress = document.getElementById('uploadProgress');
  progress.classList.remove('hidden');

  for (const file of fileList) {
    const id   = 'prog-' + Date.now() + Math.random();
    const item = document.createElement('div');
    item.id    = id;
    item.className = 'bg-gray-100 rounded-xl p-3';
    item.innerHTML = `
      <div class="flex items-center gap-2 mb-1.5">
        <span class="text-sm truncate flex-1 font-medium">${file.name}</span>
        <span class="text-xs text-gray-400" id="${id}-status">Uploading…</span>
      </div>
      <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
        <div class="bg-brand-500 h-full rounded-full transition-all duration-300" id="${id}-bar" style="width:0%"></div>
      </div>`;
    progress.prepend(item);

    const formData = new FormData();
    formData.append('file',      file);
    formData.append('folder',    folder);
    formData.append('is_public', isPublic);
    formData.append('_token',    csrf);

    try {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', uploadUrl);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
          const pct = Math.round(e.loaded / e.total * 100);
          document.getElementById(id+'-bar').style.width = pct + '%';
        }
      };
      xhr.onload = () => {
        const res = JSON.parse(xhr.responseText);
        if (res.success) {
          document.getElementById(id+'-status').textContent = '✅ Done';
          document.getElementById(id+'-bar').classList.replace('bg-brand-500','bg-green-500');
          document.getElementById(id+'-bar').style.width = '100%';
          // Add to grid
          prependToGrid(res);
          setTimeout(() => item.remove(), 3000);
        } else {
          document.getElementById(id+'-status').textContent = '❌ ' + (res.error || 'Failed');
          document.getElementById(id+'-bar').classList.replace('bg-brand-500','bg-red-500');
        }
      };
      xhr.onerror = () => {
        document.getElementById(id+'-status').textContent = '❌ Network error';
      };
      xhr.send(formData);
    } catch(e) {
      document.getElementById(id+'-status').textContent = '❌ Error';
    }
  }
}

// Prepend uploaded file to grid
function prependToGrid(file) {
  const grid = document.getElementById('fileGrid');
  if (!grid) { location.reload(); return; }
  const icons = {image:'🖼️',video:'🎬',pdf:'📄',document:'📝',other:'📎'};
  const thumb = file.type === 'image' && file.url
    ? `<img src="${file.url}" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML='<span class=\\'text-4xl\\'>🖼️</span>'">`
    : `<div class='flex flex-col items-center'><span class='text-4xl'>${icons[file.type]||'📎'}</span><span class='text-xs text-gray-500 mt-1'>${file.type.toUpperCase()}</span></div>`;
  const div = document.createElement('div');
  div.className = 'group relative bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden hover:border-brand-300 hover:shadow-md transition cursor-pointer';
  div.innerHTML = `
    <div class='aspect-square bg-gray-100 flex items-center justify-center overflow-hidden'>${thumb}</div>
    <div class='p-2'><p class='text-xs font-semibold text-gray-700 truncate'>${file.name}</p><p class='text-xs text-gray-400'>${file.size}</p></div>`;
  grid.prepend(div);
}

// File detail modal
function showFileDetail(id, name, url, type, size, ext, date) {
  document.getElementById('modalFileName').textContent = name;
  document.getElementById('modalType').textContent     = type;
  document.getElementById('modalSize').textContent     = size;
  document.getElementById('modalExt').textContent      = ext;
  document.getElementById('modalDate').textContent     = date;
  document.getElementById('modalUrl').value            = url;
  document.getElementById('modalOpenBtn').href         = url;

  const preview = document.getElementById('modalPreview');
  if (type === 'image' && url) {
    preview.innerHTML = `<img src="${url}" class="max-h-48 mx-auto rounded-xl object-contain" onerror="this.remove()">`;
  } else if (type === 'video' && url) {
    preview.innerHTML = `<video src="${url}" controls class="max-h-48 mx-auto rounded-xl w-full"></video>`;
  } else {
    preview.innerHTML = `<div class="text-6xl">${{image:'🖼️',video:'🎬',pdf:'📄',document:'📝',other:'📎'}[type]||'📎'}</div>`;
  }

  document.getElementById('fileDetailModal').classList.remove('hidden');
}

// Copy URL
function copyUrl() {
  const url = document.getElementById('modalUrl').value;
  navigator.clipboard.writeText(url).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.textContent = '✅ Copied!';
    setTimeout(() => btn.textContent = '📋 Copy', 2000);
  });
}

// Close modal on backdrop
document.getElementById('fileDetailModal').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush
