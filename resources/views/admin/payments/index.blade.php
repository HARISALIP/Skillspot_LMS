@extends('layouts.admin')
@section('title','Payments — Skillspot.in Admin')
@section('page-title','Payments')
@section('page-sub','All payment transactions')

@section('admin-content')

@if(session('success'))
<div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ✅ {{ session('success') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl px-5 py-3.5 text-sm font-semibold">
  ❌ {{ session('error') }}
  <button onclick="this.parentElement.remove()" class="ml-auto text-xl">×</button>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  @foreach([
    ['💰','Total Revenue', '₹'.number_format($stats['revenue'],2), 'bg-green-50','text-green-700'],
    ['✅','Paid',          $stats['paid'],    'bg-blue-50',  'text-blue-700'],
    ['❌','Failed',        $stats['failed'],  'bg-red-50',   'text-red-700'],
    ['↩️','Refunded',      $stats['refunded'],'bg-yellow-50','text-yellow-700'],
    ['📋','Total Orders',  $stats['total'],   'bg-gray-50',  'text-gray-700'],
  ] as [$icon,$label,$val,$bg,$text])
  <div class="{{ $bg }} rounded-2xl p-4 border border-transparent">
    <div class="text-2xl mb-1">{{ $icon }}</div>
    <div class="text-lg font-black {{ $text }}">{{ $val }}</div>
    <div class="text-xs font-semibold text-gray-500">{{ $label }}</div>
  </div>
  @endforeach
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
    <h3 class="font-black text-gray-900 text-sm">All Transactions</h3>
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('admin.payments') }}"
        class="flex flex-wrap gap-3 px-5 py-3 border-b border-gray-100 bg-gray-50/50">
    <div class="relative flex-1 min-w-[160px]">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
      <input type="text" name="search" value="{{ request('search') }}" placeholder="Payment ID, Order ID, student…"
             class="w-full pl-8 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white">
    </div>
    <select name="status" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">All Status</option>
      @foreach(['created','paid','failed','refunded'] as $s)
      <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
    <select name="mode" onchange="this.form.submit()"
            class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
      <option value="">Live + Test</option>
      <option value="live" {{ request('mode')==='live'?'selected':'' }}>🟢 Live</option>
      <option value="test" {{ request('mode')==='test'?'selected':'' }}>🟡 Test</option>
    </select>
    <input type="date" name="from" value="{{ request('from') }}"
           class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
    <input type="date" name="to" value="{{ request('to') }}"
           class="px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold">Search</button>
    @if(request()->hasAny(['search','status','mode','from','to']))
    <a href="{{ route('admin.payments') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Clear</a>
    @endif
  </form>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Student</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden md:table-cell">Course</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Amount</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Status</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden sm:table-cell">Mode</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Payment ID</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide hidden lg:table-cell">Date</th>
          <th class="px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($payments as $p)
        <tr class="hover:bg-gray-50 transition">
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-accent-400 rounded-xl flex items-center justify-center text-white font-black text-xs flex-shrink-0">
                {{ strtoupper(substr($p->user->name??'?',0,1)) }}
              </div>
              <div class="min-w-0">
                <div class="font-semibold text-gray-900 text-xs truncate">{{ $p->user->name ?? '—' }}</div>
                <div class="text-gray-400 text-xs truncate hidden sm:block">{{ $p->user->email ?? '' }}</div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3.5 hidden md:table-cell">
            <div class="text-xs text-gray-700 max-w-[160px] truncate">{{ $p->course->title ?? '—' }}</div>
          </td>
          <td class="px-5 py-3.5 font-black text-gray-900 text-sm">
            ₹{{ number_format($p->amount,2) }}
          </td>
          <td class="px-5 py-3.5">
            @php $sc = ['paid'=>'bg-green-100 text-green-700','failed'=>'bg-red-100 text-red-700','refunded'=>'bg-yellow-100 text-yellow-700','created'=>'bg-gray-100 text-gray-600'][$p->status] ?? 'bg-gray-100 text-gray-600'; @endphp
            <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-lg {{ $sc }}">{{ ucfirst($p->status) }}</span>
          </td>
          <td class="px-5 py-3.5 hidden sm:table-cell">
            <span class="text-xs {{ $p->mode==='live'?'text-green-600':'text-yellow-600' }} font-semibold">
              {{ $p->mode==='live'?'🟢':'🟡' }} {{ ucfirst($p->mode) }}
            </span>
          </td>
          <td class="px-5 py-3.5 hidden lg:table-cell">
            <div class="text-xs font-mono text-gray-500 max-w-[140px] truncate">{{ $p->razorpay_payment_id ?? $p->razorpay_order_id ?? '—' }}</div>
          </td>
          <td class="px-5 py-3.5 hidden lg:table-cell text-xs text-gray-400">
            {{ $p->paid_at?->format('d M Y H:i') ?? $p->created_at?->format('d M Y') }}
          </td>
          <td class="px-5 py-3.5">
            @if($p->status === 'paid')
            <form method="POST" action="{{ route('admin.payments.refund', $p->id) }}"
                  onsubmit="return confirm('Mark as refunded?')">
              @csrf @method('PATCH')
              <button class="text-xs text-yellow-700 hover:text-yellow-900 font-semibold hover:underline transition">↩ Refund</button>
            </form>
            @else
            <span class="text-xs text-gray-300">—</span>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-5 py-14 text-center text-gray-400">
          <div class="text-4xl mb-2">💳</div>
          <p class="font-medium">No payments yet</p>
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($payments->hasPages())
  <div class="px-5 py-4 border-t border-gray-100">{{ $payments->withQueryString()->links('pagination::tailwind') }}</div>
  @else
  <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">{{ $payments->total() }} transactions</div>
  @endif
</div>

@endsection
