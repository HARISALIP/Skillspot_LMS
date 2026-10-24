<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TeacherOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }
        $vendorId = session('active_vendor_id');
        if ($user && $vendorId && (
            ($user->hasRole('teacher') && \DB::table('vendor_teachers')->where('vendor_id', $vendorId)->where('user_id', $user->id)->exists())
            || ($user->hasRole('vendor') && \DB::table('vendor_users')->where('vendor_id', $vendorId)->where('user_id', $user->id)->exists())
        )) {
            $course = $request->route('course');
            if ($course instanceof \App\Models\Course && !$course->canManageContent($user)) abort(403);
            return $next($request);
        }

        abort(403, 'Access denied. Teacher or Admin role required.');
    }
}
