{{--
  Media Picker Component
  Props:
    name      — input field name
    value     — current value (URL)
    label     — field label
    type      — image | video | pdf | '' (all)
    folder    — R2 folder (default: general)
    cropRatio — e.g. '200:60' or '1:1' (only for images, enables crop)
--}}

@props([
  'name'      => 'media_url',
  'value'     => '',
  'label'     => 'Select File',
  'type'      => '',
  'folder'    => 'general',
  'cropRatio' => '',
])

@php
  $pickerId  = 'picker_'.str_replace(['.','[',']',':'],['_','_','_','_'], $name).'_'.rand(1000,9999);
  $inputId   = 'input_'.$pickerId;
  $previewId = 'prev_'.$pickerId;
  $modalId   = 'modal_'.$pickerId;
  $cropId    = 'crop_'.$pickerId;
  $canCrop   = !empty($cropRatio) && $type === 'image';
  $accept    = match($type) {
    'image' => 'image/*',
    'video' => 'video/*',
    'pdf'   => 'application/pdf',
    default => 'image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt',
  };
  [$cropW, $cropH] = $canCrop ? array_map('intval', explode(':', $cropRatio)) : [0,0];
  $cropAspect = ($cropW && $cropH) ? ($cropW / $cropH) : 0;
@endphp

<div class="media-picker-wrap" id="{{ $pickerId }}-wrap">
  @if($label)
  <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">{{ $label }}</label>
  @endif

  <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" value="{{ old($name, $value) }}">

  <div class="flex items-start gap-3">
    {{-- Preview --}}
    <div id="{{ $previewId }}"
         class="w-24 h-24 flex-shrink-0 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
      @if($value)
        @if($type === 'image' || preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $value))
          <img src="{{ $value }}" class="w-full h-full object-{{ $cropRatio === '1:1' ? 'cover' : 'contain' }}" alt=""
               onerror="this.parentElement.innerHTML='🖼️'">
        @elseif($type === 'video')
          <span class="text-3xl">🎬</span>
        @else
          <span class="text-3xl">📄</span>
        @endif
      @else
        <span class="text-3xl">{{ $type === 'image' ? '🖼️' : ($type === 'video' ? '🎬' : '📎') }}</span>
      @endif
    </div>

    {{-- Controls --}}
    <div class="flex-1 space-y-2">
      {{-- URL input --}}
      <input type="text" id="{{ $inputId }}-url"
             value="{{ old($name, $value) }}"
             placeholder="Paste URL or pick / upload"
             class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 transition"
             oninput="mpSetValue('{{ $pickerId }}', this.value)">

      <div class="flex flex-wrap gap-2">
        {{-- Pick from library --}}
        <button type="button"
                onclick="openMediaPicker('{{ $modalId }}','{{ $pickerId }}','{{ $type }}','{{ $folder }}')"
                class="flex items-center gap-1 px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 rounded-xl text-xs font-bold transition">
          📁 Library
        </button>

        {{-- Upload (with crop if enabled) --}}
        <label class="flex items-center gap-1 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-xl text-xs font-bold transition cursor-pointer">
          ⬆️ Upload{{ $canCrop ? ' & Crop' : '' }}
          <input type="file" class="hidden" accept="{{ $accept }}"
                 onchange="{{ $canCrop ? 'mpOpenCrop(this,\''.$pickerId.'\','.$cropAspect.',\''.$folder.'\')' : 'mpDirectUpload(this,\''.$pickerId.'\',\''.$folder.'\')' }}">
        </label>

        {{-- Clear --}}
        <button type="button"
                onclick="mpSetValue('{{ $pickerId }}','')"
                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-bold transition">
          ✕
        </button>
      </div>

      @if($canCrop)
      <p class="text-xs text-gray-400">🖼️ Recommended: <strong>{{ $cropW }}×{{ $cropH }}px</strong> — crop tool opens after selecting</p>
      @endif
    </div>
  </div>
</div>

{{-- Crop modal (only for image pickers with cropRatio) --}}
@if($canCrop)
<div id="{{ $cropId }}-modal" class="hidden fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="font-black text-gray-900">✂️ Crop Image</h3>
        <p class="text-xs text-gray-400 mt-0.5">Recommended size: {{ $cropW }}×{{ $cropH }}px · Ratio {{ $cropRatio }}</p>
      </div>
      <button onclick="mpCropCancel('{{ $cropId }}')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <div class="p-4 bg-gray-900 max-h-96 overflow-hidden flex items-center justify-center">
      <img id="{{ $cropId }}-img" src="" alt="" class="max-w-full max-h-80" style="display:none">
    </div>
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-4">
      <div class="text-xs text-gray-400">Drag to position · Scroll to zoom</div>
      <div class="flex gap-3">
        <button type="button" onclick="mpCropCancel('{{ $cropId }}')"
                class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-bold transition">
          Cancel
        </button>
        <button type="button" onclick="mpCropApply('{{ $cropId }}','{{ $pickerId }}','{{ $folder }}')"
                class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-bold transition flex items-center gap-2" id="{{ $cropId }}-apply">
          ✂️ Crop & Upload
        </button>
      </div>
    </div>
  </div>
