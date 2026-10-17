<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseAccessSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_editor_can_load_allowed_users(): void
    {
        $course = new Course();
        $course->id = 1;

        $this->assertCount(0, $course->allowedUsers()->get());
    }

    public function test_creator_can_open_owned_studio_editor_but_not_admin_editor(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        InstructorProfile::create(['user_id' => $teacher->id, 'status' => 'approved']);
        $course = Course::create([
            'title' => 'Demo Course',
            'slug' => 'demo-course',
            'category' => 'Web Development',
            'primary_instructor_id' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('instructor.courses.show', $course))
            ->assertOk()
            ->assertSee('frappe-course-workspace');
        $this->actingAs($teacher)->get(route('admin.courses.edit', $course))->assertForbidden();
    }
}
