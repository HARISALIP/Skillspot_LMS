<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_use_the_course_workspace_and_students_cannot(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Robotics Fundamentals',
            'slug' => 'robotics-fundamentals',
            'category' => 'Technology',
            'visibility' => 'public',
            'is_published' => true,
        ]);
        Section::create(['course_id' => $course->id, 'title' => 'Start here']);

        $this->actingAs($teacher)->get(route('admin.courses'))
            ->assertOk()
            ->assertSeeText('Robotics Fundamentals')
            ->assertSeeText('Published')
            ->assertSeeText('Create course')
            ->assertSee(route('admin.courses.edit', $course));

        $this->actingAs($student)->get(route('admin.courses'))->assertForbidden();
    }
}
