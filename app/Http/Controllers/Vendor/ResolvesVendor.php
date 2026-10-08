<?php
namespace App\Http\Controllers\Vendor;

use App\Models\Vendor;

trait ResolvesVendor
{
    protected function vendor(): Vendor
    {
        $user = auth()->user();

        // Admin/super-admin: use session active_vendor_id if set
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $id = session('active_vendor_id');
            if ($id) return Vendor::findOrFail($id);
            return Vendor::firstOrFail();
        }

        // Teacher: resolve from vendor_teachers pivot
        if ($user->hasRole('teacher')) {
            $id = session('active_vendor_id');
            if ($id) {
                $vendor = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                    ->where('id', $id)->first();
                if ($vendor) return $vendor;
            }
            $vendor = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->first();
            if ($vendor) {
                session(['active_vendor_id' => $vendor->id]);
                return $vendor;
            }
            abort(403, 'No vendor assigned to your teacher account.');
        }

        // Vendor user: resolve from vendor_users pivot
        $id = session('active_vendor_id');
        if ($id) {
            $vendor = Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
                ->where('id', $id)->first();
            if ($vendor) return $vendor;
        }

        $vendor = Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
            ->where('status','active')->first();
        if (!$vendor) abort(403, 'No vendor assigned.');
        session(['active_vendor_id' => $vendor->id]);
        return $vendor;
    }

    /** Is current user a teacher (not vendor/admin)? */
    protected function isTeacher(): bool
    {
        return auth()->user()->hasRole('teacher');
    }

    /** Is current user vendor-only (view only)? */
    protected function isVendorOnly(): bool
    {
        $user = auth()->user();
        return $user->hasRole('vendor') && !$user->hasRole(['teacher','admin','super-admin']);
    }
}
