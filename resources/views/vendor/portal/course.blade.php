@extends('vendor.portal.layout')
@section('title', $course->title.' — '.$vendor->brand_name)
@section('content')
<div class="mx-auto max-w-3xl px-6 py-10">
  <a href="{{ route('vendor.portal.home', $vendor->slug) }}" class="text-sm text-blue-700">← Courses</a>
  <div class="mt-4 rounded-2xl bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">{{ $course->title }}</h1>
    <p class="mt-3 text-gray-600">{{ $course->description }}</p>
    @if($hasAccess)
      <a href="{{ route('vendor.portal.batch', [$vendor->slug, $course->id]) }}" class="mt-5 inline-block rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white">Open course</a>
    @elseif(!$course->registration_open)
      <p class="mt-5 text-amber-700">Enrollment is closed.</p>
    @elseif($course->is_free || (float) $course->price <= 0)
      <form method="POST" action="{{ route('vendor.portal.courses.enroll-free', [$vendor->slug, $course]) }}" class="mt-5">@csrf<button class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white">Enroll for free</button></form>
    @elseif($checkoutIssue)
      <p class="mt-5 text-amber-700">{{ $checkoutIssue }}</p>
    @else
      <button id="pay" class="mt-5 rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white">Pay ₹{{ number_format((float) ($course->sale_price ?? $course->price), 2) }}</button>
      <p id="payment-error" role="alert" class="mt-2 text-red-700"></p>
    @endif
  </div>
</div>
@endsection
@push('scripts')
@if(!$hasAccess && !$course->is_free && (float) $course->price > 0 && !$checkoutIssue && $course->registration_open)
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('pay').addEventListener('click', async () => {
  const button = document.getElementById('pay'), error = document.getElementById('payment-error');
  button.disabled = true; error.textContent = '';
  try {
    const headers = {'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'};
    const orderResponse = await fetch(@json(route('vendor.portal.courses.order', [$vendor->slug, $course])), {method:'POST', headers});
    const order = await orderResponse.json();
    if (!orderResponse.ok) throw Error(order.error || 'Checkout unavailable');
    new Razorpay({key:order.key_id, amount:order.amount, currency:order.currency, order_id:order.order_id,
      name:@json($vendor->brand_name), description:order.course,
      prefill:{name:order.user_name,email:order.user_email,contact:order.user_phone},
      handler:async response => {
        const confirmation = await fetch(@json(route('vendor.portal.courses.verify', [$vendor->slug, $course])), {method:'POST', headers, body:JSON.stringify(response)});
        const result = await confirmation.json();
        if (result.success) location.href = result.redirect;
        else { error.textContent = result.error || 'Payment confirmation pending'; button.disabled = false; }
      }, modal:{ondismiss:() => button.disabled = false}}).open();
  } catch (e) { error.textContent = e.message; button.disabled = false; }
});
</script>
@endif
@endpush
