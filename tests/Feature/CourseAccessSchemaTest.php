<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
