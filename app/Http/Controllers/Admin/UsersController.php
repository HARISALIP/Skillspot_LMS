<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    private function assertCanManage(User $user): void
    {
        abort_if($user->hasRole('super-admin') && !auth()->user()->hasRole('super-admin'), 403);
    }

    private array $countries = [
        'IN'=>'India','US'=>'United States','GB'=>'United Kingdom',
        'AE'=>'UAE','SA'=>'Saudi Arabia','QA'=>'Qatar','KW'=>'Kuwait',
        'BH'=>'Bahrain','OM'=>'Oman','SG'=>'Singapore','MY'=>'Malaysia',
        'AU'=>'Australia','CA'=>'Canada','DE'=>'Germany','FR'=>'France',
        'NP'=>'Nepal','LK'=>'Sri Lanka','BD'=>'Bangladesh','PK'=>'Pakistan',
        'NG'=>'Nigeria','ZA'=>'South Africa','OTHER'=>'Other',
    ];

    // ── List ───────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',   'like', "%{$search}%")
                  ->orWhere('email','like', "%{$search}%")
                  ->orWhere('phone','like', "%{$search}%");
            });
        }

        if ($role = $request->role) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($country = $request->country) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'country')) {
                $query->where('country', $country);
            }
        }

        if ($source = $request->source) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'registered_via')) {
                $query->where('registered_via', $source);
            }
        }

        $users         = $query->paginate(20);
        $totalUsers    = User::count();
        $totalStudents = User::role('student')->count();
        $totalAdmins   = User::role(['admin', 'super-admin'])->count();
        $thisMonth     = User::whereMonth('created_at', now()->month)
                             ->whereYear('created_at',  now()->year)->count();

        $countries = \Illuminate\Support\Facades\Schema::hasColumn('users', 'country')
            ? User::select('country', 'country_name')
                ->whereNotNull('country')
                ->distinct()->orderBy('country_name')
                ->pluck('country_name', 'country')->toArray()
            : $this->countries;

        return view('admin.users.index', compact(
            'users', 'totalUsers', 'totalStudents', 'totalAdmins', 'thisMonth', 'countries'
        ));
    }

    // ── Create form ────────────────────────────────────────────────────
    public function create()
    {
        $user      = new User();
        $countries = $this->countries;
        $inviteMailReady = $this->inviteMailReady();
        return view('admin.users.form', compact('user', 'countries', 'inviteMailReady'));
    }

    private function inviteMailReady(): bool
    {
        return config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'))
            && filled(Setting::get('mail_invites_verified_at'));
    }

    // ── Store ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $allRoles = Role::query()
            ->when(!$request->user()->hasRole('super-admin'), fn ($query) => $query->where('name', '!=', 'super-admin'))
            ->pluck('name')->toArray();

        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'country'       => ['required', 'string', 'max:5'],
            'role'          => ['required', 'string', 'in:' . implode(',', $allRoles)],
            'portal_access' => ['nullable', 'in:Skillspot_only,vendor_only,both'],
        ]);

        if (!$this->inviteMailReady()) {
            throw ValidationException::withMessages([
                'email' => 'Verify SMTP in Settings → Email Accounts before creating users.',
            ]);
        }

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'phone'          => $request->phone ?: null,
            'country'        => $request->country,
            'country_name'   => $this->countries[$request->country] ?? $request->country,
            'password'       => Hash::make(Str::random(64)),
            'registered_via' => 'admin',
            'portal_access'  => $request->input('role') === 'vendor'
                ? 'vendor_only'
                : ($request->input('role') === 'student'
                    ? $request->input('portal_access', 'skillspot_only')
                    : 'skillspot_only'),
        ]);

        $user->assignRole($request->role);

        try {
            $status = PasswordBroker::sendResetLink(['email' => $user->email]);
            if ($status !== PasswordBroker::RESET_LINK_SENT) {
                throw new \RuntimeException($status);
            }
        } catch (\Throwable $exception) {
            DB::transaction(function () use ($user) {
                $user->roles()->detach();
                $user->delete();
                DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                    ->where('email', $user->email)->delete();
            });
            throw ValidationException::withMessages([
                'email' => 'The invitation could not be sent. No account was created. Check Email Accounts and retry.',
            ]);
        }

        return redirect()->route('admin.users')
            ->with('success', "Invitation sent to {$user->email}. They can set their password using the link.");
    }

    // ── Edit form ──────────────────────────────────────────────────────
    public function edit(User $user)
    {
        $this->assertCanManage($user);
        $relations = ['roles'];
        if (\Illuminate\Support\Facades\Schema::hasTable('student_vendor_access')) {
            $relations[] = 'vendorAccess';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('course_allowed_users')) {
            $relations[] = 'assignedCourses';
        }
        $user->load($relations);

        $countries  = $this->countries;
        $allVendors = \Illuminate\Support\Facades\Schema::hasTable('vendors')
            ? \App\Models\Vendor::orderBy('brand_name')->get(['id','brand_name','slug','status','primary_color'])
            : collect([]);

        $allVendorsFull   = $allVendors;
        $SkillspotCourses = \Illuminate\Support\Facades\Schema::hasTable('courses')
            ? \App\Models\Course::whereNull('vendor_id')->orderBy('title')->get(['id','title','category'])
            : collect([]);

        $teacherVendors   = \Illuminate\Support\Facades\Schema::hasTable('vendor_teachers')
            ? \Illuminate\Support\Facades\DB::table('vendor_teachers')
                ->where('user_id', $user->id)->pluck('vendor_id')->toArray()
            : [];

        return view('admin.users.form', compact('user','countries','allVendors','allVendorsFull','SkillspotCourses','teacherVendors'));
    }

    // ── Update ─────────────────────────────────────────────────────────
    public function update(Request $request, User $user)
    {
        $this->assertCanManage($user);
        $allRoles = Role::query()
            ->when(!$request->user()->hasRole('super-admin'), fn ($query) => $query->where('name', '!=', 'super-admin'))
            ->pluck('name')->toArray();

        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone'         => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'country'       => ['required', 'string', 'max:5'],
            'role'          => ['required', 'string', 'in:' . implode(',', $allRoles)],
            'portal_access' => ['nullable', 'in:Skillspot_only,vendor_only,both'],
            'password'      => ['nullable', Password::min(8)],
        ]);

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone ?: null,
            'country'       => $request->country,
            'country_name'  => $this->countries[$request->country] ?? $request->country,
            'portal_access' => $request->input('role') === 'vendor'
                ? 'vendor_only'
                : ($request->input('role') === 'student'
                    ? $request->input('portal_access', 'Skillspot_only')
                    : 'Skillspot_only'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users')
            ->with('success', "User \"{$user->name}\" updated successfully ✅");
    }

    // ── Delete ─────────────────────────────────────────────────────────
    public function destroy(User $user)
    {
        abort_if($user->hasRole('super-admin'), 403);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User \"{$name}\" deleted.");
    }

    // ── User Vendor Access Management ─────────────────────────────────
    public function access(\App\Models\User $user)
    {
        $this->assertCanManage($user);
        $user->load('vendorAccess','roles');
        $allVendors = \App\Models\Vendor::orderBy('brand_name')->get(['id','brand_name','slug','status','primary_color']);
        return view('admin.users.access', compact('user','allVendors'));
    }

    public function addAccess(\Illuminate\Http\Request $request, \App\Models\User $user)
    {
        $this->assertCanManage($user);
        $request->validate(['vendor_id' => 'required|exists:vendors,id']);
        \Illuminate\Support\Facades\DB::table('student_vendor_access')->updateOrInsert(
            ['user_id' => $user->id, 'vendor_id' => $request->vendor_id],
            ['granted_by' => auth()->id(), 'note' => $request->note, 'updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', 'Vendor access granted ✅');
    }

    public function removeAccess(\App\Models\User $user, \App\Models\Vendor $vendor)
    {
        $this->assertCanManage($user);
        \Illuminate\Support\Facades\DB::table('student_vendor_access')
            ->where('user_id', $user->id)->where('vendor_id', $vendor->id)->delete();
        return back()->with('success', 'Access removed.');
    }

    public function setPortal(\Illuminate\Http\Request $request, \App\Models\User $user)
    {
        $this->assertCanManage($user);
        $request->validate(['portal_access' => 'required|in:Skillspot_only,vendor_only,both']);
        $user->update(['portal_access' => $request->portal_access]);
        return back()->with('success', 'Portal access updated ✅');
    }

    // ── Teacher: assign to vendor ────────────────────────────────────────
    public function assignTeacherVendor(\Illuminate\Http\Request $request, \App\Models\User $user)
    {
        $this->assertCanManage($user);
        $request->validate(['vendor_id' => 'required|exists:vendors,id']);
        if (!$user->hasRole('teacher')) $user->assignRole('teacher');
        \Illuminate\Support\Facades\DB::table('vendor_teachers')->updateOrInsert(
            ['vendor_id' => $request->vendor_id, 'user_id' => $user->id],
            ['added_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', 'Vendor assigned to teacher ✅');
    }

    public function removeTeacherVendor(\App\Models\User $user, \App\Models\Vendor $vendor)
    {
        $this->assertCanManage($user);
        \Illuminate\Support\Facades\DB::table('vendor_teachers')
            ->where('vendor_id', $vendor->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Vendor removed from teacher.');
    }

    // ── Teacher: assign to Skillspot course ────────────────────────────────
    public function assignTeacherCourse(\Illuminate\Http\Request $request, \App\Models\User $user)
    {
        $this->assertCanManage($user);
        $request->validate(['course_id' => 'required|exists:courses,id']);
        if (!$user->hasRole('teacher')) $user->assignRole('teacher');
        \Illuminate\Support\Facades\DB::table('course_teachers')->updateOrInsert(
            ['course_id' => $request->course_id, 'user_id' => $user->id],
            ['added_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
        );
        return back()->with('success', 'Skillspot course assigned to teacher ✅');
    }

    public function removeTeacherCourse(\App\Models\User $user, int $courseId)
    {
        $this->assertCanManage($user);
        \Illuminate\Support\Facades\DB::table('course_teachers')
            ->where('course_id', $courseId)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Course removed from teacher.');
    }

    // ── Impersonate user (super-admin only) ───────────────────────────
    public function impersonate(\App\Models\User $user)
    {
        // Only super-admin can impersonate
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Only super-admins can impersonate users.');
        }

        // Cannot impersonate another super-admin
        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Cannot impersonate another super-admin.');
        }

        // Store original admin ID in session
        session(['impersonating_as' => $user->id, 'impersonator_id' => auth()->id()]);

        // Login as target user
        auth()->login($user);

        return redirect('/')->with('success', 'You are now logged in as ' . $user->name . '. Click "Stop Impersonating" to return.');
    }

    // ── Stop impersonating — return to original admin account ─────────
    public function stopImpersonate()
    {
        $originalId = session('impersonator_id');

        if (!$originalId) {
            return redirect()->route('admin.dashboard');
        }

        $original = \App\Models\User::find($originalId);

        session()->forget(['impersonating_as', 'impersonator_id']);

        if ($original) {
            auth()->login($original);
        }

        return redirect()->route('admin.users')->with('success', 'Returned to your admin account.');
    }
}
