<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationAccess
{
    public static function member(User $user, Course $course): bool
    {
        return $course->vendor_id !== null && DB::table('student_vendor_access')
            ->where('vendor_id', $course->vendor_id)->where('user_id', $user->id)->exists();
    }

    public static function eligible(User $user, Course $course): bool
    {
        if (!$course->vendor_id || !$course->organization_published || $course->vendor?->status !== 'active'
            || !self::member($user, $course)) return false;
        if ($course->organization_audience === 'all') return true;
        if ($course->allowedUsers()->where('users.id', $user->id)->exists()) return true;

        return DB::table('organization_class_courses as targets')
            ->join('organization_classes as classes', 'classes.id', '=', 'targets.organization_class_id')
            ->join('organization_class_students as members', 'members.organization_class_id', '=', 'classes.id')
            ->where('targets.course_id', $course->id)->where('classes.vendor_id', $course->vendor_id)
            ->where('members.user_id', $user->id)->exists();
    }

    public static function grant(User $user, Course $course, string $channel, string $source, ?int $orderId = null, ?int $actorId = null): void
    {
        DB::table('course_access_grants')->updateOrInsert(
            ['user_id' => $user->id, 'course_id' => $course->id, 'channel' => $channel, 'source' => $source],
            ['source' => $source, 'payment_order_id' => $orderId, 'granted_by' => $actorId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public static function grantManual(User $user, Course $course, int $actorId): void
    {
        DB::transaction(function () use ($user, $course, $actorId) {
            if ($course->vendor_id) {
                DB::table('student_vendor_access')->updateOrInsert(
                    ['user_id' => $user->id, 'vendor_id' => $course->vendor_id],
                    ['granted_by' => $actorId, 'updated_at' => now(), 'created_at' => now()]
                );
                $course->allowedUsers()->syncWithoutDetaching([$user->id => ['added_by' => $actorId]]);
            }
            $enrollment = Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['vendor_id' => $course->vendor_id, 'amount_paid' => 0, 'payment_method' => 'admin_grant', 'status' => 'active', 'granted_by' => $actorId]
            );
            if (!in_array($enrollment->status, ['active', 'completed'], true)) $enrollment->update(['status' => 'active']);
            self::grant($user, $course, $course->vendor_id ? 'organization' : 'marketplace', 'admin', null, $actorId);
        });
    }

    public static function revokeManual(User $user, Course $course): void
    {
        DB::transaction(function () use ($user, $course) {
            $course->allowedUsers()->detach($user->id);
            DB::table('course_access_grants')->where('user_id', $user->id)->where('course_id', $course->id)
                ->where('channel', $course->vendor_id ? 'organization' : 'marketplace')->where('source', 'admin')->delete();
            if (!DB::table('course_access_grants')->where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
                Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->update(['status' => 'revoked']);
            }
        });
    }

    public static function hasChannel(User $user, Course $course, string $channel): bool
    {
        $enrolled = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
        if (!$enrolled || !DB::table('course_access_grants')->where('user_id', $user->id)
            ->where('course_id', $course->id)->where('channel', $channel)->exists()) return false;
        if ($channel === 'organization') {
            return self::eligible($user, $course);
        }
        return !in_array($course->marketplace_status, ['suspended', 'archived', 'rejected'], true);
    }
}
