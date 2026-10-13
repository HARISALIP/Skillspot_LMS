<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_and_save_the_four_tab_course_workspace(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'Design Basics', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true,
            'primary_instructor_id' => $teacher->id,
        ]);
        $course->teachers()->attach($teacher->id, ['added_by' => $teacher->id]);
        Section::create(['course_id' => $course->id, 'title' => 'Introduction', 'order' => 1]);

        $this->actingAs($teacher)->get(route('admin.courses.edit', $course))
            ->assertOk()->assertSee('Overview')->assertSee('Dashboard')
            ->assertSee('Course editor')->assertSee('Meta Tags')->assertSee('Chapters')
            ->assertSee('md:w-2/3', false)->assertSee('w-80', false)
            ->assertSee('1 section')->assertSee('Course content coming soon!');

        $this->put(route('admin.courses.update', $course), [
            'title' => 'Design Basics', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'visibility' => 'draft',
            'instructor_ids' => [$teacher->id], 'tags' => 'Design, Beginner',
            'meta_description' => 'Learn the fundamentals.', 'thumbnail_color' => '#fda4af',
            'upcoming' => 1, 'enforce_lesson_completion' => 1,
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('Design, Beginner', $course->tags);
        $this->assertTrue($course->upcoming);
        $this->assertTrue($course->enforce_lesson_completion);
        $this->assertFalse($course->enable_certification);
    }

    public function test_add_chapter_modal_saves_a_scorm_chapter_and_returns_to_the_editor(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'SCORM Course', 'category' => 'Technology', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true,
            'primary_instructor_id' => $teacher->id,
        ]);
        $course->teachers()->attach($teacher->id, ['added_by' => $teacher->id]);

        $this->actingAs($teacher)->get(route('admin.courses.edit', $course).'#editor')
            ->assertOk()
            ->assertSee('id="addChapterModal"', false)
            ->assertSee('Add Chapter')
            ->assertSee('SCORM Package');

        $this->post(route('admin.courses.sections.store', $course), [
            'title' => 'Interactive package',
            'is_scorm' => '1',
        ])->assertRedirect(route('admin.courses.edit', $course).'#editor');

        $chapter = $course->sections()->firstOrFail();
        $this->assertSame('Interactive package', $chapter->title);
        $this->assertTrue($chapter->is_scorm);
    }

    public function test_enforced_course_blocks_skipping_an_incomplete_lesson(): void
    {
        Role::findOrCreate('student', 'web');
        $student = User::factory()->create();
        $student->assignRole('student');
        $course = Course::create([
            'title' => 'Sequential Course', 'category' => 'General', 'is_free' => true,
            'visibility' => 'public', 'is_published' => true, 'enforce_lesson_completion' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
        ]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start', 'order' => 1]);
        $section->lessons()->create(['title' => 'First', 'type' => 'text', 'order' => 1]);
        $second = $section->lessons()->create(['title' => 'Second', 'type' => 'text', 'order' => 2]);
        CourseService::enrollFree($student, $course);

        $this->actingAs($student)->get(route('student.get-lesson', [$course, $second]))
            ->assertStatus(423)->assertJsonPath('error', 'Complete the previous lesson before opening this one.');
    }
}
