@extends('layouts.vendor')
@section('title', $file->title . ')
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

  <div class="mb-5 flex items-center justify-between">
    <a href="{{ route('vendor.live_classes.show', $liveClass->id) }}"
       class="text-sm text-gray-500 hover:text-gray-700">← Back to {{ $liveClass->title }}</a>
    <div class="flex items-center gap-2">
      
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="text-2xl">{{ $file->file_type === 'recording' ? '🎬' : ($file->file_type === 'notes' ? '📄' : '📦') }}</span>
        <div>
          <h1 class="font-bold text-gray-900">{{ $file->title }}</h1>
          <p class="text-xs text-gray-400">{{ ucfirst($file->file_type) }} · {{ $file->humanSize() }} · {{ $file->created_at->format('d M Y') }}</p>
        </div>
      </div>
      @if($file->isVideo())
      <button onclick="toggleFullscreen()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-3 py-1.5 rounded-lg transition">⛶ Fullscreen</button>
      @endif
    </div>

    <div class="p-4" oncontextmenu="return false;">

      @if($file->isVideo())
      {{-- Video player with fullscreen but no download --}}
      <div id="videoWrap" class="rounded-xl overflow-hidden bg-black relative" oncontextmenu="return false;">
        <video id="secureVideo"
               class="w-full max-h-[72vh]"
               controls
               controlsList="nodownload noremoteplayback"
               disablePictureInPicture
               oncontextmenu="return false;"
               style="pointer-events:auto;">
          <source src="{{ $signedUrl }}" type="{{ $file->mime_type ?? 'video/mp4' }}">
          Your browser does not support video playback.
        </video>
      </div>
      

      @elseif($file->isPdf())
      {{-- PDF.js viewer — fullscreen capable, no download --}}
      <div id="pdfWrap" class="rounded-xl overflow-hidden border border-gray-200 bg-gray-100 relative" style="height:80vh;" oncontextmenu="return false;">
        <div id="pdfToolbar" class="flex items-center justify-between px-4 py-2 bg-gray-800 text-white text-xs">
          <div class="flex items-center gap-3">
            <button onclick="prevPage()" class="bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded">◀ Prev</button>
            <span>Page <span id="pageNum">1</span> of <span id="pageCount">?</span></span>
            <button onclick="nextPage()" class="bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded">Next ▶</button>
          </div>
          <div class="flex items-center gap-3">
            <button onclick="zoomOut()" class="bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded">−</button>
            <span id="zoomLevel">100%</span>
            <button onclick="zoomIn()" class="bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded">+</button>
            <button onclick="togglePdfFullscreen()" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded">⛶ Fullscreen</button>
          </div>
        </div>
        <div id="pdfCanvas" class="overflow-auto bg-gray-200 flex justify-center" style="height:calc(80vh - 40px);">
          <canvas id="pdfViewerCanvas" class="shadow-lg my-2" style="max-width:100%;"></canvas>
        </div>
        <div id="pdfLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
          <div class="text-center text-gray-500">
            <div class="text-3xl mb-2">📄</div>
            <p class="text-sm">Loading PDF...</p>
          </div>
        </div>
      </div>
      

      @else
      <div class="text-center py-16">
        <div class="text-5xl mb-3">📦</div>
        <p class="text-gray-600 font-semibold">{{ $file->title }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ $file->humanSize() }}</p>
        <p class="text-xs text-gray-400 mt-4">Preview not available for this file type.</p>
      </div>
      @endif

    </div>
  </div>
</div>

@push('scripts')
@if($file->isVideo())
<script>
function toggleFullscreen() {
  const el = document.getElementById('videoWrap');
  if (!document.fullscreenElement) el.requestFullscreen();
  else document.exitFullscreen();
}
document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && ['s','u','p'].includes(e.key.toLowerCase())) e.preventDefault();
});
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('dragstart', e => e.preventDefault());
</script>
@endif

@if($file->isPdf())
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs" type="module"></script>
<script type="module">
import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.worker.min.mjs';

let pdfDoc = null, pageNum = 1, scale = 1.3;
const canvas = document.getElementById('pdfViewerCanvas');
const ctx = canvas.getContext('2d');

async function loadPdf() {
  try {
    pdfDoc = await pdfjsLib.getDocument({ url: '{{ $signedUrl }}', disableFontFace: false }).promise;
    document.getElementById('pageCount').textContent = pdfDoc.numPages;
    document.getElementById('pdfLoading').style.display = 'none';
    await renderPage(1);
  } catch(e) {
    document.getElementById('pdfLoading').innerHTML = '<p class="text-red-500 text-sm">Failed to load PDF. Please refresh.</p>';
  }
}

async function renderPage(num) {
  const page = await pdfDoc.getPage(num);
  const viewport = page.getViewport({ scale });
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  await page.render({ canvasContext: ctx, viewport }).promise;
  document.getElementById('pageNum').textContent = num;
}

window.prevPage = () => { if (pageNum > 1) renderPage(--pageNum); };
window.nextPage = () => { if (pdfDoc && pageNum < pdfDoc.numPages) renderPage(++pageNum); };
window.zoomIn  = () => { scale = Math.min(scale + 0.2, 3.0); document.getElementById('zoomLevel').textContent = Math.round(scale*100)+'%'; renderPage(pageNum); };
window.zoomOut = () => { scale = Math.max(scale - 0.2, 0.5); document.getElementById('zoomLevel').textContent = Math.round(scale*100)+'%'; renderPage(pageNum); };
window.togglePdfFullscreen = () => {
  const el = document.getElementById('pdfWrap');
  if (!document.fullscreenElement) el.requestFullscreen();
  else document.exitFullscreen();
};

document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && ['s','u','p','c'].includes(e.key.toLowerCase())) e.preventDefault();
});

loadPdf();
</script>
@endif
@endpush
@endsection
