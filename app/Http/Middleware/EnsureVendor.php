<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class EnsureVendor
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return redirect('/login');

        // Admins pass freely
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return $next($request);
        }

        // Teacher role
        if ($user->hasRole('teacher')) {
            $vendor = Vendor::whereHas('teachers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->first();
            if (!$vendor) {
                Auth::logout();
                $request->session()->invalidate();
                return redirect('/login')->withErrors([
                    'login' => 'Your teacher account is not assigned to any active vendor. Contact admin.'
                ]);
            }
            // Set session vendor if not already set
            if (!session('active_vendor_id')) {
                session(['active_vendor_id' => $vendor->id]);
            }
            return $next($request);
        }

        // Vendor role
        if ($user->hasRole('vendor')) {
            $vendors = Vendor::whereHas('managers', fn($q) => $q->where('user_id', $user->id))
                ->where('status','active')->get();

            if ($vendors->isEmpty()) {
                Auth::logout();
                $request->session()->invalidate();
                return redirect('/login')->withErrors([
                    'login' => 'Your vendor account is not set up or is inactive. Contact admin.'
                ]);
            }

            $activeVendorId = session('active_vendor_id');
            if (!$activeVendorId || !$vendors->contains('id', $activeVendorId)) {
                if ($vendors->count() === 1) {
                    session(['active_vendor_id' => $vendors->first()->id]);
                } else {
                    if (!$request->routeIs('vendor.pick')) {
                        return redirect()->route('vendor.pick');
                    }
                }
            }
            return $next($request);
        }

        abort(403, 'Access denied.');
    }
}
