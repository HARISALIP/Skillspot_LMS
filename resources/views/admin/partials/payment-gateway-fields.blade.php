@foreach(\App\Support\PaymentGatewayCatalog::PROVIDERS as $providerKey => $definition)
  @continue($providerKey === 'razorpay')
  @php $existing = $gatewayAccounts->get($providerKey)?->credentials ?? []; @endphp
  <div data-provider-fields="{{ $providerKey }}" class="mt-5 grid grid-cols-1 gap-x-4 gap-y-5 border-t border-gray-200 pt-4 sm:grid-cols-2 hidden" aria-label="{{ $definition['label'] }} credentials">
    @foreach($definition['fields'] as $fieldKey => $meta)
      @php
        $fieldId = 'gateway-'. $providerKey .'-'. $fieldKey;
        $fieldName = "credentials[$providerKey][$fieldKey]";
        $isSecret = $meta['secret'] ?? false;
        $isBoolean = ($meta['type'] ?? '') === 'boolean';
      @endphp
      @if($isBoolean)
        <label class="flex items-center gap-3 text-sm text-gray-700"><input type="checkbox" name="{{ $fieldName }}" value="1" @checked(old("credentials.$providerKey.$fieldKey", $existing[$fieldKey] ?? false)) disabled class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">{{ $meta['label'] }}</label>
      @else
        <label for="{{ $fieldId }}" class="block text-sm text-gray-700 {{ ($meta['type'] ?? '') === 'textarea' ? 'sm:col-span-2' : '' }}">{{ $meta['label'] }} @if($meta['required'] ?? false)<span class="text-red-500" aria-hidden="true">*</span>@endif
          @if(($meta['type'] ?? '') === 'textarea')
            <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="3" maxlength="4096" autocomplete="off" disabled class="mt-1 block w-full rounded-md border-0 bg-gray-100 px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-brand-500" placeholder="{{ $isSecret && filled($existing[$fieldKey] ?? null) ? 'Saved · leave blank to keep current' : '' }}">{{ $isSecret ? '' : old("credentials.$providerKey.$fieldKey", $existing[$fieldKey] ?? '') }}</textarea>
          @else
            <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="{{ $isSecret ? 'password' : (($meta['type'] ?? '') === 'number' ? 'number' : 'text') }}" value="{{ $isSecret ? '' : old("credentials.$providerKey.$fieldKey", $existing[$fieldKey] ?? '') }}" maxlength="{{ ($meta['type'] ?? '') === 'number' ? '' : '4096' }}" autocomplete="off" disabled data-required="{{ ($meta['required'] ?? false) && blank($existing[$fieldKey] ?? null) ? '1' : '0' }}" class="mt-1 block w-full rounded-md border-0 bg-gray-100 px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:ring-brand-500" placeholder="{{ $isSecret && filled($existing[$fieldKey] ?? null) ? 'Saved · leave blank to keep current' : '' }}">
          @endif
        </label>
      @endif
    @endforeach
  </div>
@endforeach
