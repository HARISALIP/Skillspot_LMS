<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\OrganizationClass;
use App\Models\Section;
use App\Models\Upload;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationReleaseRepairTest extends TestCase
{
    use RefreshDatabase;

    private function organization(): array
    {
        foreach (['vendor', 'teacher', 'student'] as $role) Role::findOrCreate($role, 'web');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Repair Academy', 'status' => 'active']);
        $student = User::factory()->create(['portal_access' => 'both']);
        $student->assignRole('student');
        $vendor->studentAccess()->attach($student->id);
        $course = Course::create(['title' => 'Repair Course', 'vendor_id' => $vendor->id,
            'organization_published' => true, 'registration_open' => true, 'is_free' => true]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter One', 'order' => 1]);
        $lesson = Lesson::create(['section_id' => $section->id, 'title' => 'Reading One', 'type' => 'text', 'order' => 1]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'completed', 'progress' => 100]);
        OrganizationAccess::grant($student, $course, 'organization', 'free');
        return [$owner, $vendor, $student, $course, $section, $lesson];
    }

    public function test_completed_course_stays_in_organization_portal_with_lesson_links(): void
    {
        [, $vendor, $student, $course, , $lesson] = $this->organization();
        $this->actingAs($student)->get(route('vendor.portal.dashboard', $vendor->slug))
            ->assertOk()->assertSeeText('Repair Course')->assertSeeText('Completed')->assertSeeText('Review lessons');
        $this->get(route('vendor.portal.batch', [$vendor->slug, $course]))
            ->assertOk()->assertSeeText('Reading One')->assertSee('lesson-'.$lesson->id);
        $this->get(route('student.learn', $course))->assertOk();
    }

    public function test_local_private_video_uses_actual_disk_and_denies_revoked_class(): void
    {
        [, $vendor, $student, $course, $section] = $this->organization();
        $class = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $class->students()->attach($student);
        $course->update(['organization_audience' => 'classes']);
        $course->organizationClasses()->attach($class);
        Storage::fake('local');
        $path = 'uploads/course-videos/lesson.mp4';
        Storage::disk('local')->put($path, 'private-video-bytes');
        Upload::create(['original_name' => 'lesson.mp4', 'file_name' => 'lesson.mp4', 'mime_type' => 'video/mp4',
            'extension' => 'mp4', 'size' => 19, 'disk' => 'local', 'path' => $path, 'type' => 'video',
            'folder' => 'course-videos', 'is_public' => false]);
        $video = Lesson::create(['section_id' => $section->id, 'title' => 'Private Video', 'type' => 'video',
            'video_storage' => 'r2', 'video_r2_path' => $path, 'order' => 2]);

        $this->actingAs($student)->get(route('student.get-lesson', [$course, $video]))
            ->assertOk()->assertJsonPath('video_url', route('video.file', $video));
        $this->get(route('video.stream', $video))->assertOk()->assertJsonPath('url', route('video.file', $video));
        $this->get(route('video.file', $video))->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $class->students()->detach($student);
        $this->get(route('video.stream', $video))->assertForbidden();
        $this->get(route('video.file', $video))->assertForbidden();
        OrganizationAccess::grant($student, $course, 'marketplace', 'free');
        $this->get(route('video.file', $video))->assertOk();
        Storage::disk('local')->delete($path);
        $this->get(route('student.get-lesson', [$course, $video]))
            ->assertOk()->assertJsonPath('video_unavailable', true);
        $this->get(route('video.stream', $video))->assertStatus(503)
            ->assertJsonPath('error', 'Video temporarily unavailable.');
        $video->update(['video_r2_path' => 'legacy-r2/missing.mp4']);
        $this->get(route('video.stream', $video))->assertStatus(503);
    }

    public function test_legacy_pdf_upload_url_uses_current_host_and_course_access(): void
    {
        [, , $student, $course, $section] = $this->organization();
        Storage::fake('local');
        Storage::disk('local')->put('uploads/course-materials/handout.pdf', '%PDF-1.4');
        $upload = Upload::create(['original_name' => 'handout.pdf', 'file_name' => 'handout.pdf',
            'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 8,
            'disk' => 'local', 'path' => 'uploads/course-materials/handout.pdf',
            'type' => 'pdf', 'folder' => 'course-materials', 'is_public' => false,
            'used_in' => ['course_id' => $course->id]]);
        $pdf = Lesson::create(['section_id' => $section->id, 'title' => 'Old PDF', 'type' => 'pdf',
            'content' => 'http://old.example.test/course-media/'.$course->id.'/'.$upload->uuid, 'order' => 2]);

        $this->actingAs($student)->get(route('student.get-lesson', [$course, $pdf]))
            ->assertOk()->assertJsonPath('content', route('media.course', [$course, $upload->uuid]));
        $this->get(route('media.course', [$course, $upload->uuid]))->assertOk();
    }

    public function test_organization_staff_share_studio_but_not_other_organizations(): void
    {
        [$owner, $vendor, , $course] = $this->organization();
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $vendor->teachers()->attach($teacher);
        $course->teachers()->attach($teacher);
        $other = User::factory()->create();
        $other->assignRole('vendor');
        Vendor::create(['user_id' => $other->id, 'brand_name' => 'Other School', 'status' => 'active']);
        $class = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);

        foreach ([$owner, $teacher] as $staff) {
            $this->actingAs($staff)->get(route('instructor.courses.show', $course))->assertOk()
                ->assertSeeText('Organization access')->assertSee('name="organization_published"', false)
                ->assertSee('name="organization_audience"', false);
            $this->get(route('instructor.api.courses.show', $course))->assertOk();
        }
        $this->actingAs($teacher)->patchJson(route('instructor.api.courses.update', $course), [
            'organization_audience' => 'classes', 'class_ids' => [$class->id],
            'organization_published' => true, 'is_free' => true,
        ])->assertOk();
        $this->assertSame([$class->id], $course->organizationClasses()->pluck('organization_classes.id')->all());
        $this->actingAs($other)->get(route('instructor.courses.show', $course))->assertForbidden();
        $this->patchJson(route('instructor.api.courses.update', $course), ['title' => 'Intrusion'])->assertForbidden();
        $this->actingAs($owner)->get(route('vendor.courses.edit', $course))
            ->assertRedirect(route('instructor.courses.show', $course));
    }

    public function test_failed_invitation_remains_visible_until_successful_retry(): void
    {
        [$owner, $vendor, $student] = $this->organization();
        DB::table('organization_failed_invitations')->insert([
            'vendor_id' => $vendor->id, 'user_id' => $student->id, 'failed_at' => now(),
        ]);
        $this->actingAs($owner)->get(route('vendor.organization.index'))->assertOk()->assertSeeText('Retry invitation');
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'invites@example.test']);
        \App\Models\Setting::set('mail_invites_verified_at', now()->toIso8601String());
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);
        $this->post(route('vendor.organization.invitations.resend', $student))->assertRedirect();
        $this->assertDatabaseMissing('organization_failed_invitations', ['vendor_id' => $vendor->id, 'user_id' => $student->id]);
        $this->get(route('vendor.organization.index'))->assertOk()->assertDontSeeText('Retry invitation');
    }
}
