<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstructorProfile;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatorStudioAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_teacher_keeps_own_editor_without_bypassing_new_publication_approval(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create(['title' => 'Legacy class', 'primary_instructor_id' => $teacher->id]);

        $this->actingAs($teacher)->get(route('instructor.courses.show', $course))->assertOk();
        $this->actingAs($teacher)->post(route('studio.courses.submit-review', $course))->assertForbidden();
        InstructorProfile::create(['user_id' => $teacher->id, 'status' => 'suspended']);
        $this->actingAs($teacher)->get(route('instructor.courses.show', $course))->assertForbidden();
        $this->actingAs($teacher)->get(route('instructor.courses.preview', $course))->assertForbidden();
    }

    public function test_approved_creator_price_edit_goes_live_and_is_audited(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $course = Course::create(['title' => 'Live owned course', 'primary_instructor_id' => $creator->id,
            'marketplace_status' => 'live', 'marketplace_visible' => true, 'is_published' => true, 'visibility' => 'public', 'price' => 100]);

        $this->actingAs($creator)->patchJson(route('instructor.api.courses.update', $course), ['price' => 120])
            ->assertOk();
        $this->assertSame('live', $course->fresh()->marketplace_status);
        $this->assertDatabaseHas('course_change_audits', ['course_id' => $course->id, 'actor_id' => $creator->id, 'subject_type' => 'course']);
    }

    public function test_pending_applicant_can_make_private_draft_but_cannot_submit_it(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'pending']);

        $response = $this->actingAs($creator)->postJson(route('instructor.api.courses.store'), ['title' => 'Private draft']);
        $response->assertCreated();
        $course = Course::findOrFail($response->json('id'));
        $this->assertSame($creator->id, $course->primary_instructor_id);
        $this->assertFalse($course->is_published);
        $this->assertSame('draft', $course->visibility);
        $this->actingAs($creator)->post(route('studio.courses.submit-review', $course))->assertForbidden();
    }

    public function test_another_creator_cannot_edit_or_preview_someone_elses_course(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        InstructorProfile::create(['user_id' => $other->id, 'status' => 'approved']);
        $course = Course::create(['title' => 'Private draft', 'primary_instructor_id' => $owner->id]);

        $this->actingAs($other)->get(route('instructor.api.courses.show', $course))->assertForbidden();
        $this->actingAs($other)->patchJson(route('instructor.api.courses.update', $course), ['title' => 'Taken'])->assertForbidden();
        $this->actingAs($other)->get(route('instructor.courses.preview', $course))->assertForbidden();
        $this->actingAs($other)->get(route('admin.dashboard'))->assertForbidden();
        $this->assertSame('Private draft', $course->fresh()->title);
    }

    public function test_creator_upload_is_private_and_scoped_to_owned_course(): void
    {
        Setting::set('storage_driver', 'local', 'storage');
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        InstructorProfile::create(['user_id' => $owner->id, 'status' => 'pending']);
        InstructorProfile::create(['user_id' => $other->id, 'status' => 'approved']);
        $course = Course::create(['title' => 'Private file class', 'primary_instructor_id' => $owner->id]);

        $this->actingAs($other)->postJson(route('courses.teaching.media.store', $course),
            ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])->assertForbidden();
        $response = $this->actingAs($owner)->postJson(route('courses.teaching.media.store', $course),
            ['file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])->assertCreated();
        $response->assertJsonPath('success', true)->assertJsonStructure(['path', 'url', 'uuid']);
        $upload = \App\Models\Upload::findOrFail($response->json('id'));
        $this->assertSame('local', $upload->disk);
        $this->assertFalse($upload->is_public);
        $this->assertSame('', $upload->public_url);
        $this->actingAs($other)->get(route('media.course', [$course, $upload->uuid]))->assertForbidden();
        $this->actingAs($owner)->get(route('media.course', [$course, $upload->uuid]))->assertOk();
        $this->actingAs($other)->getJson(route('courses.teaching.media.index', $course))->assertForbidden();
        $this->actingAs($owner)->getJson(route('courses.teaching.media.index', $course))
            ->assertOk()->assertJsonPath('data.0.id', $upload->id);
    }

    public function test_new_studio_chapter_has_an_empty_lesson_list_for_immediate_authoring(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'pending']);
        $course = Course::create(['title' => 'New class', 'primary_instructor_id' => $creator->id]);

        $this->actingAs($creator)->postJson(route('instructor.api.courses.sections.store', $course),
            ['title' => 'Chapter one'])->assertCreated()->assertJsonPath('lessons', []);
    }

    public function test_studio_duration_uses_minutes_while_storage_remains_seconds(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $course = Course::create(['title' => 'Timed class', 'primary_instructor_id' => $creator->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter one', 'order' => 1]);
        $lesson = Lesson::create(['section_id' => $section->id, 'title' => 'Timed lesson', 'type' => 'text', 'duration' => 600]);

        $this->actingAs($creator)->getJson(route('instructor.api.courses.show', $course))
            ->assertOk()->assertJsonPath('sections.0.lessons.0.duration', 10);
        $this->actingAs($creator)->patchJson(route('instructor.api.courses.lessons.update', [$course, $section, $lesson]), ['duration' => 12])
            ->assertOk()->assertJsonPath('duration', 12);
        $this->assertSame(720, $lesson->fresh()->duration);
    }

    public function test_changes_to_an_approved_but_hidden_course_are_still_audited(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $course = Course::create([
            'title' => 'Approved hidden class',
            'primary_instructor_id' => $creator->id,
            'marketplace_status' => 'live',
            'marketplace_visible' => false,
            'is_published' => false,
            'visibility' => 'draft',
        ]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter one', 'order' => 1]);
        $lesson = Lesson::create(['section_id' => $section->id, 'title' => 'Original title', 'type' => 'text']);

        $this->actingAs($creator)->patchJson(route('instructor.api.courses.lessons.update', [$course, $section, $lesson]), [
            'title' => 'Updated title',
        ])->assertOk();

        $this->assertDatabaseHas('course_change_audits', [
            'course_id' => $course->id,
            'actor_id' => $creator->id,
            'subject_type' => 'lesson',
            'subject_id' => $lesson->id,
        ]);
    }

    public function test_incomplete_course_submission_explains_what_the_creator_must_fix(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $course = Course::create(['title' => 'Incomplete class', 'primary_instructor_id' => $creator->id]);

        $this->actingAs($creator)->from(route('studio.dashboard'))->followingRedirects()
            ->post(route('studio.courses.submit-review', $course))
            ->assertOk()
            ->assertSee('This course is not ready to submit.')
            ->assertSee('Add an introduction and description.')
            ->assertSee('Add at least one lesson.');
    }
}