</div>
@endif

{{-- Media picker modal --}}
<div id="{{ $modalId }}"
     class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
      <h3 class="font-black text-gray-900">📁 Media Library</h3>
      <div class="flex items-center gap-3">
        <input type="text" placeholder="Search…" id="{{ $modalId }}-search"
               class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-40"
               oninput="mpSearch('{{ $modalId }}')">
        <select id="{{ $modalId }}-type" onchange="mpSearch('{{ $modalId }}')"
                class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none cursor-pointer">
          <option value="{{ $type }}">{{ $type ? ucfirst($type).'s' : 'All Types' }}</option>
          @if(empty($type))
          <option value="image">Images</option>
          <option value="video">Videos</option>
          <option value="pdf">PDFs</option>
          @endif
        </select>
        <button onclick="document.getElementById('{{ $modalId }}').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-2xl ml-2">×</button>
      </div>
    </div>
    <div class="flex-1 overflow-y-auto p-4">
      <div id="{{ $modalId }}-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
        <div class="col-span-full text-center py-10 text-gray-400 text-sm">Loading…</div>
      </div>
      <div id="{{ $modalId }}-loadmore" class="text-center mt-4 hidden">
        <button onclick="mpLoadMore('{{ $modalId }}')"
                class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Load more</button>
      </div>
    </div>
    <div class="flex-shrink-0 border-t border-gray-100 px-6 py-3 bg-gray-50 flex items-center gap-3">
      <label class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-bold cursor-pointer transition">
        ⬆️ Upload New
        <input type="file" class="hidden" accept="{{ $accept }}" multiple
               onchange="mpDirectUpload(this,'{{ $pickerId }}','{{ $folder }}',true)">
      </label>
      <span class="text-xs text-gray-400">or pick from library below</span>
    </div>
  </div>
</div>

@once
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endpush
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
const _mpApiUrl    = '{{ route('admin.media.list') }}';
const _mpUploadUrl = '{{ route('admin.media.upload') }}';
const _mpCsrf      = '{{ csrf_token() }}';
const _mpState     = {};
let   _mpCropper   = null;
let   _mpCropFile  = null;
let   _mpCropPicker= null;
let   _mpCropFolder= null;

// ── Open picker modal ──────────────────────────────────────────────────
function openMediaPicker(modalId, pickerId, type, folder) {
  _mpState[modalId] = { page:1, type, folder, pickerId, nextPageUrl:null };
  document.getElementById(modalId).classList.remove('hidden');
  mpLoad(modalId, true);
}

function mpSearch(modalId) {
  _mpState[modalId].page = 1;
  mpLoad(modalId, true);
}

async function mpLoad(modalId, reset=false) {
  const st     = _mpState[modalId];
  const search = document.getElementById(modalId+'-search')?.value || '';
  const type   = document.getElementById(modalId+'-type')?.value   || st.type;
  const grid   = document.getElementById(modalId+'-grid');
  if (reset) grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400 text-sm">Loading…</div>';

  const params = new URLSearchParams({ type, search, page: st.page });
  if (st.folder) params.append('folder', st.folder);

  const res  = await fetch(_mpApiUrl + '?' + params);
  const data = await res.json();

  if (reset) grid.innerHTML = '';
  const icons = {image:'🖼️',video:'🎬',pdf:'📄',document:'📝',other:'📎'};

  if (!data.data.length && reset) {
    grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-400 text-sm">No files found</div>';
    return;
  }
  data.data.forEach(f => {
    const div   = document.createElement('div');
    div.className = 'cursor-pointer rounded-xl border-2 border-transparent hover:border-brand-400 overflow-hidden bg-gray-50 transition';
    div.onclick   = () => { mpSetValue(st.pickerId, f.url); document.getElementById(modalId).classList.add('hidden'); };
    const thumb   = f.type === 'image' && f.url
      ? `<img src="${f.url}" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.innerHTML='<span class=\\'text-3xl\\'>🖼️</span>'">`
      : `<div class='flex flex-col items-center gap-1'><span class='text-3xl'>${icons[f.type]||'📎'}</span><span class='text-xs text-gray-400'>${f.extension?.toUpperCase()}</span></div>`;
    div.innerHTML = `<div class='aspect-square bg-gray-100 flex items-center justify-center overflow-hidden'>${thumb}</div><div class='p-1.5'><p class='text-xs text-gray-700 truncate'>${f.name}</p><p class='text-xs text-gray-400'>${f.size}</p></div>`;
    grid.appendChild(div);
  });
  _mpState[modalId].nextPageUrl = data.next_page_url;
  const lm = document.getElementById(modalId+'-loadmore');
  if (lm) lm.classList.toggle('hidden', !data.next_page_url);
}

function mpLoadMore(modalId) {
  _mpState[modalId].page++;
  mpLoad(modalId, false);
}

