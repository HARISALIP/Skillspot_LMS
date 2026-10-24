@extends(request()->boolean('embed') ? 'layouts.admin-embed' : 'layouts.admin')
@section('title', isset($user) && $user->exists ? 'Edit User — Skillspot.in Admin' : 'Add New User — Skillspot.in Admin')
@section('page-title', isset($user) && $user->exists ? 'Edit User' : 'Add New User')
@section('page-sub',   isset($user) && $user->exists ? 'Update user details, role and password' : 'Invite a new student or admin to set their own password')

@section('admin-content')

@php
  $isEdit      = isset($user) && $user->exists;
  $currentRole = $isEdit ? ($user->roles->first()?->name ?? 'student') : old('role','student');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Main form ──────────────────────────────────────────────────── --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

      {{-- Header --}}
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-100 bg-gray-50/50">
        <div class="w-12 h-12 bg-gradient-to-br {{ $isEdit ? 'from-brand-400 to-accent-400' : 'from-green-400 to-emerald-500' }} rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-sm">
          {{ $isEdit ? strtoupper(substr($user->name,0,1)) : '➕' }}
        </div>
        <div>
          <h3 class="font-black text-gray-900">{{ $isEdit ? $user->name : 'New User Account' }}</h3>
          <p class="text-xs text-gray-400">{{ $isEdit ? $user->email : 'Enter their details and send a set-password link' }}</p>
        </div>
      </div>

      {{-- Errors --}}
      @if($errors->any())
      <div role="alert" class="mx-6 mt-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        @foreach($errors->all() as $e)<div class="flex items-center gap-1.5">• {{ $e }}</div>@endforeach
      </div>
      @endif

      @if(!$isEdit && !$inviteMailReady)
      <div role="alert" class="mx-6 mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        User invitations are unavailable until email delivery is verified.
        <a class="font-semibold underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500" href="{{ route('admin.settings', ['tab' => 'mail']) }}">Open Settings → Email Accounts</a> and send a test email first.
      </div>
      @endif

      <form method="POST"
            action="{{ $isEdit ? route('admin.users.update', $user->id) : route('admin.users.store') }}"
            class="p-6 space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Name --}}
        <div>
          <label for="user-name" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Full Name <span class="text-red-400">*</span></label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">👤</span>
            <input id="user-name" type="text" name="name" autocomplete="name"
                   value="{{ old('name', $isEdit ? $user->name : '') }}"
                   required placeholder="Full name"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
          <label for="user-email" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Email Address <span class="text-red-400">*</span></label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📧</span>
            <input id="user-email" type="email" name="email" autocomplete="email"
                   value="{{ old('email', $isEdit ? $user->email : '') }}"
                   required placeholder="user@example.com"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Phone --}}
        <div>
          <label for="user-phone" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Phone Number</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">📱</span>
            <input id="user-phone" type="tel" name="phone" autocomplete="tel"
                   value="{{ old('phone', $isEdit ? $user->phone : '') }}"
                   placeholder="+91 9876543210"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
          </div>
          @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Country --}}
        <div>
          <label for="user-country" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Country</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🌍</span>
            <select id="user-country" name="country" autocomplete="country"
                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition appearance-none cursor-pointer">
              @foreach($countries as $code => $cname)
              <option value="{{ $code }}" {{ old('country', $isEdit ? $user->country : 'IN') === $code ? 'selected' : '' }}>
                {{ $cname }}
              </option>
              @endforeach
            </select>
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">▾</span>
          </div>
        </div>

        {{-- Role --}}
        <div>
          <div id="user-role-label" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Role <span class="text-red-400">*</span></div>
          <div role="radiogroup" aria-labelledby="user-role-label" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @php
              $roleOptions = [
                'student'     => ['icon'=>'🎓','label'=>'Student',    'desc'=>'Can browse & enroll in courses',    'color'=>'border-blue-300 bg-blue-50 text-blue-700'],
                'teacher'     => ['icon'=>'👩‍🏫','label'=>'Teacher',    'desc'=>'Schedule classes, manage vendor content', 'color'=>'border-indigo-300 bg-indigo-50 text-indigo-700'],
                'admin'       => ['icon'=>'🛡️','label'=>'Admin',      'desc'=>'Manage courses, users & content',   'color'=>'border-orange-300 bg-orange-50 text-orange-700'],
              ];
              if(auth()->user()->hasRole('super-admin')) {
                $roleOptions['super-admin'] = ['icon'=>'👑','label'=>'Super Admin', 'desc'=>'Full platform access & settings', 'color'=>'border-red-300 bg-red-50 text-red-700'];
              }
              // Merge custom roles
              foreach(\Spatie\Permission\Models\Role::whereNotIn('name',['student','admin','super-admin'])->get() as $cr) {
                $roleOptions[$cr->name] = ['icon'=>'🎭','label'=>ucfirst(str_replace('-',' ',$cr->name)),'desc'=>'Custom role','color'=>'border-purple-300 bg-purple-50 text-purple-700'];
              }
            @endphp
            @foreach($roleOptions as $rkey => $rinfo)
            <label class="cursor-pointer">
              <input type="radio" name="role" value="{{ $rkey }}"
                     {{ old('role', $currentRole) === $rkey ? 'checked' : '' }}
                     class="sr-only peer">
              <div class="border-2 rounded-xl p-3 transition peer-checked:{{ $rinfo['color'] }} peer-checked:shadow-sm border-gray-200 bg-white hover:border-gray-300">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-lg">{{ $rinfo['icon'] }}</span>
                  <span class="font-bold text-gray-900 text-sm">{{ $rinfo['label'] }}</span>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed">{{ $rinfo['desc'] }}</p>
              </div>
            </label>
            @endforeach
          </div>
          @error('role')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        @if($isEdit)
        {{-- Password for existing users only --}}
        <div>
          <label for="pwdInput" class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">
            Password <span class="text-gray-400 font-normal normal-case ml-1">(leave blank to keep current)</span>
          </label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">🔑</span>
            <input type="password" name="password" id="pwdInput" autocomplete="new-password"
                   placeholder="Leave blank to keep current"
                   class="w-full pl-10 pr-12 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }} text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            <button type="button" aria-label="Show password"
                    onclick="const i=document.getElementById('pwdInput');i.type=i.type==='text'?'password':'text';this.textContent=i.type==='password'?'👁':'🙈';this.setAttribute('aria-label',i.type==='password'?'Show password':'Hide password')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition focus:outline-none">👁</button>
          </div>
          @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
        @else
        <p class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">The new user will receive a one-time link to set their own password. The link expires after 60 minutes.</p>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
          <a href="{{ route('admin.users') }}"
             class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
            ← Back to Users
          </a>
          <div class="flex items-center gap-2">
            @if($isEdit)
            <a href="{{ route('admin.users') }}"
               class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-sm transition">
              Cancel
            </a>
            @endif
            <button type="submit" id="submitBtn" @if(!$isEdit && !$inviteMailReady) disabled aria-disabled="true" @endif
                    class="flex items-center gap-2 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-700 hover:to-accent-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none text-white font-black px-7 py-2.5 rounded-xl transition shadow-lg shadow-brand-200 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
              <span id="submitText">{{ $isEdit ? 'Update User' : 'Create User & Send Invite' }}</span>
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>

  {{-- ── Side info panel ─────────────────────────────────────────────── --}}
  <div class="space-y-5">

    @if($isEdit)
    {{-- User info card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-4">Account Info</h4>
      <div class="space-y-3">
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-base flex-shrink-0">🆔</span>
          <div><div class="text-xs text-gray-400">User ID</div><div class="font-semibold text-gray-900">#{{ $user->id }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center text-base flex-shrink-0">📅</span>
          <div><div class="text-xs text-gray-400">Joined</div><div class="font-semibold text-gray-900">{{ $user->created_at?->format('d M Y') }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-base flex-shrink-0">🌍</span>
          <div><div class="text-xs text-gray-400">Country</div><div class="font-semibold text-gray-900">{{ $user->country_name }}</div></div>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center text-base flex-shrink-0">✅</span>
          <div>
            <div class="text-xs text-gray-400">Email Verified</div>
            <div class="font-semibold {{ $user->email_verified_at ? 'text-green-600' : 'text-gray-400' }}">
              {{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not verified' }}
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

    {{-- Tips card --}}
    <div class="bg-brand-50 border border-brand-200 rounded-2xl p-5">
      <h4 class="font-bold text-brand-800 text-sm mb-3">
        {{ $isEdit ? '✏️ Editing Tips' : '➕ Creating Tips' }}
      </h4>
      <ul class="space-y-2 text-xs text-brand-700 leading-relaxed">
        @if($isEdit)
        <li>• Leave password blank to keep existing password</li>
        <li>• Changing role takes effect immediately</li>
        <li>• Email change will require re-verification</li>
        @else
        <li>• The user sets their own password using the 60-minute invitation link</li>
        <li>• Verify email delivery in Settings before inviting users</li>
        <li>• Student role is recommended for learners</li>
        <li>• Custom roles can be created in the Roles section</li>
        @endif
      </ul>
    </div>

    {{-- Quick links --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h4 class="font-bold text-gray-900 text-sm mb-3">Quick Links</h4>
      <div class="space-y-2">
        <a href="{{ route('admin.users') }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 transition py-1">
          👥 All Users
        </a>
        <a href="{{ route('admin.roles') }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 transition py-1">
          🎭 Manage Roles
        </a>
        @if(!$isEdit)
        <a href="{{ route('admin.users.create') }}" class="flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 transition py-1">
          ➕ Add Another User
        </a>
        @endif
      </div>
    </div>

  </div>
</div>


@if($isEdit && $user->exists)
{{-- Organization learning Access Management --}}
<div class="mt-6 space-y-4" id="vendor-access-section">
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h2 class="font-semibold text-gray-900">🏪 Organization learning Access</h2>
        <p class="text-xs text-gray-400 mt-0.5">Manage which organizations + Skillspot.in this user can access</p>
      </div>
    </div>
    <div class="p-5 space-y-5">

      {{-- Portal access type: for non-vendor users show selector; vendors always get vendor_only --}}
      @if(session('access_success'))<div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('access_success') }}</div>@endif
      @php $currentRole = $user->roles->first()?->name ?? 'student'; @endphp
      @if($currentRole === 'student')
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Login Redirect</label>
        <form method="POST" action="{{ route('admin.users.access.portal', $user) }}" class="flex flex-wrap gap-2 items-center">
          @csrf
          @foreach(['Skillspot_only'=>['🎓','Skillspot.in Only'],'vendor_only'=>['🏪','Organization learning Only'],'both'=>['🔁','Both Portals']] as $val=>[$ico,$lbl])
          <label class="flex items-center gap-2 px-3 py-2 rounded-xl border-2 cursor-pointer text-sm transition
            {{ ($user->portal_access ?? 'Skillspot_only') === $val ? 'border-brand-400 bg-brand-50 text-brand-700 font-semibold' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
            <input type="radio" name="portal_access" value="{{ $val }}" {{ ($user->portal_access ?? 'Skillspot_only') === $val ? 'checked' : '' }} class="sr-only" onchange="this.form.submit()">
            {{ $ico }} {{ $lbl }}
          </label>
          @endforeach
        </form>
      </div>
      @endif

      {{-- Add vendor --}}
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Assign Organization learning</label>
        <form method="POST" action="{{ route('admin.users.access.add', $user) }}" class="flex gap-2 flex-wrap">
          @csrf
          <select name="vendor_id" required class="flex-1 min-w-48 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">— Select organization learning —</option>
            @foreach($allVendors ?? [] as $v)
              <option value="{{ $v->id }}" {{ $user->vendorAccess->contains('id',$v->id) ? 'disabled' : '' }}>
                {{ $v->brand_name }}{{ $user->vendorAccess->contains('id',$v->id) ? ' ✓' : '' }}
              </option>
            @endforeach
          </select>
          <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">Add Access</button>
        </form>
      </div>

      {{-- Current vendor access list --}}
      @if($user->vendorAccess->count())
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Current Portals ({{ $user->vendorAccess->count() }})</label>
        <div class="space-y-2">
          @foreach($user->vendorAccess as $v)
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-black" style="background: {{ $v->primary_color ?? '#2563eb' }}">{{ strtoupper(substr($v->brand_name,0,1)) }}</div>
              <div>
                <div class="text-sm font-semibold text-gray-800">{{ $v->brand_name }}</div>
                <div class="text-xs text-gray-400">{{ $v->slug }}</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $v->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ ucfirst($v->status) }}</span>
              <form method="POST" action="{{ route('admin.users.access.remove', [$user, $v]) }}" onsubmit="return confirm('Remove vendor access?')">
                @csrf @method('DELETE')
                <button class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 transition">✕ Remove</button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @else
      <p class="text-xs text-gray-400">No organizations assigned. Add one above.</p>
      @endif

    </div>
  </div>
</div>
@endif

@if($isEdit && $user->exists && (($user->roles->first()?->name ?? '') === 'teacher'))
{{-- ── Teacher Assignment Section ── --}}
<div class="mt-6 space-y-4" id="teacher-assignment-section">

  {{-- Assigned Vendors --}}
  <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-indigo-100 bg-indigo-50">
      <h2 class="font-bold text-indigo-900">🏪 Organization learning Assignments</h2>
      <p class="text-xs text-indigo-600 mt-0.5">Teacher can manage courses, lessons, live & batches for these vendors</p>
    </div>
    <div class="p-5 space-y-4">

      @if(session('teacher_success'))
      <div class="bg-green-50 text-green-800 border border-green-200 rounded-xl px-4 py-3 text-sm">{{ session('teacher_success') }}</div>
      @endif

      {{-- Add vendor --}}
      <form method="POST" action="{{ route('admin.users.teacher.vendor.add', $user) }}" class="flex gap-2 flex-wrap">
        @csrf
        <select name="vendor_id" required class="flex-1 min-w-48 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">— Select organization to assign —</option>
          @foreach($allVendorsFull ?? [] as $v)
            <option value="{{ $v->id }}" {{ in_array($v->id, $teacherVendors ?? []) ? 'disabled' : '' }}>
              {{ $v->brand_name }}{{ in_array($v->id, $teacherVendors ?? []) ? ' ✓ Assigned' : '' }}
            </option>
          @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">Assign Vendor</button>
      </form>

      {{-- Current vendor assignments --}}
      @php $assignedVendors = ($allVendorsFull ?? collect())->whereIn('id', $teacherVendors ?? []); @endphp
      @if($assignedVendors->count())
      <div class="space-y-2">
        @foreach($assignedVendors as $v)
        <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-xl border border-indigo-100">
          <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-black flex-shrink-0" style="background:{{ $v->primary_color ?? '#4f46e5' }}">{{ strtoupper(substr($v->brand_name,0,1)) }}</div>
            <div>
              <div class="text-sm font-semibold text-gray-800">{{ $v->brand_name }}</div>
              <div class="text-xs text-gray-400">{{ $v->slug }}</div>
            </div>
          </div>
          <form method="POST" action="{{ route('admin.users.teacher.vendor.remove', [$user, $v->id]) }}" onsubmit="return confirm('Remove vendor assignment?')">
            @csrf @method('DELETE')
            <button class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 transition">✕ Remove</button>
          </form>
        </div>
        @endforeach
      </div>
      @else
      <p class="text-xs text-gray-400">No organizations assigned yet.</p>
      @endif
    </div>
  </div>

  {{-- Skillspot.in Course Assignments --}}
  <div class="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-brand-100 bg-brand-50">
      <h2 class="font-bold text-brand-900">🎛️ Skillspot.in Course Assignments</h2>
      <p class="text-xs text-brand-600 mt-0.5">Teacher can manage syllabus, lessons & live for these Skillspot.in Courses</p>
    </div>
    <div class="p-5 space-y-4">

      {{-- Add course --}}
      <form method="POST" action="{{ route('admin.users.teacher.course.add', $user) }}" class="flex gap-2 flex-wrap">
        @csrf
        <select name="course_id" required class="flex-1 min-w-48 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="">— Select Skillspot.in course —</option>
          @foreach($SkillspotCourses ?? [] as $c)
            @php $alreadyAssigned = $user->assignedCourses->contains('id', $c->id); @endphp
            <option value="{{ $c->id }}" {{ $alreadyAssigned ? 'disabled' : '' }}>
              {{ $c->title }}{{ $alreadyAssigned ? ' ✓ Assigned' : '' }}
            </option>
          @endforeach
        </select>
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">Assign Course</button>
      </form>

      {{-- Assigned courses --}}
      @if($user->assignedCourses->count())
      <div class="space-y-2">
        @foreach($user->assignedCourses as $c)
        <div class="flex items-center justify-between p-3 bg-brand-50 rounded-xl border border-brand-100">
          <div>
            <div class="text-sm font-semibold text-gray-800">{{ $c->title }}</div>
            <div class="text-xs text-gray-400">{{ $c->category }}</div>
          </div>
          <form method="POST" action="{{ route('admin.users.teacher.course.remove', [$user, $c->id]) }}" onsubmit="return confirm('Remove course assignment?')">
            @csrf @method('DELETE')
            <button class="text-xs text-red-500 hover:text-red-700 font-semibold px-2 py-1 transition">✕ Remove</button>
          </form>
        </div>
        @endforeach
      </div>
      @else
      <p class="text-xs text-gray-400">No Skillspot.in Courses assigned yet.</p>
      @endif
    </div>
  </div>

</div>
@endif

@endsection

@push('scripts')
<script>
  // Submit spinner
  document.querySelector('form')?.addEventListener('submit', function() {
    const btn  = document.getElementById('submitBtn');
    const text = document.getElementById('submitText');
    if (btn && text) {
      text.textContent = 'Saving…';
      btn.disabled = true;
    }
  });
</script>
@endpush
