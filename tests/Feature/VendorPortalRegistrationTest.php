<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorPortalRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function portal(): array
    {
        Role::findOrCreate('student', 'web');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Portal Academy', 'status' => 'active']);
        $course = Course::create([
            'title' => 'Portal Basics', 'vendor_id' => $vendor->id, 'is_free' => true,
            'price' => 0, 'organization_published' => true, 'registration_open' => true,
        ]);
        return [$vendor, $course];
    }

    public function test_registration_is_by_invitation_only(): void
    {
        [$vendor, $course] = $this->portal();
        $this->get('/v/'.$vendor->slug.'/register')->assertNotFound();
        $this->post('/v/'.$vendor->slug.'/register/'.$course->id)->assertNotFound();
        $this->get(route('vendor.portal.login', $vendor->slug))
            ->assertOk()->assertSeeText('Ask your organization admin for an invitation.');
        $this->assertDatabaseCount('student_vendor_access', 0);
    }

    public function test_invited_student_can_enroll_in_free_organization_course(): void
    {
        [$vendor, $course] = $this->portal();
        $learner = User::factory()->create(['portal_access' => 'both']);
        $learner->assignRole('student');
        $vendor->studentAccess()->attach($learner->id);

        $this->actingAs($learner)->get(route('vendor.portal.courses.show', [$vendor->slug, $course]))->assertOk();
        $this->post(route('vendor.portal.courses.enroll-free', [$vendor->slug, $course]))
            ->assertRedirect(route('vendor.portal.batch', [$vendor->slug, $course->id]));
        $this->assertDatabaseHas('course_access_grants', [
            'user_id' => $learner->id, 'course_id' => $course->id, 'channel' => 'organization',
        ]);
        $this->get(route('vendor.portal.batch', [$vendor->slug, $course->id]))->assertOk();
    }

    public function test_unrelated_student_cannot_open_private_portal_or_course(): void
    {
        [$vendor, $course] = $this->portal();
        $learner = User::factory()->create(['portal_access' => 'both']);
        $learner->assignRole('student');

        $this->actingAs($learner)->get(route('vendor.portal.dashboard', $vendor->slug))->assertForbidden();
        $this->get(route('vendor.portal.courses.show', [$vendor->slug, $course]))->assertForbidden();
        $this->get(route('vendor.portal.home', $vendor->slug))->assertOk()->assertDontSeeText('Portal Basics');
    }

    public function test_staff_from_another_organization_cannot_open_the_portal(): void
    {
        [$vendor] = $this->portal();
        $other = Vendor::create(['user_id' => User::factory()->create()->id, 'brand_name' => 'Other Academy', 'status' => 'active']);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $other->teachers()->attach($teacher);
        $this->actingAs($teacher)->get(route('vendor.portal.dashboard', $vendor->slug))->assertForbidden();
    }
}
