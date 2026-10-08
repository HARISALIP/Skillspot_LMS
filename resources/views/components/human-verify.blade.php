@php
  $fk         = $formKey ?? 'default';
  $fType      = (str_contains($fk, 'reg') || str_contains($fk, 'register')) ? 'register' : 'login';
  $hvEnabled  = \App\Models\Setting::get($fType === 'register' ? 'human_verify_register' : 'human_verify_login', '1') === '1';
  $hv         = $hvEnabled ? \App\Services\HumanVerifier::generate($fk) : ['question'=>'','token'=>''];
@endphp
@if(!$hvEnabled)
{{-- Human verification disabled by admin --}}
@else

{{-- ── Honeypot fields (hidden from humans, bots fill these) ── --}}
<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;opacity:0;pointer-events:none;tab-index:-1;">
  <input type="text"     name="_email_confirm"  value="" autocomplete="off" tabindex="-1" id="hp1">
  <input type="url"      name="_website"        value="" autocomplete="off" tabindex="-1" id="hp2">
  <input type="text"     name="_phone_confirm"  value="" autocomplete="off" tabindex="-1" id="hp3">
</div>

{{-- ── Hidden verification fields (set by JS) ── --}}
<input type="hidden" name="_hv_token"    id="hvToken"    value="">
<input type="hidden" name="_hv_interact" id="hvInteract" value="0">
<input type="hidden" name="_hv_answer"  id="hvAnswerHidden" value="">

{{-- ── Math challenge ── --}}
<div class="mb-5">
  <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
    <div class="flex items-start gap-3">
      <div class="w-9 h-9 bg-brand-100 rounded-xl flex items-center justify-center text-lg flex-shrink-0">🧮</div>
      <div class="flex-1 min-w-0">
        <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">
          Quick Check — Prove you're human
        </label>
        <div class="flex items-center gap-3 flex-wrap">
          <span class="text-gray-900 font-black text-base">{{ $hv['question'] }} = ?</span>
          <input type="number" name="_hv_answer" id="hvAnswerInput"
                 inputmode="numeric" min="0" max="81" autocomplete="off"
                 placeholder="Answer"
                 class="w-20 px-3 py-2 rounded-xl border-2 border-gray-200 focus:border-brand-500 focus:outline-none text-center font-black text-gray-900 text-base bg-white transition">
        </div>
        @if($errors->has('_hv_answer') || $errors->has('_hv'))
        <p class="text-xs text-red-500 mt-1.5">{{ $errors->first('_hv_answer') ?: $errors->first('_hv') }}</p>
        @endif
      </div>
    </div>

    {{-- Status indicator --}}
    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200">
      <span id="hvStatusDot" class="w-2 h-2 rounded-full bg-gray-300 flex-shrink-0 transition-colors duration-500"></span>
      <span id="hvStatusText" class="text-xs text-gray-400">Waiting for interaction…</span>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  // Set token immediately
  document.getElementById('hvToken').value = '{{ $hv['token'] }}';

  // Track interaction score
  let interactScore = 0;
  const interact    = document.getElementById('hvInteract');
  const statusDot   = document.getElementById('hvStatusDot');
  const statusText  = document.getElementById('hvStatusText');
  const answerInput = document.getElementById('hvAnswerInput');
  const answerHidden= document.getElementById('hvAnswerHidden');

  function addInteract(points) {
    interactScore = Math.min(interactScore + points, 10);
    interact.value = interactScore;
    if (interactScore >= 3) {
      statusDot.className  = 'w-2 h-2 rounded-full bg-green-500 flex-shrink-0 transition-colors duration-500';
      statusText.textContent = '✓ Human verified';
      statusText.className = 'text-xs text-green-600 font-semibold';
    }
  }

  // Mouse move
  let mouseMoved = false;
  document.addEventListener('mousemove', function() {
    if (!mouseMoved) { mouseMoved = true; addInteract(2); }
  }, { once: false });

  // Touch (mobile)
  document.addEventListener('touchstart', function() { addInteract(3); }, { once: true });
  document.addEventListener('touchmove',  function() { addInteract(1); }, { once: true });

  // Scroll
  document.addEventListener('scroll', function() { addInteract(1); }, { once: true });

  // Keypress in any field
  document.addEventListener('keydown', function(e) {
    if (!['Tab','Shift','Control','Alt','Meta'].includes(e.key)) {
      addInteract(1);
    }
  }, { once: true });

  // Sync visible answer to hidden field on submit
  const form = answerInput?.closest('form');
  if (form) {
    form.addEventListener('submit', function() {
      answerHidden.value = answerInput?.value ?? '';
    });
  }
  // Also sync on input change (belt + suspenders)
  answerInput?.addEventListener('input', function() {
    answerHidden.value = this.value;
  });

  // Focus on answer input = human interaction
  answerInput?.addEventListener('focus', function() { addInteract(2); });

  // Prevent honeypot autofill from browser
  ['hp1','hp2','hp3'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.value = ''; el.setAttribute('readonly', true); }
  });
})();
</script>
@endpush
@endif
