@extends('layouts.admin')
@section('title','Enroll Student — Skillspot.in Admin')
@section('page-title','Enroll Students')
@section('page-sub','Grant course access to specific students with custom expiry')

@section('admin-content')

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-4 text-sm">
  @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Form --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center text-lg">🎓</div>
        <div>
          <h3 class="font-black text-gray-900">Grant Course Access</h3>
          <p class="text-xs text-gray-400">Enroll one or multiple students manually</p>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.enrollments.store') }}" class="p-6 space-y-5">
        @csrf

        {{-- Student search --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">
            Students <span class="text-red-400">*</span>
            <span class="text-gray-400 font-normal normal-case ml-1">— search and add multiple</span>
          </label>

          {{-- Search input --}}
          <div class="relative mb-2">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
            <input type="text" id="studentSearch" placeholder="Search by name, email or phone…"
                   autocomplete="off"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            {{-- Dropdown --}}
            <div id="studentDropdown"
                 class="hidden absolute top-full left-0 right-0 mt-1 bg-white rounded-2xl border border-gray-200 shadow-xl z-20 max-h-56 overflow-y-auto">
            </div>
          </div>

          {{-- Selected students --}}
          <div id="selectedStudents" class="flex flex-wrap gap-2 min-h-[40px] p-2 bg-gray-50 rounded-xl border border-gray-200">
            @if($preUser)
            <div class="selected-student flex items-center gap-1.5 bg-brand-100 text-brand-800 text-xs font-bold px-3 py-1.5 rounded-xl"
                 data-id="{{ $preUser->id }}">
              <input type="hidden" name="user_ids[]" value="{{ $preUser->id }}">
              <span>{{ $preUser->name }}</span>
              <button type="button" onclick="removeStudent(this)" class="hover:text-red-600 ml-1 font-black">×</button>
            </div>
            @else
            <span id="noStudentPlaceholder" class="text-xs text-gray-400 p-1">No students selected yet</span>
            @endif
          </div>
        </div>

        {{-- Course --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Course <span class="text-red-400">*</span></label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📚</span>
            <select name="course_id" required
                    class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('course_id') ? 'border-red-400' : 'border-gray-200' }} bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition appearance-none">
              <option value="">Select course…</option>
              @foreach($courses as $course)
              <option value="{{ $course->id }}"
                      {{ old('course_id', $preCourse?->id) == $course->id ? 'selected' : '' }}>
                {{ $course->title }}
                ({{ ucfirst($course->level) }})
              </option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Access type --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Access Type <span class="text-red-400">*</span></label>
          <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
              <input type="radio" name="access_type" value="lifetime" class="sr-only peer"
                     {{ old('access_type','lifetime') === 'lifetime' ? 'checked' : '' }}
                     onchange="toggleExpiry(false)">
              <div class="border-2 border-gray-200 bg-white rounded-2xl p-4 peer-checked:border-green-500 peer-checked:bg-green-50 transition">
                <div class="text-2xl mb-2">♾️</div>
                <div class="font-bold text-gray-900 text-sm">Lifetime Access</div>
                <div class="text-xs text-gray-400 mt-1">Never expires — access forever</div>
              </div>
            </label>
            <label class="cursor-pointer">
              <input type="radio" name="access_type" value="limited" class="sr-only peer"
                     {{ old('access_type') === 'limited' ? 'checked' : '' }}
                     onchange="toggleExpiry(true)">
              <div class="border-2 border-gray-200 bg-white rounded-2xl p-4 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition">
                <div class="text-2xl mb-2">📅</div>
                <div class="font-bold text-gray-900 text-sm">Limited Access</div>
                <div class="text-xs text-gray-400 mt-1">Set a custom expiry date</div>
              </div>
            </label>
          </div>
        </div>

        {{-- Expiry date (shown only for limited) --}}
        <div id="expirySection" class="{{ old('access_type') === 'limited' ? '' : 'hidden' }}">
          <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">Expiry Date <span class="text-red-400">*</span></label>

          {{-- Quick presets --}}
          <div class="flex flex-wrap gap-2 mb-3">
            @foreach([
              ['1 Month',   1, 'month'],
              ['2 Months',  2, 'month'],
              ['3 Months',  3, 'month'],
              ['6 Months',  6, 'month'],
              ['1 Year',    1, 'year'],
              ['2 Years',   2, 'year'],
            ] as [$label, $val, $unit])
            <button type="button"
                    onclick="setExpiry({{ $val }},'{{ $unit }}')"
                    class="text-xs bg-gray-100 hover:bg-brand-100 hover:text-brand-700 font-semibold px-3 py-1.5 rounded-xl transition">
              {{ $label }}
            </button>
            @endforeach
          </div>

          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📅</span>
            <input type="date" name="expires_at" id="expiryDate"
                   value="{{ old('expires_at') }}"
                   min="{{ now()->addDay()->format('Y-m-d') }}"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          <div id="expiryDisplay" class="text-xs text-brand-600 font-semibold mt-1.5 hidden"></div>
        </div>

        {{-- Notes --}}
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Internal Note <span class="text-gray-400 font-normal normal-case">(optional)</span></label>
          <textarea name="notes" rows="2" placeholder="e.g. Scholarship student, Batch Jan 2026…"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none">{{ old('notes') }}</textarea>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
          <a href="{{ route('admin.enrollments') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
          <button type="submit"
                  class="flex items-center gap-2 bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white font-black px-7 py-2.5 rounded-xl transition shadow-md text-sm">
            🎓 Grant Access
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Sidebar --}}
  <div class="space-y-5">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">⏱ Access Types</h4>
      <div class="space-y-3 text-xs text-gray-600">
        <div class="p-3 bg-green-50 rounded-xl border border-green-100">
          <div class="font-bold text-green-800 mb-1">♾️ Lifetime</div>
          <div>Student keeps access forever. Even if they close account and re-open, access remains.</div>
        </div>
        <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
          <div class="font-bold text-blue-800 mb-1">📅 Limited</div>
          <div>Access expires on set date. After expiry, student cannot watch videos or attend live classes. You can extend anytime.</div>
        </div>
      </div>
    </div>
    <div class="bg-brand-50 border border-brand-200 rounded-2xl p-5">
      <h4 class="font-bold text-brand-800 text-sm mb-2">💡 Tips</h4>
      <ul class="text-xs text-brand-700 space-y-1.5 leading-relaxed">
        <li>• Search students by name, email or phone</li>
        <li>• Add multiple students at once</li>
        <li>• Use quick presets for common durations</li>
        <li>• You can extend access later from the list</li>
        <li>• Notes are internal — students don't see them</li>
      </ul>
    </div>
  </div>
