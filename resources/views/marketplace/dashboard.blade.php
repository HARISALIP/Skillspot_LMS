@extends('layouts.student')
@section('title','Creator Studio')
@section('page-title','Creator Studio')
@section('page-sub','Course reviews, sales, and payout availability.')
@section('student-content')
<div class="space-y-6">
 @if(session('success'))<div class="rounded-xl bg-green-50 border border-green-200 p-4 text-green-700 text-sm">{{ session('success') }}</div>@endif
 @if(session('info'))<div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-blue-700 text-sm">{{ session('info') }}</div>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 border border-red-200 p-4 text-red-700 text-sm"><p class="font-bold">This course is not ready to submit.</p><p class="mt-1">{{ $errors->first() }}</p></div>@endif

 @php
   $isLmsTeacher = auth()->user()->hasRole(['teacher', 'admin', 'super-admin']);
 @endphp

 @if($isLmsTeacher)
   <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-purple-50 p-5 flex items-center justify-between flex-wrap gap-4 shadow-sm">
     <div>
       <div class="flex items-center gap-2">
         <span class="text-xl">👩‍🏫</span>
         <h2 class="font-bold text-indigo-950 text-base">Teacher Workspace</h2>
         <span class="bg-indigo-600 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full">LMS Teacher</span>
       </div>
       <p class="mt-1 text-xs text-indigo-700">You are verified as a teacher on Skillspot.in. Build courses, create lessons, organize curriculum chapters, and mentor enrolled students.</p>
     </div>
     <a class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-xs font-bold shadow transition flex items-center gap-1.5" href="{{ route('instructor.courses.index') }}">
       <i class="fa-solid fa-plus"></i> Create Course
     </a>
   </div>
 @elseif(!$profile || $profile->status !== 'approved')
   <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
     <h2 class="font-bold text-amber-900">{{ match($profile?->status) { 'pending' => 'Instructor review in progress', 'rejected' => 'Application needs changes', 'suspended' => 'Creator access suspended', default => 'Apply to teach' } }}</h2>
     <p class="mt-1 text-sm text-amber-800">{{ $profile?->status === 'suspended' ? 'Your creator tools are paused. Contact an administrator about the review decision.' : 'You can keep learning while Skillspot checks your profile. Courses can only be submitted after approval.' }}</p>
     @if($profile?->status !== 'suspended')
       <a class="inline-block mt-3 text-sm font-bold text-brand-700" href="{{ route('marketplace.apply') }}">{{ $profile ? 'Update application →' : 'Apply now →' }}</a>
     @endif
   </div>
 @endif

 <div class="grid md:grid-cols-3 gap-4">
   @foreach(['Pending'=>$balances['pending'],'Available'=>$balances['available'],'Paid'=>$balances['paid']] as $label=>$amount)
     <div class="bg-white border border-gray-100 rounded-2xl p-5">
       <p class="text-xs font-bold uppercase tracking-wide text-gray-400">{{ $label }}</p>
       <p class="mt-2 text-2xl font-black text-gray-900">₹{{ number_format($amount,2) }}</p>
     </div>
   @endforeach
 </div>

 @if($balances['ledger'] < 0)
   <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
     Refund reconciliation balance: ₹{{ number_format($balances['ledger'],2) }}. New payouts are paused until an administrator reconciles it.
   </div>
 @endif

 <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
   <div class="p-5 border-b border-gray-100 flex items-center justify-between">
     <h2 class="font-bold text-gray-900">Your courses</h2>
     @if($isLmsTeacher || ($profile && $profile->status !== 'suspended'))
       <a class="rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-bold text-white transition" href="{{ route('instructor.courses.index') }}">Create course</a>
     @endif
   </div>
   <div class="divide-y divide-gray-100">
     @forelse($courses as $course)
       <div class="p-5 flex flex-wrap items-center justify-between gap-4">
         <div>
           <p class="font-semibold text-gray-900">{{ $course->title }}</p>
           <p class="text-xs text-gray-500 mt-1">
             {{ ucfirst(str_replace('_',' ', $course->marketplace_status ?: 'Draft')) }}
             @if($course->enrollments_count ?? false) · {{ $course->enrollments_count }} students @endif
             {{ $course->review_feedback ? ' — '.$course->review_feedback : '' }}
           </p>
         </div>
         <div class="flex flex-wrap items-center gap-3">
           <a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('instructor.courses.show',$course) }}">Edit Curriculum</a>
           <a class="text-sm font-semibold text-gray-600 hover:underline" href="{{ route('instructor.courses.preview',$course) }}">Preview</a>
           @if(($isLmsTeacher || $profile?->status === 'approved') && in_array($course->marketplace_status, ['draft','needs_changes','rejected', null]))
             <form method="POST" action="{{ route('studio.courses.submit-review',$course) }}">
               @csrf
               <button class="rounded-lg border border-brand-600 px-3 py-1.5 text-xs font-bold text-brand-700 hover:bg-brand-50 transition">Submit for approval</button>
             </form>
           @endif
         </div>
       </div>
     @empty
       <div class="p-8 text-sm text-gray-500 text-center">
         <div class="text-3xl mb-2">📚</div>
         <p class="font-medium">No courses created yet.</p>
         <p class="text-xs text-gray-400 mt-1">Click "Create course" to start building your first course.</p>
       </div>
     @endforelse
   </div>
 </div>

 @if($isLmsTeacher || $profile?->status === 'approved')
   <div class="bg-white rounded-2xl border border-gray-100 p-5">
     <h2 class="font-bold text-gray-900">Payout details</h2>
     <p class="mt-1 text-sm text-gray-500">Add a bank account before payouts are processed. Your account number and IFSC are encrypted.</p>
     <form method="POST" action="{{ route('studio.payout.update') }}" class="mt-4 grid gap-3 md:grid-cols-3">
       @csrf @method('PUT')
       <label class="text-sm">Account holder
         <input name="payout_account_name" required value="{{ old('payout_account_name', $profile?->payout_account_name) }}" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
       </label>
       <label class="text-sm">Account number
         <input name="payout_account_number" required autocomplete="off" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ $profile?->payout_account_number ? 'Saved — enter to replace' : '' }}">
       </label>
       <label class="text-sm">IFSC
         <input name="payout_ifsc" required autocomplete="off" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="{{ $profile?->payout_ifsc ? 'Saved — enter to replace' : '' }}">
       </label>
       <button class="justify-self-start rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-bold text-white md:col-span-3 transition">Save payout details</button>
     </form>
   </div>
 @endif

 <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
   <div class="p-5 border-b border-gray-100">
     <h2 class="font-bold text-gray-900">Recent sales</h2>
   </div>
   <div class="divide-y divide-gray-100">
     @forelse($sales as $sale)
       <div class="p-4 flex justify-between text-sm">
         <span>{{ $sale->course->title }}</span>
         <span class="font-semibold">₹{{ number_format($sale->seller_share,2) }} · {{ ucfirst($sale->status) }}</span>
       </div>
     @empty
       <div class="p-8 text-sm text-gray-500 text-center">No individual marketplace sales yet.</div>
     @endforelse
   </div>
 </div>
</div>
@endsection
