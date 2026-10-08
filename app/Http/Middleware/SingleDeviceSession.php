<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SingleDeviceSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('student')) {
            $sessionId = $request->session()->getId();

            // Check if this session still exists in DB
            $exists = DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->exists();

            if (!$exists) {
                // Session was killed by another login — force logout
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Try to detect vendor slug for redirect
                $slug = $request->route('slug');
                if ($slug) {
                    return redirect()->route('vendor.portal.login', $slug)
                        ->with('error', 'You were logged out because your account was signed in on another device.');
                }

                return redirect('/login')
                    ->with('error', 'You were logged out because your account was signed in on another device.');
            }
        }

        return $next($request);
    }
}
