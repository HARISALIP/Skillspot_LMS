<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonContentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_written_lesson_accepts_full_content(): void
    {
        $course = Course::create(['title' => 'Demo', 'slug' => 'demo', 'category' => 'Web Development']);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Introduction']);
        $content = str_repeat('An instructive paragraph. ', 100);

        $lesson = $section->lessons()->create(['title' => 'Lesson', 'type' => 'text', 'content' => $content]);

        $this->assertSame($content, $lesson->fresh()->content);
    }
}
