<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendorsController extends Controller
{
    private function vendorUsers()
    {
        return User::role('vendor')->orderBy('name')->get(['id','name','email']);
    }

    public function index()
    {
        $vendors = Vendor::with('user')->withCount('courses','enrollments')->latest()->paginate(20);
        return view('admin.vendors.index', compact('vendors'));
    }

    public function accessAudit()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $issues = DB::table('organization_access_migration_issues as issues')
            ->join('enrollments as enrollments', 'enrollments.id', '=', 'issues.enrollment_id')
            ->join('courses as courses', 'courses.id', '=', 'enrollments.course_id')
            ->leftJoin('users as users', 'users.id', '=', 'enrollments.user_id')
            ->select('issues.id', 'issues.reason', 'issues.enrollment_id', 'courses.title', 'users.email')
            ->orderBy('issues.id')->paginate(50);
        return view('admin.vendors.access-audit', compact('issues'));
    }

    public function create()
    {
        $users = $this->vendorUsers();
        return view('admin.vendors.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'       => 'nullable|required_without:admin_email|exists:users,id',
            'admin_name'    => 'required_with:admin_email|string|max:255',
            'admin_email'   => 'nullable|email|unique:users,email',
            'brand_name'    => 'required|string|max:120',
            'tagline'       => 'nullable|string|max:200',
            'description'   => 'nullable|string',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|string|max:20',
            'website'       => 'nullable|url',
            'primary_color' => 'nullable|string|max:20',
            'accent_color'  => 'nullable|string|max:20',
            'status'        => 'required|in:pending,active,suspended',
            'plan'          => 'required|string|max:40',
            'logo'          => 'nullable|image|max:2048',
            'banner_image'  => 'nullable|image|max:4096',
            'favicon'       => 'nullable|image|max:512',
            'payout_account_name' => 'nullable|string|max:255',
            'payout_account_number' => 'nullable|string|max:80',
            'payout_ifsc' => 'nullable|string|max:30',
        ]);

        $invite = filled($data['admin_email'] ?? null);
        if ($invite && !(config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address')) && filled(Setting::get('mail_invites_verified_at')))) {
            throw ValidationException::withMessages(['admin_email' => 'Verify SMTP in Email Accounts before inviting an organization admin.']);
        }
        foreach (['logo','banner_image','favicon'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store("vendors/{$field}s", 'public');
            }
        }
        $newAdmin = null;
        if ($invite) {
            $newAdmin = User::create([
                'name' => $data['admin_name'], 'email' => $data['admin_email'],
                'password' => Hash::make(Str::random(64)), 'registered_via' => 'admin',
                'portal_access' => 'vendor_only',
            ]);
            $newAdmin->assignRole('vendor');
            $data['user_id'] = $newAdmin->id;
        }
        unset($data['admin_name'], $data['admin_email']);
        $data['slug'] = Vendor::uniqueSlug($data['brand_name']);
        $data['marketplace_verified_at'] = $data['status'] === 'active' && filled($data['payout_account_name'] ?? null) && filled($data['payout_account_number'] ?? null) && filled($data['payout_ifsc'] ?? null) ? now() : null;
        $vendor = Vendor::create($data);

        // Auto-assign vendor role
        $user = User::find($data['user_id']);
        if ($user && !$user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        if ($newAdmin) {
            try {
                if (Password::sendResetLink(['email' => $newAdmin->email]) !== Password::RESET_LINK_SENT) {
                    throw new \RuntimeException('Invitation delivery failed.');
                }
            } catch (\Throwable $exception) {
                $vendor->delete();
                $newAdmin->delete();
                throw ValidationException::withMessages(['admin_email' => 'Invitation could not be sent. Organization was not created.']);
            }
        }

        return redirect()->route('admin.vendors.edit', $vendor)->with('success', "Organization \"{$data['brand_name']}\" created. Open its management panel to continue.");
    }

    public function edit(Vendor $vendor)
    {
        $users       = $this->vendorUsers();
        $vendorUsers = $this->vendorUsers();
        if ($vendor->user && !$users->contains('id', $vendor->user_id)) {
            $users = $users->push($vendor->user)->sortBy('name')->values();
        }
        return view('admin.vendors.edit', compact('vendor','users','vendorUsers'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'brand_name'    => 'required|string|max:120',
            'tagline'       => 'nullable|string|max:200',
            'description'   => 'nullable|string',
            'email'         => 'nullable|email',
            'phone'         => 'nullable|string|max:20',
            'website'       => 'nullable|url',
            'primary_color' => 'nullable|string|max:20',
            'accent_color'  => 'nullable|string|max:20',
            'status'        => 'required|in:pending,active,suspended',
            'plan'          => 'required|string|max:40',
            'logo'          => 'nullable|image|max:2048',
            'banner_image'  => 'nullable|image|max:4096',
            'favicon'       => 'nullable|image|max:512',
            'payout_account_name' => 'nullable|string|max:255',
            'payout_account_number' => 'nullable|string|max:80',
            'payout_ifsc' => 'nullable|string|max:30',
            'organization_fee_percent' => 'sometimes|numeric|between:0,100',
        ]);

        foreach (['logo','banner_image','favicon'] as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->$field) Storage::disk('public')->delete($vendor->$field);
                $data[$field] = $request->file($field)->store("vendors/{$field}s", 'public');
            }
        }

        if ((int)$data['user_id'] !== (int)$vendor->user_id) {
            $newUser = User::find($data['user_id']);
            if ($newUser && !$newUser->hasRole('vendor')) {
                $newUser->assignRole('vendor');
            }
        }

        if (!auth()->user()->hasRole('super-admin')) unset($data['organization_fee_percent']);
        $data['marketplace_verified_at'] = $data['status'] === 'active' && filled($data['payout_account_name'] ?? null) && filled($data['payout_account_number'] ?? null) && filled($data['payout_ifsc'] ?? null) ? ($vendor->marketplace_verified_at ?: now()) : null;
        $previousOwner = $vendor->user_id;
        $vendor->update($data);
        if ($previousOwner !== $vendor->user_id) {
            DB::table('vendor_users')->where('vendor_id', $vendor->id)
                ->where('user_id', $previousOwner)->where('role', 'owner')->delete();
        }
        return redirect()->route('admin.vendors')->with('success', "Vendor updated ✅");
    }

    public function destroy(Vendor $vendor)
    {
        $vendorId = $vendor->id;
        $name     = $vendor->brand_name;

        // Get course & lesson IDs for this vendor
        $courseIds = \App\Models\Course::where('vendor_id', $vendorId)->pluck('id');
        $lessonIds = \App\Models\Lesson::whereHas('section', fn($q) => $q->whereIn('course_id', $courseIds))->pluck('id');

        // Delete related records — only if tables/columns exist
        if (Schema::hasTable('batch_attendance')) {
            DB::table('batch_attendance')->whereIn('lesson_id', $lessonIds)->delete();
        }
        if (Schema::hasTable('lesson_progress')) {
            DB::table('lesson_progress')->whereIn('lesson_id', $lessonIds)->delete();
        }
        if (Schema::hasTable('quiz_attempts')) {
            DB::table('quiz_attempts')->whereIn('lesson_id', $lessonIds)->delete();
        }

        DB::table('lessons')->whereIn('id', $lessonIds)->delete();
        DB::table('sections')->whereIn('course_id', $courseIds)->delete();
        DB::table('enrollments')->whereIn('course_id', $courseIds)->delete();
        DB::table('certificates')->whereIn('course_id', $courseIds)->delete();
        DB::table('reviews')->whereIn('course_id', $courseIds)->delete();

        if (Schema::hasTable('course_allowed_users')) {
            DB::table('course_allowed_users')->whereIn('course_id', $courseIds)->delete();
        }
        if (Schema::hasTable('course_teachers')) {
            DB::table('course_teachers')->whereIn('course_id', $courseIds)->delete();
        }

        DB::table('courses')->where('vendor_id', $vendorId)->delete();

        // Vendor-specific relation tables (may not exist in all environments)
        foreach (['vendor_users', 'vendor_teachers', 'student_vendor_access', 'vendor_preallowed_contacts'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('vendor_id', $vendorId)->delete();
            }
        }

        // Payment orders — only delete if vendor_id column exists
        if (Schema::hasTable('payment_orders') && Schema::hasColumn('payment_orders', 'vendor_id')) {
            DB::table('payment_orders')->where('vendor_id', $vendorId)->delete();
        }

        // Delete vendor images from storage
        foreach (['logo', 'banner_image', 'favicon'] as $field) {
            if ($vendor->$field) Storage::disk('public')->delete($vendor->$field);
        }

        $vendor->delete();

        return redirect()->route('admin.vendors')
            ->with('success', "Vendor \"{$name}\" and all related data deleted ✅");
    }

    public function toggleStatus(Vendor $vendor)
    {
        $vendor->status = $vendor->status === 'active' ? 'suspended' : 'active';
        $vendor->save();
        return back()->with('success', "Vendor status updated.");
    }

    public function enter(Vendor $vendor)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        session(['active_vendor_id' => $vendor->id]);
        return redirect()->route('vendor.dashboard');
    }

    /** Add a manager/owner to a vendor */
    public function addManager(Request $request, Vendor $vendor)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:owner,manager',
        ]);

        $user = User::findOrFail($request->user_id);
        if (!$user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        DB::table('vendor_users')->updateOrInsert(
            ['vendor_id' => $vendor->id, 'user_id' => $user->id],
            ['role' => $request->role, 'added_by' => auth()->id(), 'updated_at' => now(), 'created_at' => now()]
        );

        return back()->with('success', "{$user->name} added as {$request->role} ✅");
    }

    /** Remove a manager from a vendor */
    public function removeManager(Vendor $vendor, User $user)
    {
        abort_if($vendor->user_id === $user->id, 422, 'Assign another owner before removing the owner.');
        DB::table('vendor_users')
            ->where('vendor_id', $vendor->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', "{$user->name} removed from vendor.");
    }
}
