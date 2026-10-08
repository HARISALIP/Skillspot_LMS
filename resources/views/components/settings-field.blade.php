@props(['field', 'meta', 'value' => ''])

@if($meta['type'] === 'boolean')
{{-- Toggle switch --}}
<label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 cursor-pointer hover:border-brand-300 transition">
  <div>
    <div class="text-sm font-semibold text-gray-900">{{ $meta['label'] }}</div>
  </div>
  <div class="relative flex-shrink-0 ml-4">
    <input type="checkbox" name="{{ $field }}" id="{{ $field }}"
           class="sr-only peer" {{ $value == '1' ? 'checked' : '' }}>
    <div class="w-11 h-6 bg-gray-200 peer-checked:bg-brand-600 rounded-full transition-colors duration-200 peer-focus:ring-2 peer-focus:ring-brand-400"></div>
    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-5"></div>
  </div>
</label>

@elseif($meta['type'] === 'textarea')
<div>
  <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">{{ $meta['label'] }}</label>
  <textarea name="{{ $field }}" rows="3"
            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-gray-50 focus:bg-white transition resize-none">{{ old($field, $value) }}</textarea>
</div>

@elseif($meta['type'] === 'password')
<div>
  <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">{{ $meta['label'] }}</label>
  <div class="relative">
    <input type="password" name="{{ $field }}" id="{{ $field }}"
           value="{{ old($field, $value) }}"
           placeholder="••••••••  (leave blank to keep current)"
           class="w-full pl-4 pr-11 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
    <button type="button" onclick="(function(i,b){const t=i.type==='text';i.type=t?'password':'text';b.textContent=t?'👁':'🙈'})(document.getElementById('{{ $field }}'),this)"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm focus:outline-none transition">👁</button>
  </div>
</div>

@else
{{-- Text input --}}
<div>
  <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">{{ $meta['label'] }}</label>
  <input type="text" name="{{ $field }}" value="{{ old($field, $value) }}"
         class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
</div>
@endif
