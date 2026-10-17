<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\BatchAttendance;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorPortalRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_registration_uses_the_rendered_challenge_and_enrolls_the_selected_free_course(): void
    {
        [$vendor, $course] = $this->portalWithFreeCourse();

        $page = $this->get(route('vendor.portal.register.general', $vendor->slug))
            ->assertOk()
            ->assertSee('for="portal-register-course"', false)
            ->assertSee('name="course_id"', false);
        [$token, $answer] = $this->challengeFrom($page->getContent());

        $this->post(route('vendor.portal.register.general.submit', $vendor->slug), [
            'name' => 'Portal Learner',
            'email' => 'portal-learner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_id' => $course->id,
            '_hv_token' => $token,
            '_hv_answer' => $answer,
        ])->assertRedirect(route('vendor.portal.dashboard', $vendor->slug));

        $user = User::where('email', 'portal-learner@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('student_vendor_access', ['user_id' => $user->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'course_id' => $course->id, 'status' => 'active']);
        $this->get(route('vendor.portal.dashboard', $vendor->slug))->assertOk()->assertSeeText('Portal Basics');

        $section = Section::create(['course_id' => $course->id, 'title' => 'Start here']);
        $liveLesson = Lesson::create(['section_id' => $section->id, 'title' => 'Welcome live class', 'type' => 'live']);
        Lesson::create(['section_id' => $section->id, 'title' => 'Workbook', 'type' => 'document',
            'resources' => [['name' => 'Starter PDF', 'url' => 'https://example.test/starter.pdf']]]);
        BatchAttendance::create(['lesson_id' => $liveLesson->id, 'course_id' => $course->id,
            'user_id' => $user->id, 'vendor_id' => $vendor->id, 'status' => 'present']);
        $this->get(route('vendor.portal.batch', [$vendor->slug, $course->id]))
            ->assertOk()->assertSeeText('Back to dashboard')->assertSeeText('Starter PDF')
            ->assertSeeText('100% attendance')->assertSeeText('Present');
    }

    public function test_course_registration_renders_a_matching_challenge_and_enrolls_the_learner(): void
    {
        [$vendor, $course] = $this->portalWithFreeCourse();

        $page = $this->get(route('vendor.portal.register', [$vendor->slug, $course->id]))
            ->assertOk()
            ->assertSee('Quick Check')
            ->assertSee('for="portal-course-register-email"', false);
        [$token, $answer] = $this->challengeFrom($page->getContent());

        $this->post(route('vendor.portal.register.submit', [$vendor->slug, $course->id]), [
            'name' => 'Course Learner',
            'email' => 'course-learner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_hv_token' => $token,
            '_hv_answer' => $answer,
        ])->assertRedirect(route('vendor.portal.dashboard', $vendor->slug));

        $user = User::where('email', 'course-learner@example.test')->firstOrFail();
        $this->assertDatabaseHas('student_vendor_access', ['user_id' => $user->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('enrollments', ['user_id' => $user->id, 'course_id' => $course->id, 'status' => 'active']);
        $this->get(route('vendor.portal.dashboard', $vendor->slug))->assertOk()->assertSeeText('Portal Basics');
    }

    public function test_an_unrelated_learner_cannot_open_another_vendors_private_portal_dashboard(): void
    {
        [$vendor] = $this->portalWithFreeCourse();
        $learner = User::factory()->create();
        $learner->assignRole('student');

        $this->actingAs($learner)->get(route('vendor.portal.dashboard', $vendor->slug))->assertForbidden();
        $this->actingAs($learner)->get(route('vendor.portal.home', $vendor->slug))
            ->assertOk()->assertSeeText('Skillspot Dashboard')->assertDontSeeText('My Dashboard');
    }

    private function portalWithFreeCourse(): array
    {
        Role::findOrCreate('student', 'web');
        Setting::set('human_verify_register', '1', 'security', 'boolean');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Portal Academy', 'status' => 'active']);
        $course = Course::create([
            'title' => 'Portal Basics',
            'vendor_id' => $vendor->id,
            'is_free' => true,
            'price' => 0,
            'is_published' => true,
            'registration_open' => true,
        ]);

        return [$vendor, $course];
    }

    private function challengeFrom(string $html): array
    {
        preg_match("/document\.getElementById\('hvToken'\)\.value = '([^']+)'/", $html, $match);
        $this->assertNotEmpty($match[1] ?? null, 'The page did not render a human-verification token.');
        [, $answer] = explode(':', base64_decode($match[1], true), 3);

        return [$match[1], $answer];
    }
}
