<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\OrganizationClass;
use App\Models\Setting;
use App\Models\User;
use App\Models\Upload;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class OrganizationController extends Controller
{
    use ResolvesVendor;

    private function managedVendor(): \App\Models\Vendor
    {
        $vendor = $this->vendor();
        $user = auth()->user();
        abort_unless($user->hasRole(['admin', 'super-admin']) || $vendor->managers()->where('users.id', $user->id)->exists(), 403);
        return $vendor;
    }

    public function index()
    {
        $vendor = $this->managedVendor();
        $classes = $vendor->organizationClasses()->withCount('students', 'courses')->orderBy('name')->get();
        $students = $vendor->studentAccess()->with('organizationClasses')->orderBy('name')->paginate(30);
        $staff = $vendor->managers()->get()->merge($vendor->teachers()->get())->unique('id');
        $courses = $vendor->courses()->with('organizationClasses')->orderBy('title')->get();
        $failedInvitations = DB::table('organization_failed_invitations')->where('vendor_id', $vendor->id)->pluck('user_id');
        return view('vendor.organization', compact('vendor', 'classes', 'students', 'staff', 'courses', 'failedInvitations'));
    }

    public function media()
    {
        $vendor = $this->managedVendor();
        $courses = $vendor->courses()->orderBy('title')->get(['id', 'title']);
        $files = Upload::with('uploader')->whereIn('used_in->course_id', $courses->pluck('id'))
            ->latest()->paginate(24);
        return view('vendor.media', compact('vendor', 'courses', 'files'));
    }

    public function reports()
    {
        $vendor = $this->managedVendor();
        $courses = $vendor->courses()->withCount('enrollments', 'certificates')->orderBy('title')->get();
        $certificates = Certificate::with('course', 'user')->whereIn('course_id', $courses->pluck('id'))
            ->latest()->take(50)->get();
        return view('vendor.reports', compact('vendor', 'courses', 'certificates'));
    }

    public function storeClass(Request $request)
    {
        $vendor = $this->managedVendor();
        $data = $request->validateWithBag('class', ['name' => 'required|string|max:120']);
        $vendor->organizationClasses()->firstOrCreate(['name' => trim($data['name'])]);
        return back()->with('success', 'Class created.');
    }

    public function deleteClass(OrganizationClass $class)
    {
        $vendor = $this->managedVendor();
        abort_unless($class->vendor_id === $vendor->id, 404);
        if ($class->courses()->where('organization_audience', 'classes')->exists())
            return back()->with('error', 'Move courses to another class before removing this class. No assignments were changed.');
        $class->delete();
        return back()->with('success', 'Class removed.');
    }

    public function updateStudentClasses(Request $request, User $user)
    {
        $vendor = $this->managedVendor();
        abort_unless($user->hasVendorAccess($vendor->id), 404);
        $data = $request->validate([
            'class_ids' => 'array',
            'class_ids.*' => ['integer', \Illuminate\Validation\Rule::exists('organization_classes', 'id')->where('vendor_id', $vendor->id)],
        ]);
        $existing = $user->organizationClasses()->where('vendor_id', $vendor->id)->pluck('organization_classes.id');
        $user->organizationClasses()->detach($existing);
        $user->organizationClasses()->syncWithoutDetaching($data['class_ids'] ?? []);
        return back()->with('success', 'Student classes updated.');
    }

    public function storeStaff(Request $request)
    {
        $vendor = $this->managedVendor();
        $data = $request->validateWithBag('staff', [
            'name' => 'required|string|max:255', 'email' => 'required|email',
            'role' => 'required|in:admin,teacher',
        ]);
        $user = User::where('email', $data['email'])->first();
        abort_if($user && $user->hasRole(['admin', 'super-admin']), 422, 'Platform administrators cannot be assigned as organization staff.');
        if (!$user) {
            $this->assertMailReady('email');
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make(Str::random(64)), 'registered_via' => 'organization_invite',
                'portal_access' => 'both',
            ]);
        }
        $role = $data['role'] === 'admin' ? 'vendor' : 'teacher';
        if (!$user->hasRole($role)) $user->assignRole($role);
        if ($role === 'vendor') {
            $vendor->managers()->syncWithoutDetaching([$user->id => ['role' => 'manager', 'added_by' => auth()->id()]]);
        } else {
            $vendor->teachers()->syncWithoutDetaching([$user->id => ['added_by' => auth()->id()]]);
        }
        if ($user->wasRecentlyCreated && !$this->sendInvitation($vendor->id, $user))
            return back()->with('error', 'Staff access was created, but the invitation email failed. The staff list shows a Retry button.');
        return back()->with('success', 'Staff access granted.');
    }

    public function removeStaff(User $user)
    {
        $vendor = $this->managedVendor();
        abort_if($vendor->user_id === $user->id, 422, 'Assign another owner before removing this owner.');
        $vendor->managers()->detach($user->id);
        $vendor->teachers()->detach($user->id);
        DB::table('organization_failed_invitations')->where('vendor_id', $vendor->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Staff removed from this organization.');
    }

    public function template(string $format)
    {
        $this->managedVendor();
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);
        if ($format === 'csv') return response("name,email,classes\nStudent Name,student@example.com,Class 10 A;Class 10 B\n", 200, [
            'Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="organization-students.csv"',
        ]);
        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->fromArray([['name', 'email', 'classes'], ['Student Name', 'student@example.com', 'Class 10 A;Class 10 B']]);
        $path = tempnam(sys_get_temp_dir(), 'organization-template-');
        try {
            IOFactory::createWriter($sheet, 'Xlsx')->save($path);
            return response()->streamDownload(function () use ($path) { readfile($path); unlink($path); }, 'organization-students.xlsx');
        } finally {
            $sheet->disconnectWorksheets();
        }
    }

    private function rows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (($reader->listWorksheetInfo($path)[0]['totalRows'] ?? 0) > 501) {
            throw ValidationException::withMessages(['file' => 'Import at most 500 students at a time.']);
        }
        $sheet = $reader->load($path);
        try {
            $rows = $sheet->getActiveSheet()->toArray(null, false, false, false);
        } finally {
            $sheet->disconnectWorksheets();
        }
        if (array_map('strtolower', array_map('trim', array_slice($rows[0] ?? [], 0, 3))) !== ['name', 'email', 'classes']) {
            throw ValidationException::withMessages(['file' => 'First row must be: name, email, classes.']);
        }
        return array_values(array_filter(array_slice($rows, 1), fn ($row) => count(array_filter($row, fn ($value) => filled($value))) > 0));
    }

    private function validateRows(array $rows, \App\Models\Vendor $vendor): array
    {
        $classes = $vendor->organizationClasses()->pluck('id', 'name');
        $seen = [];
        $results = [];
        foreach ($rows as $index => $row) {
            [$name, $email, $classList] = array_pad(array_slice($row, 0, 3), 3, '');
            $name = trim((string) $name);
            $email = strtolower(trim((string) $email));
            $names = array_values(array_unique(array_filter(array_map('trim', explode(';', (string) $classList)))));
            $errors = [];
            if ($name === '' || mb_strlen($name) > 255) $errors[] = 'Invalid name';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors[] = 'Invalid email';
            if (isset($seen[$email])) $errors[] = 'Duplicate email in file';
            foreach ($names as $className) if (!$classes->has($className)) $errors[] = "Unknown class: {$className}";
            $existing = User::where('email', $email)->first();
            if ($existing?->hasRole(['admin', 'super-admin', 'teacher', 'vendor'])) $errors[] = 'Existing staff account';
            if ($existing?->hasVendorAccess($vendor->id)) $errors[] = 'Already in this organization';
            $results[] = ['row' => $index + 2, 'name' => $name, 'email' => $email, 'classes' => $names,
                'class_ids' => array_map(fn ($name) => $classes[$name] ?? null, $names),
                'existing' => (bool) $existing, 'errors' => $errors];
            $seen[$email] = true;
        }
        return $results;
    }

    public function preview(Request $request)
    {
        $vendor = $this->managedVendor();
        $request->validate(['file' => 'required|file|max:2048|mimes:csv,txt,xlsx']);
        $previous = $request->session()->get('organization_import');
        if (str_starts_with($previous['path'] ?? '', 'organization-imports/')) Storage::disk('local')->delete($previous['path']);
        $path = $request->file('file')->store('organization-imports', 'local');
        try {
            $results = $this->validateRows($this->rows(Storage::disk('local')->path($path)), $vendor);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        $request->session()->put('organization_import', ['vendor_id' => $vendor->id, 'path' => $path,
            'hash' => hash_file('sha256', Storage::disk('local')->path($path))]);
        return back()->with('import_preview', $results);
    }

    public function import(Request $request)
    {
        $vendor = $this->managedVendor();
        $pending = $request->session()->get('organization_import');
        abort_unless($pending && $pending['vendor_id'] === $vendor->id && Storage::disk('local')->exists($pending['path']), 422);
        $path = Storage::disk('local')->path($pending['path']);
        abort_unless(hash_file('sha256', $path) === $pending['hash'], 422);
        $rows = $this->validateRows($this->rows($path), $vendor);
        if (collect($rows)->contains(fn ($row) => $row['errors'])) return back()->with('import_preview', $rows)->with('error', 'Correct the errors before importing.');
        if (collect($rows)->contains(fn ($row) => !$row['existing'])) $this->assertMailReady();
        $failed = [];
        foreach ($rows as $row) {
            $user = DB::transaction(function () use ($row, $vendor) {
                $user = User::firstOrCreate(['email' => $row['email']], [
                    'name' => $row['name'], 'password' => Hash::make(Str::random(64)),
                    'registered_via' => 'organization_import', 'portal_access' => 'both',
                ]);
                if (!$user->hasRole('student')) $user->assignRole('student');
                if ($user->portal_access !== 'both') $user->update(['portal_access' => 'both']);
                $vendor->studentAccess()->syncWithoutDetaching([$user->id => ['granted_by' => auth()->id()]]);
                $user->organizationClasses()->syncWithoutDetaching($row['class_ids']);
                return $user;
            });
            if ($user->wasRecentlyCreated) {
                if (!$this->sendInvitation($vendor->id, $user)) $failed[] = $user->email;
            }
        }
        Storage::disk('local')->delete($pending['path']);
        $request->session()->forget('organization_import');
        return back()->with('success', count($rows) . (count($rows) === 1 ? ' student imported.' : ' students imported.'))
            ->with('import_failed', $failed);
    }

    public function resendInvitation(User $user)
    {
        $vendor = $this->managedVendor();
        abort_unless($user->hasVendorAccess($vendor->id) || $vendor->managers()->where('users.id', $user->id)->exists()
            || $vendor->teachers()->where('users.id', $user->id)->exists(), 404);
        $this->assertMailReady('invitation');
        if (!$this->sendInvitation($vendor->id, $user)) return back()->with('error', 'Invitation email failed. You can retry from this list.');
        return back()->with('success', 'Invitation sent.');
    }

    private function sendInvitation(int $vendorId, User $user): bool
    {
        try { $sent = Password::sendResetLink(['email' => $user->email]) === Password::RESET_LINK_SENT; }
        catch (\Throwable $exception) { $sent = false; }
        if ($sent) DB::table('organization_failed_invitations')->where('vendor_id', $vendorId)->where('user_id', $user->id)->delete();
        else DB::table('organization_failed_invitations')->updateOrInsert(
            ['vendor_id' => $vendorId, 'user_id' => $user->id], ['failed_at' => now()]
        );
        return $sent;
    }

    private function assertMailReady(string $field = 'file'): void
    {
        if (config('mail.default') !== 'smtp' || !filled(config('mail.mailers.smtp.host'))
            || !filled(config('mail.from.address')) || !filled(Setting::get('mail_invites_verified_at'))) {
            throw ValidationException::withMessages([$field => 'Invitations are unavailable until a Skillspot admin verifies email delivery.'])->errorBag($field === 'email' ? 'staff' : 'default');
        }
    }
}
