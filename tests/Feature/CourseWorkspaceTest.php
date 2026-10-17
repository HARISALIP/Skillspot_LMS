<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
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
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
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
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
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
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
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

    public function test_lesson_creation_can_stay_inside_the_course_editor(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $course = Course::create(['title' => 'Inline Lessons', 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Introduction', 'order' => 1]);

        $this->actingAs($teacher)->get(route('admin.courses.edit', $course))
            ->assertOk()
            ->assertSee('openLessonInWorkspace', false)
            ->assertSee("event.data.type === 'skillspot:lesson-created'", false);
        $this->get(route('admin.courses.lessons.create', [$course, $section]).'?embed=1')
            ->assertOk()
            ->assertSee('id="frappe-lesson-editor"', false)
            ->assertSee('data-section-id="'.$section->id.'"', false)
            ->assertDontSeeText('Video Security')
            ->assertDontSee('frappe-sidebar', false);
    }

    public function test_chapter_deletion_stays_in_the_editor_and_supports_inline_deletion(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $course = Course::create(['title' => 'Delete Chapter', 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Remove me', 'order' => 1]);

        $this->actingAs($teacher)->delete(route('admin.courses.sections.destroy', [$course, $section]))
            ->assertRedirect(route('admin.courses.edit', $course).'#editor');
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);

        $section = Section::create(['course_id' => $course->id, 'title' => 'Remove inline', 'order' => 1]);
        $this->actingAs($teacher)->deleteJson(route('admin.courses.sections.destroy', [$course, $section]))
            ->assertNoContent();
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
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
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
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
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
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

    public function test_outline_changes_recalculate_existing_enrollment_progress(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Changing Course', 'primary_instructor_id' => $teacher->id,
            'enable_certification' => false,
        ]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start', 'order' => 1]);
        $first = $section->lessons()->create(['title' => 'First', 'type' => 'text', 'order' => 1]);
        $enrollment = CourseService::enrollFree($student, $course);
        CourseService::updateLessonProgress($student, $first, 100);
        $this->assertSame(100, $enrollment->fresh()->progress);
        $this->assertSame('completed', $enrollment->fresh()->status);

        $this->actingAs($teacher)->postJson(route('instructor.api.courses.lessons.store', [$course, $section]), [
            'title' => 'Second',
        ])->assertCreated();

        $second = $section->lessons()->where('title', 'Second')->firstOrFail();
        $this->assertSame(50, $enrollment->fresh()->progress);
        $this->assertSame('active', $enrollment->fresh()->status);
        $this->assertNull($enrollment->fresh()->completed_at);

        $this->delete(route('admin.courses.lessons.destroy', [$course, $section, $second]))->assertRedirect();
        $this->assertSame(100, $enrollment->fresh()->progress);
        $this->assertSame('completed', $enrollment->fresh()->status);

        $this->delete(route('admin.courses.lessons.destroy', [$course, $section, $first]))->assertRedirect();
        $this->assertSame(0, $enrollment->fresh()->progress);
        $this->assertSame('active', $enrollment->fresh()->status);
        $this->assertNull($enrollment->fresh()->completed_at);
    }

    public function test_lesson_deletion_cannot_cross_course_boundaries(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $course = Course::create(['title' => 'First Course', 'primary_instructor_id' => $admin->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'First Chapter']);
        $otherCourse = Course::create(['title' => 'Second Course', 'primary_instructor_id' => $admin->id]);
        $otherSection = Section::create(['course_id' => $otherCourse->id, 'title' => 'Second Chapter']);
        $otherLesson = $otherSection->lessons()->create(['title' => 'Keep me', 'type' => 'text']);

        $this->actingAs($admin)
            ->delete(route('admin.courses.lessons.destroy', [$course, $section, $otherLesson]))
            ->assertNotFound();

        $this->assertDatabaseHas('lessons', ['id' => $otherLesson->id]);
    }

    public function test_admin_attendance_is_scoped_to_the_course_and_its_enrolled_learners(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $course = Course::create(['title' => 'Attendance Course']);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Live']);
        $lesson = $section->lessons()->create([
            'title' => 'Live class',
            'type' => 'live',
            'live_scheduled_at' => now()->addDay(),
        ]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($admin)->get(route('admin.live'))
            ->assertOk()
            ->assertSee('for="courseSelect"', false)
            ->assertSee('for="sectionSelect"', false)
            ->assertSee('for="live-scheduled-at"', false)
            ->assertSee('aria-label="Cancel Live class"', false);
        $this->actingAs($admin)->get(route('admin.batches'))
            ->assertOk()->assertSee('for="batch-vendor"', false)->assertSee('for="batch-search"', false);
        $this->get(route('admin.batches.attendance', [$course, $lesson]))
            ->assertOk()->assertSeeText('Mark Attendance')
            ->assertSee('aria-label="Present for '.$student->name.'"', false);
        $this->actingAs($admin)->post(route('admin.batches.attendance.save', [$course, $lesson]), [
            'attendance' => [$outsider->id => 'present'],
        ])->assertUnprocessable();
        $this->post(route('admin.live.attendance.save', $lesson), [
            'attendance' => [$outsider->id => 'present'],
        ])->assertUnprocessable();
        $this->assertDatabaseMissing('batch_attendance', ['lesson_id' => $lesson->id, 'user_id' => $outsider->id]);

        $otherCourse = Course::create(['title' => 'Other Attendance Course']);
        $this->get(route('admin.batches.attendance', [$otherCourse, $lesson]))->assertNotFound();
    }
}
