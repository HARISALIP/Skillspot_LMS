<?php

namespace App\Http\Middleware;

use App\Models\Course;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreatorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $status = $user?->instructorProfile()->value('status');
        abort_if($status === 'suspended' && !$user->hasRole(['admin', 'super-admin']), 403);
        $legacyTeacher = $user?->hasRole('teacher') && Course::where('primary_instructor_id', $user->id)->exists();
        abort_unless($user?->hasRole(['admin', 'super-admin']) || in_array($status, ['pending', 'approved', 'rejected'], true) || $legacyTeacher, 403);

        return $next($request);
    }
}
