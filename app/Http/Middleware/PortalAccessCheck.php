<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAccessCheck
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return $next($request);

        // Admins, teachers, vendors pass freely
        if ($user->hasRole(['super-admin', 'admin', 'teacher', 'vendor'])) {
            return $next($request);
        }

        // vendor_only students cannot access main Skillspot pages
        if ($user->hasRole('student') && $user->portal_access === 'vendor_only') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to their vendor portal if we can find it
            $vendors = $user->vendorAccess()->where('vendors.status', 'active')->get();
            if ($vendors->count() === 1) {
                return redirect()->route('vendor.portal.login', $vendors->first()->slug)
                    ->with('error', 'Your account is not enabled for this portal. Please use your institution portal.');
            }

            $slug2 = request()->route('slug');
            if ($slug2) {
                return redirect()->route('vendor.portal.login', $slug2)
                    ->with('error', 'Your account is not enabled for this portal.');
            }
            return redirect('/login')
                ->with('error', 'Your account is not enabled for this portal. Please use your institution portal.');
        }

        return $next($request);
    }
}
