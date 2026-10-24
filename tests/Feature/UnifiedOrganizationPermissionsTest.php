<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\OrganizationClass;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedOrganizationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_route_does_not_collide_with_public_asset_directory(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('vendor'));
        $this->get('/vendor/pdfjs/pdf.min.js')->assertRedirect('/assets/pdfjs/pdf.min.js');
    }

    public function test_organization_teacher_management_page_renders_with_admin_navigation(): void
    {
        foreach (['vendor', 'teacher'] as $role) Role::findOrCreate($role, 'web');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $org = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Alpha', 'status' => 'active']);

        $this->actingAs($owner)->withSession(['active_vendor_id' => $org->id])
            ->get(route('vendor.teachers'))->assertOk()->assertSeeText('Teacher access');
    }

    public function test_teacher_requires_staff_membership_and_course_ownership_or_assignment(): void
    {
        foreach (['vendor', 'teacher'] as $role) Role::findOrCreate($role, 'web');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $other = User::factory()->create();
        $other->assignRole('teacher');
        $org = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Alpha', 'status' => 'active']);
        $otherOrg = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Beta', 'status' => 'active']);
        $org->teachers()->attach([$teacher->id, $other->id]);
        $course = Course::create(['title' => 'Private class', 'vendor_id' => $org->id, 'primary_instructor_id' => $teacher->id]);
        $unassigned = Course::create(['title' => 'Not assigned', 'vendor_id' => $org->id]);
        $foreign = Course::create(['title' => 'Other organization', 'vendor_id' => $otherOrg->id]);

        $this->assertSame($teacher->id, $course->primary_instructor_id);
        $this->assertTrue($org->teachers()->where('users.id', $teacher->id)->exists(), 'Teacher staff pivot missing');
        $this->assertTrue($course->canManageContent($teacher), 'Publisher access missing');
        $this->assertFalse($unassigned->canManageContent($teacher));
        $this->actingAs($teacher)->get(route('instructor.courses.show', $unassigned))->assertForbidden();
        $this->get(route('instructor.courses.show', $foreign))->assertForbidden();
        $this->actingAs($owner)->withSession(['active_vendor_id' => $org->id])
            ->post(route('vendor.courses.teachers.assign', $unassigned), ['user_id' => $teacher->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('course_teachers', ['course_id' => $unassigned->id, 'user_id' => $teacher->id]);
        $this->assertTrue($unassigned->canManageContent($teacher), 'Course assignment access missing');
        $this->actingAs($owner)->post(route('vendor.courses.teachers.assign', $unassigned), ['user_id' => $other->id])->assertRedirect();
        $this->actingAs($teacher)->post(route('vendor.courses.teachers.assign', $unassigned), ['user_id' => $teacher->id])->assertForbidden();
        $this->actingAs($owner)->delete(route('vendor.courses.teachers.remove', [$unassigned, $teacher]))->assertRedirect();
        $this->assertFalse($unassigned->canManageContent($teacher));
        $org->teachers()->detach($teacher);
        $this->assertFalse($course->canManageContent($teacher));
    }

    public function test_standalone_live_class_belongs_to_creator_or_organization_manager(): void
    {
        foreach (['vendor', 'teacher'] as $role) Role::findOrCreate($role, 'web');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $creator = User::factory()->create();
        $creator->assignRole('teacher');
        $colleague = User::factory()->create();
        $colleague->assignRole('teacher');
        $org = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Alpha', 'status' => 'active']);
        $org->teachers()->attach([$creator->id, $colleague->id]);
        $live = LiveClass::create(['vendor_id' => $org->id, 'created_by' => $creator->id,
            'title' => 'Lesson', 'slug' => 'lesson', 'schedule_type' => 'hours_based',
            'platform' => 'zoom', 'total_hours' => 1, 'status' => 'active']);

        $this->actingAs($colleague)->get(route('vendor.live_classes.show', $live))->assertForbidden();
        $this->actingAs($creator)->get(route('vendor.live_classes.show', $live))->assertOk();
        $this->actingAs($owner)->get(route('vendor.live_classes.show', $live))->assertOk();
        $org->teachers()->detach($creator);
        $this->actingAs($creator)->get(route('vendor.live_classes.show', $live))->assertRedirect();
    }

    public function test_learner_contexts_keep_organization_and_marketplace_grants_separate(): void
    {
        Role::findOrCreate('student', 'web');
        $owner = User::factory()->create();
        $org = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Alpha', 'status' => 'active']);
        $other = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Beta', 'status' => 'active']);
        $student = User::factory()->create(['portal_access' => 'both']);
        $student->assignRole('student');
        $org->studentAccess()->attach($student);
        $class = OrganizationClass::create(['vendor_id' => $org->id, 'name' => 'Class 10']);
        $class->students()->attach($student);
        $course = Course::create(['title' => 'Class 10 only', 'vendor_id' => $org->id,
            'organization_audience' => 'classes', 'organization_published' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
            'is_published' => true, 'visibility' => 'public', 'is_free' => true]);
        $course->organizationClasses()->attach($class);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id,
            'vendor_id' => $org->id, 'status' => 'completed', 'progress' => 100, 'access_type' => 'lifetime']);
        OrganizationAccess::grant($student, $course, 'organization', 'free');

        $this->actingAs($student)->get(route('organization.dashboard', $org->slug))
            ->assertOk()->assertSeeText('Class 10 only')->assertSeeText('Review lessons');
        $this->get(route('student.dashboard'))->assertOk()->assertDontSeeText('Completed courses');
        $this->get(route('organization.dashboard', $other->slug))->assertForbidden();
        $this->get(route('student.learn', [$course, 'org' => $org->slug]))->assertOk();

        $class->students()->detach($student);
        $this->get(route('student.learn', [$course, 'org' => $org->slug]))->assertForbidden();
        OrganizationAccess::grant($student, $course, 'marketplace', 'free');
        $this->assertTrue(OrganizationAccess::hasChannel($student, $course, 'marketplace'));
        $this->assertSame('lifetime', Enrollment::where('course_id', $course->id)->value('access_type'));
        $this->get(route('organization.dashboard', $org->slug))->assertOk()->assertDontSeeText('Class 10 only');
        $this->get(route('student.dashboard'))->assertOk()->assertSeeText('Completed courses')->assertSeeText('Class 10 only');
        $this->get(route('student.learn', [$course, 'org' => $org->slug]))->assertForbidden();
        $this->get(route('student.learn', $course))->assertOk();
    }

    public function test_legacy_organization_only_student_can_use_shared_player_and_profile(): void
    {
        Role::findOrCreate('student', 'web');
        $owner = User::factory()->create();
        $org = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Alpha', 'status' => 'active']);
        $student = User::factory()->create(['portal_access' => 'vendor_only']);
        $student->assignRole('student');
        $org->studentAccess()->attach($student);
        $course = Course::create(['title' => 'Legacy course', 'vendor_id' => $org->id,
            'organization_audience' => 'all', 'organization_published' => true]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id,
            'vendor_id' => $org->id, 'status' => 'active', 'access_type' => 'lifetime']);
        OrganizationAccess::grant($student, $course, 'organization', 'free');

        $this->actingAs($student)->get(route('organization.dashboard', $org->slug))->assertOk();
        $this->get(route('student.learn', $course))->assertOk()->assertSeeText('Legacy course');
        $this->get(route('student.profile', ['org' => $org->slug]))->assertOk()->assertSeeText('My Profile');
        $this->get(route('student.dashboard'))->assertRedirect();
    }
}
