<article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm flex flex-col">
  <div class="text-xs font-semibold text-brand-600">{{ $source }} · {{ ucwords(str_replace('_',' ', $material['kind'])) }}</div>
  <h3 class="font-bold text-gray-900 mt-1">{{ $material['title'] }}</h3>
  @if($material['type'] === 'image')
    <button type="button" class="mt-3 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden" onclick="openMaterial(this)"
            data-title="{{ $material['title'] }}" data-type="image" data-url="{{ $material['url'] }}" data-download-url="{{ $material['download_url'] }}">
      <img src="{{ $material['url'] }}" alt="Preview {{ $material['title'] }}" loading="lazy" class="max-h-52 w-full object-contain">
    </button>
  @elseif($material['type'] === 'audio')
    <audio controls preload="none" class="w-full mt-4" src="{{ $material['url'] }}">Audio playback is unavailable in this browser.</audio>
  @else
    <div class="mt-3 rounded-xl bg-brand-50 text-brand-700 p-5 text-center text-3xl" aria-hidden="true">{{ $material['type'] === 'presentation' ? '📊' : '📄' }}</div>
  @endif
  <div class="flex flex-wrap items-center gap-3 mt-auto pt-4 text-sm font-bold">
    @if($material['type'] !== 'document')
      <button type="button" class="text-brand-700 hover:underline" onclick="openMaterial(this)"
              data-title="{{ $material['title'] }}" data-type="{{ $material['type'] }}" data-url="{{ $material['preview_url'] ?: $material['url'] }}" data-download-url="{{ $material['download_url'] }}">Preview {{ $material['type'] === 'presentation' ? 'slides' : '' }} ↗</button>
    @else
      <a href="{{ $material['url'] }}" target="_blank" rel="noopener noreferrer" class="text-brand-700 hover:underline">Open file ↗</a>
    @endif
    <a href="{{ $material['download_url'] }}" class="text-gray-700 hover:underline">Download ↓</a>
  </div>
</article>
