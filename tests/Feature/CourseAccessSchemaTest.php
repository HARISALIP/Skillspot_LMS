<?php

namespace Tests\Feature;

use App\Models\Course;
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

    public function test_teacher_can_open_course_editor(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'Demo Course',
            'slug' => 'demo-course',
            'category' => 'Web Development',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSeeText('Sections & Lessons');
    }
}