// ── Set value + update preview ─────────────────────────────────────────
function mpSetValue(pickerId, url) {
  const wrap     = document.getElementById(pickerId+'-wrap');
  if (!wrap) return;
  const hidden   = document.getElementById('input_'+pickerId);
  const urlInput = document.getElementById('input_'+pickerId+'-url');
  const preview  = document.getElementById('prev_'+pickerId);
  if (hidden)   hidden.value   = url;
  if (urlInput) urlInput.value = url;
  if (preview) {
    if (url && /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url)) {
      preview.innerHTML = `<img src="${url}" class="w-full h-full object-contain" onerror="this.parentElement.innerHTML='🖼️'">`;
    } else if (url && /\.(mp4|webm|ogg|mov)$/i.test(url)) {
      preview.innerHTML = '🎬';
    } else if (url) {
      preview.innerHTML = '📄';
    } else {
      preview.innerHTML = '📎';
      if (hidden)   hidden.value   = '';
      if (urlInput) urlInput.value = '';
    }
  }
}

// ── Direct upload (no crop) ────────────────────────────────────────────
async function mpDirectUpload(input, pickerId, folder, reloadPicker=false) {
  const files = input.files;
  if (!files.length) return;
  for (const file of files) {
    await mpDoUpload(file, pickerId, folder);
  }
  input.value = '';
  if (reloadPicker) {
    Object.keys(_mpState).forEach(mid => mpLoad(mid, true));
  }
}

// ── Open crop modal ────────────────────────────────────────────────────
function mpOpenCrop(input, pickerId, aspectRatio, folder) {
  const file = input.files[0];
  if (!file) return;
  input.value = '';

  _mpCropFile   = file;
  _mpCropPicker = pickerId;
  _mpCropFolder = folder;

  const cropId  = 'crop_'+pickerId;
  const cropImg = document.getElementById(cropId+'-img');
  const reader  = new FileReader();

  reader.onload = (e) => {
    cropImg.src   = e.target.result;
    cropImg.style.display = 'block';
    document.getElementById(cropId+'-modal').classList.remove('hidden');

    if (_mpCropper) _mpCropper.destroy();
    _mpCropper = new Cropper(cropImg, {
      aspectRatio : aspectRatio || NaN,
      viewMode    : 1,
      dragMode    : 'move',
      autoCropArea: 1,
      guides      : true,
      background  : true,
      movable     : true,
      zoomable    : true,
      rotatable   : false,
      scalable    : false,
    });
  };
  reader.readAsDataURL(file);
}

// ── Apply crop & upload ────────────────────────────────────────────────
async function mpCropApply(cropId, pickerId, folder) {
  if (!_mpCropper) return;
  const applyBtn = document.getElementById(cropId+'-apply');
  const origText = applyBtn.innerHTML;
  applyBtn.innerHTML = '⏳ Uploading…';
  applyBtn.disabled  = true;

  const canvas = _mpCropper.getCroppedCanvas({ maxWidth: 1200, maxHeight: 1200, fillColor: '#fff' });
  const ext    = (_mpCropFile?.name || 'image.png').split('.').pop().toLowerCase();
  const mime   = ext === 'jpg' || ext === 'jpeg' ? 'image/jpeg' : 'image/png';

  canvas.toBlob(async (blob) => {
    const name     = (_mpCropFile?.name || 'cropped.png').replace(/\.[^.]+$/, '') + '_cropped.' + (mime === 'image/jpeg' ? 'jpg' : 'png');
    const file     = new File([blob], name, { type: mime });
    const url      = await mpDoUpload(file, pickerId, folder);
    document.getElementById(cropId+'-modal').classList.add('hidden');
    if (_mpCropper) { _mpCropper.destroy(); _mpCropper = null; }
    applyBtn.innerHTML = origText;
    applyBtn.disabled  = false;
  }, mime, 0.92);
}

function mpCropCancel(cropId) {
  document.getElementById(cropId+'-modal').classList.add('hidden');
  if (_mpCropper) { _mpCropper.destroy(); _mpCropper = null; }
}

// ── Core upload function ───────────────────────────────────────────────
async function mpDoUpload(file, pickerId, folder) {
  const formData = new FormData();
  formData.append('file',      file);
  formData.append('folder',    folder || 'general');
  formData.append('is_public', '1');
  formData.append('_token',    _mpCsrf);

  const res  = await fetch(_mpUploadUrl, { method:'POST', body:formData });
  const data = await res.json();

  if (data.success) {
    mpSetValue(pickerId, data.url || data.path);
    return data.url || data.path;
  } else {
    alert('Upload failed: ' + (data.error || 'Unknown error'));
    return '';
  }
}

// Close modals on backdrop click
document.addEventListener('click', e => {
  if (e.target.id && (e.target.id.endsWith('-modal') || e.target.classList.contains('fixed'))) {
    if (e.target === document.getElementById(e.target.id)) {
      e.target.classList.add('hidden');
    }
  }
});
</script>
@endpush
@endonce