</div>

@endsection
@push('scripts')
<script>
const searchUrl = '{{ route('admin.enrollments.search-users') }}';
let searchTimer;
const selected  = new Set();

// Pre-select if coming from user page
@if($preUser) selected.add({{ $preUser->id }}); @endif

document.getElementById('studentSearch').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 2) { hideDropdown(); return; }
  searchTimer = setTimeout(() => doSearch(q), 300);
});

async function doSearch(q) {
  const res   = await fetch(searchUrl + '?q=' + encodeURIComponent(q));
  const users = await res.json();
  const dd    = document.getElementById('studentDropdown');
  dd.innerHTML = '';
  if (!users.length) {
    dd.innerHTML = '<div class="px-4 py-3 text-xs text-gray-400">No students found</div>';
  } else {
    users.forEach(u => {
      if (selected.has(u.id)) return;
      const div = document.createElement('div');
      div.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-brand-50 cursor-pointer transition';
      div.innerHTML = `
        <div class="w-7 h-7 bg-gradient-to-br from-brand-400 to-accent-400 rounded-lg flex items-center justify-center text-white font-black text-xs flex-shrink-0">
          ${u.name.charAt(0).toUpperCase()}
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-gray-900">${u.name}</div>
          <div class="text-xs text-gray-400">${u.email}${u.phone ? ' · '+u.phone : ''}</div>
        </div>`;
      div.onclick = () => addStudent(u);
      dd.appendChild(div);
    });
  }
  dd.classList.remove('hidden');
}

function addStudent(u) {
  if (selected.has(u.id)) return;
  selected.add(u.id);
  const placeholder = document.getElementById('noStudentPlaceholder');
  if (placeholder) placeholder.remove();
  const container = document.getElementById('selectedStudents');
  const tag = document.createElement('div');
  tag.className = 'selected-student flex items-center gap-1.5 bg-brand-100 text-brand-800 text-xs font-bold px-3 py-1.5 rounded-xl';
  tag.dataset.id = u.id;
  tag.innerHTML = `<input type="hidden" name="user_ids[]" value="${u.id}">
    <span>${u.name}</span>
    <button type="button" onclick="removeStudent(this)" class="hover:text-red-600 ml-1 font-black">×</button>`;
  container.appendChild(tag);
  document.getElementById('studentSearch').value = '';
  hideDropdown();
}

function removeStudent(btn) {
  const tag = btn.closest('.selected-student');
  selected.delete(parseInt(tag.dataset.id));
  tag.remove();
  if (!document.querySelectorAll('.selected-student').length) {
    const p = document.createElement('span');
    p.id = 'noStudentPlaceholder';
    p.className = 'text-xs text-gray-400 p-1';
    p.textContent = 'No students selected yet';
    document.getElementById('selectedStudents').appendChild(p);
  }
}

function hideDropdown() {
  document.getElementById('studentDropdown').classList.add('hidden');
}
document.addEventListener('click', e => {
  if (!e.target.closest('#studentSearch') && !e.target.closest('#studentDropdown')) hideDropdown();
});

function toggleExpiry(show) {
  document.getElementById('expirySection').classList.toggle('hidden', !show);
}

function setExpiry(val, unit) {
  const d = new Date();
  if (unit === 'month') d.setMonth(d.getMonth() + val);
  else d.setFullYear(d.getFullYear() + val);
  const str = d.toISOString().split('T')[0];
  document.getElementById('expiryDate').value = str;
  const disp = document.getElementById('expiryDisplay');
  disp.textContent = 'Expires: ' + d.toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
  disp.classList.remove('hidden');
}

document.getElementById('expiryDate')?.addEventListener('change', function() {
  if (!this.value) return;
  const d = new Date(this.value);
  const disp = document.getElementById('expiryDisplay');
  disp.textContent = 'Expires: ' + d.toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'});
  disp.classList.remove('hidden');
});
</script>
@endpush
