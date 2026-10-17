<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Like Laravel's `auth` middleware but redirects to the vendor portal
 * login page instead of the main /login when unauthenticated.
 *
 * Applied to:  Route::prefix('v/{slug}') auth-protected group
 */
class VendorPortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            $slug = $request->route('slug');

            // Store the intended URL so we can redirect back after login
            session(['url.intended' => $request->fullUrl()]);

            if ($slug) {
                return redirect()->route('vendor.portal.login', $slug)
                    ->with('info', 'Please log in to continue.');
            }

            return redirect('/login');
        }

        $user = Auth::user();
        if (!$user->hasRole(['super-admin', 'admin', 'teacher', 'vendor'])) {
            $hasAccess = DB::table('student_vendor_access')
                ->join('vendors', 'vendors.id', '=', 'student_vendor_access.vendor_id')
                ->where('student_vendor_access.user_id', $user->id)
                ->where('vendors.slug', $request->route('slug'))
                ->where('vendors.status', 'active')
                ->exists();
            abort_unless($hasAccess, 403, 'You do not have access to this portal.');
        }

        return $next($request);
    }
}
