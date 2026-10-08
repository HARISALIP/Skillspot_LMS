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

    public function create()
    {
        $users = $this->vendorUsers();
        return view('admin.vendors.create', compact('users'));
    }

    public function store(Request $request)
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
        ]);

        foreach (['logo','banner_image','favicon'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store("vendors/{$field}s", 'public');
            }
        }

        $data['slug'] = Vendor::uniqueSlug($data['brand_name']);
        $vendor = Vendor::create($data);

        // Auto-assign vendor role
        $user = User::find($data['user_id']);
        if ($user && !$user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        return redirect()->route('admin.vendors')->with('success', "Vendor \"{$data['brand_name']}\" created ✅");
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

        $vendor->update($data);
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
        DB::table('vendor_users')
            ->where('vendor_id', $vendor->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', "{$user->name} removed from vendor.");
    }
}
