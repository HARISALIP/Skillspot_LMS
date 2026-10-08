<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $file->title }} — {{ $vendor->brand_name }}</title>
  @if($vendor->favicon)<link rel="icon" href="{{ Storage::disk('public')->url($vendor->favicon) }}">@endif
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root { --primary: {{ $vendor->primary_color ?? '#2563eb' }}; }
    body { font-family: 'Inter', sans-serif; background: #0f172a; }
    video::-webkit-media-controls-download-button { display: none !important; }
    video::-webkit-media-controls-enclosure { overflow: hidden; }
  </style>
</head>
<body class="text-white min-h-screen" oncontextmenu="return false;">

<nav class="bg-gray-900 border-b border-gray-800 px-4 py-3 flex items-center justify-between sticky top-0 z-10">
  <div class="flex items-center gap-3">
    <a href="{{ route('vendor.portal.live_class.detail', [$vendor->slug, $liveClass->slug]) }}"
       class="text-gray-400 hover:text-white text-sm transition">← Back</a>
    <span class="text-gray-600">|</span>
    <span class="text-sm font-semibold text-white truncate max-w-xs">{{ $file->title }}</span>
  </div>
  <div class="flex items-center gap-3">
    
    @if($file->isVideo())
    <button onclick="toggleFullscreen()" class="text-xs bg-gray-700 hover:bg-gray-600 px-3 py-1.5 rounded-lg transition">⛶ Fullscreen</button>
    @endif
  </div>
</nav>

<div class="max-w-5xl mx-auto px-4 py-6">

  @if($file->isVideo())
  <div id="videoWrap" class="rounded-2xl overflow-hidden bg-black shadow-2xl" oncontextmenu="return false;">
    <video id="secureVideo"
           class="w-full max-h-[75vh]"
           controls
           controlsList="nodownload noremoteplayback"
           disablePictureInPicture
           oncontextmenu="return false;">
      <source src="{{ $signedUrl }}" type="{{ $file->mime_type ?? 'video/mp4' }}">
    </video>
  </div>
  

  @elseif($file->isPdf())
  <div id="pdfWrap" class="rounded-2xl overflow-hidden shadow-2xl bg-gray-800" style="min-height:85vh;" oncontextmenu="return false;">
    <div class="flex items-center justify-between px-4 py-2 bg-gray-900 text-white text-xs">
      <div class="flex items-center gap-3">
        <button onclick="prevPage()" class="bg-gray-700 hover:bg-gray-600 px-2.5 py-1.5 rounded-lg transition">◀</button>
        <span>Page <span id="pageNum">1</span> / <span id="pageCount">?</span></span>
        <button onclick="nextPage()" class="bg-gray-700 hover:bg-gray-600 px-2.5 py-1.5 rounded-lg transition">▶</button>
      </div>
      <div class="flex items-center gap-2">
        <button onclick="zoomOut()" class="bg-gray-700 hover:bg-gray-600 px-2.5 py-1.5 rounded-lg transition">−</button>
        <span id="zoomLevel" class="w-12 text-center">100%</span>
        <button onclick="zoomIn()" class="bg-gray-700 hover:bg-gray-600 px-2.5 py-1.5 rounded-lg transition">+</button>
        <button onclick="togglePdfFullscreen()" class="bg-gray-700 hover:bg-gray-600 px-2.5 py-1.5 rounded-lg transition">⛶</button>
      </div>
    </div>
    <div id="pdfCanvas" class="overflow-auto bg-gray-700 flex justify-center py-2" style="min-height:calc(85vh - 40px);">
      <canvas id="pdfViewerCanvas" class="shadow-2xl"></canvas>
    </div>
    <div id="pdfLoading" class="absolute inset-0 flex items-center justify-center">
      <p class="text-gray-400 text-sm">Loading PDF...</p>
    </div>
  </div>
  

  @else
  <div class="text-center py-24">
    <div class="text-6xl mb-4">📦</div>
    <p class="text-gray-300 font-semibold text-lg">{{ $file->title }}</p>
    <p class="text-gray-500 text-sm mt-2">{{ $file->humanSize() }}</p>
    <p class="text-gray-600 text-xs mt-4">Preview not available for this file type.</p>
  </div>
  @endif

</div>

<script>
  document.addEventListener('contextmenu', e => e.preventDefault());
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey||e.metaKey) && ['s','u','p','c','a'].includes(e.key.toLowerCase())) e.preventDefault();
  });
  document.addEventListener('dragstart', e => e.preventDefault());

  @if($file->isVideo())
  function toggleFullscreen() {
    const el = document.getElementById('videoWrap');
    if (!document.fullscreenElement) el.requestFullscreen?.();
    else document.exitFullscreen?.();
  }
  @endif

  @if($file->isPdf())
  let pdfDoc = null, pageNum = 1, scale = 1.2;

  async function loadPdf() {
    const { getDocument, GlobalWorkerOptions } = await import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs');
    GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.worker.min.mjs';
    try {
      pdfDoc = await getDocument('{{ $signedUrl }}').promise;
      document.getElementById('pageCount').textContent = pdfDoc.numPages;
      document.getElementById('pdfLoading').style.display = 'none';
      await renderPage(1);
    } catch(e) {
      document.getElementById('pdfLoading').innerHTML = '<p class="text-red-400 text-sm">Failed to load. Please refresh.</p>';
    }
  }

  async function renderPage(num) {
    const page = await pdfDoc.getPage(num);
    const viewport = page.getViewport({ scale });
    const canvas = document.getElementById('pdfViewerCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: ctx, viewport }).promise;
    document.getElementById('pageNum').textContent = num;
  }

  function prevPage() { if (pageNum > 1) renderPage(--pageNum); }
  function nextPage() { if (pdfDoc && pageNum < pdfDoc.numPages) renderPage(++pageNum); }
  function zoomIn()  { scale = Math.min(scale+0.2, 3); document.getElementById('zoomLevel').textContent=Math.round(scale*100)+'%'; renderPage(pageNum); }
  function zoomOut() { scale = Math.max(scale-0.2, 0.5); document.getElementById('zoomLevel').textContent=Math.round(scale*100)+'%'; renderPage(pageNum); }
  function togglePdfFullscreen() {
    const el = document.getElementById('pdfWrap');
    if (!document.fullscreenElement) el.requestFullscreen?.();
    else document.exitFullscreen?.();
  }

  loadPdf();
  @endif
</script>
</body>
</html>
