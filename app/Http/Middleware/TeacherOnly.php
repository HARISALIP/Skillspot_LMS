<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TeacherOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->hasRole(['teacher', 'admin', 'super-admin'])) {
            return $next($request);
        }

        abort(403, 'Access denied. Teacher or Admin role required.');
    }
}
