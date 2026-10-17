<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_stays_inside_selected_course_and_manual_grants_keep_the_vendor(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Enrollment Academy', 'status' => 'active']);
        $selected = Course::create(['title' => 'Selected Course', 'vendor_id' => $vendor->id, 'is_published' => true]);
        $other = Course::create(['title' => 'Other Course', 'is_published' => true]);
        $matchingLearner = User::factory()->create(['name' => 'Search Match']);
        Enrollment::create(['user_id' => $matchingLearner->id, 'course_id' => $other->id, 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.enrollments', [
            'search' => 'Search Match', 'course_id' => $selected->id,
        ]))->assertOk()->assertDontSee($matchingLearner->email);

        $newLearner = User::factory()->create();
        $this->post(route('admin.enrollments.store'), [
            'user_ids' => [$newLearner->id], 'course_id' => $selected->id,
            'access_type' => 'lifetime',
        ])->assertRedirect(route('admin.enrollments'));
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $newLearner->id, 'course_id' => $selected->id, 'vendor_id' => $vendor->id,
        ]);
        $enrollment = Enrollment::where('user_id', $newLearner->id)->where('course_id', $selected->id)->firstOrFail();

        $this->get(route('admin.enrollments'))
            ->assertOk()->assertSee('for="enrollment-course"', false)
            ->assertSee('for="enrollment-access"', false)
            ->assertSee('for="enrollment-expiry"', false)
            ->assertSeeText('Selected Course')
            ->assertSee('aria-label="Revoke '.e($newLearner->name).' access to Selected Course"', false);
        $this->get(route('admin.enrollments.create'))
            ->assertOk()->assertSee('for="studentSearch"', false)
            ->assertSee('for="enrollment-course-select"', false)
            ->assertSee('for="enrollment-notes"', false)
            ->assertSee('name.textContent = u.name', false)
            ->assertDontSee('div.innerHTML', false);
        $this->get(route('admin.enrollments.edit', $enrollment))
            ->assertOk()->assertSeeText('Edit Enrollment')
            ->assertSee('for="edit-enrollment-access"', false)
            ->assertSee('for="edit-enrollment-status"', false)
            ->assertSee(route('admin.enrollments'));
    }
}
