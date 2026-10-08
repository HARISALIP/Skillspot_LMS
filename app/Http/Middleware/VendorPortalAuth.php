<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return $next($request);
    }
}
