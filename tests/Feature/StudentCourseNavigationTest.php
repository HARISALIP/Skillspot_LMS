<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_browse_open_and_start_a_free_course(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Web Development',
            'slug' => 'web-development',
            'category' => 'Web Development',
            'visibility' => 'public',
            'is_published' => true,
            'is_free' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.browse'))
            ->assertOk()
            ->assertSee('Find your next skill — 1 course available')
            ->assertSee(route('student.course-detail', $course->slug));

        $this->get(route('student.course-detail', $course->slug))
            ->assertOk()
            ->assertSeeText($course->title);

        $this->post(route('student.enroll-free', $course))
            ->assertRedirect(route('student.learn', $course));

        $this->get(route('student.learn', $course))
            ->assertOk()
            ->assertSeeText($course->title);
    }
}
