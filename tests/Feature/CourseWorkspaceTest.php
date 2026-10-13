<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertSee('data-course-tab="overview"', false)
            ->assertSee('data-course-panel="settings"', false)
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

    public function test_teacher_can_open_a_workspace_with_legacy_double_encoded_course_lists(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'Legacy Course', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true,
            'primary_instructor_id' => $teacher->id,
        ]);
        $course->teachers()->attach($teacher->id, ['added_by' => $teacher->id]);
        DB::table('courses')->where('id', $course->id)->update([
            'requirements' => json_encode(json_encode(['Basic computer skills'])),
            'outcomes' => json_encode(json_encode(['Build a portfolio'])),
        ]);

        $this->actingAs($teacher)->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('Basic computer skills')
            ->assertSee('Build a portfolio');
    }

    public function test_add_chapter_modal_saves_a_regular_chapter_and_returns_to_the_editor(): void
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
            ->assertSee('SCORM Package')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="scorm_package"', false)
            ->assertSee('Upload a ZIP file');

        $this->post(route('admin.courses.sections.store', $course), [
            'title' => 'Interactive package',
        ])->assertRedirect(route('admin.courses.edit', $course).'#editor');

        $chapter = $course->sections()->firstOrFail();
        $this->assertSame('Interactive package', $chapter->title);
        $this->assertFalse($chapter->is_scorm);
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

    public function test_vue_workspace_persists_block_documents_without_overwriting_a_legacy_lesson(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'Block Course', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true, 'primary_instructor_id' => $teacher->id,
        ]);
        $course->teachers()->attach($teacher->id, ['added_by' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start', 'order' => 1]);
        $lesson = $section->lessons()->create(['title' => 'Legacy copy', 'type' => 'text', 'content' => '<p>Old content</p>', 'order' => 1]);
        $blocks = ['time' => 1, 'version' => '2.29.0', 'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'New content']]]];

        $this->actingAs($teacher)->patchJson(route('instructor.api.courses.lessons.update', [$course, $section, $lesson]), [
            'block_document' => $blocks,
        ])->assertOk()->assertJsonPath('blocks.blocks.0.data.text', 'New content');

        $lesson->refresh();
        $this->assertSame('<p>Old content</p>', $lesson->content);
        $this->assertSame(1, $lesson->block_document['time']);
        $this->assertSame('2.29.0', $lesson->block_document['version']);
        $this->assertSame('New content', $lesson->block_document['blocks'][0]['data']['text']);
        $this->actingAs($teacher)->get(route('instructor.courses.show', $course))
            ->assertOk()->assertSee('frappe-course-workspace', false);
        $this->actingAs($teacher)->getJson(route('instructor.api.courses.show', $course))
            ->assertOk()->assertJsonPath('sections.0.lessons.0.blocks.blocks.0.data.text', 'New content');
    }

    public function test_teacher_cannot_open_another_instructors_workspace(): void
    {
        Role::findOrCreate('teacher', 'web');
        $owner = User::factory()->create();
        $owner->assignRole('teacher');
        $other = User::factory()->create();
        $other->assignRole('teacher');
        $course = Course::create([
            'title' => 'Private Course', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true, 'primary_instructor_id' => $owner->id,
        ]);
        $course->teachers()->attach($owner->id, ['added_by' => $owner->id]);

        $this->actingAs($other)->getJson(route('instructor.api.courses.show', $course))->assertForbidden();
    }

    public function test_workspace_outline_can_move_a_lesson_between_chapters(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create([
            'title' => 'Outline Course', 'category' => 'Design', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'is_free' => true, 'primary_instructor_id' => $teacher->id,
        ]);
        $course->teachers()->attach($teacher->id, ['added_by' => $teacher->id]);
        $first = Section::create(['course_id' => $course->id, 'title' => 'First', 'order' => 1]);
        $second = Section::create(['course_id' => $course->id, 'title' => 'Second', 'order' => 2]);
        $lesson = $first->lessons()->create(['title' => 'Move me', 'type' => 'text', 'order' => 1]);

        $this->actingAs($teacher)->postJson(route('instructor.api.courses.outline', $course), [
            'sections' => [
                ['id' => $second->id, 'lessons' => [$lesson->id]],
                ['id' => $first->id, 'lessons' => []],
            ],
        ])->assertOk();

        $lesson->refresh();
        $this->assertSame($second->id, $lesson->section_id);
        $this->assertSame(1, $lesson->order);
    }
}
