<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstructorProfile;
use App\Models\Section;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_creator_can_use_the_owned_full_workspace_without_admin_controls(): void
    {
        Storage::fake('public');
        Role::findOrCreate('teacher', 'web');
        $creator = User::factory()->create();
        $creator->assignRole('teacher');
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');
        InstructorProfile::create(['user_id' => $otherTeacher->id, 'status' => 'approved']);
        $outsider = User::factory()->create();
        $outsider->assignRole('teacher');
        InstructorProfile::create(['user_id' => $outsider->id, 'status' => 'approved']);
        $vendor = Vendor::create(['user_id' => $creator->id, 'brand_name' => 'Protected Academy', 'status' => 'active']);
        $vendor->teachers()->attach([$creator->id, $otherTeacher->id]);
        $course = Course::create([
            'title' => 'Demo Course', 'slug' => 'demo-course', 'category' => 'Web Development',
            'level' => 'beginner', 'language' => 'en', 'price' => 100, 'is_free' => false,
            'primary_instructor_id' => $creator->id, 'vendor_id' => $vendor->id,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
            'visibility' => 'public', 'is_published' => true,
        ]);
        $course->teachers()->attach($creator->id, ['added_by' => $creator->id]);

        $this->actingAs($creator)->get(route('instructor.courses.show', $course))
            ->assertOk()
            ->assertSee('data-course-tab="overview"', false)
            ->assertSee('data-course-tab="dashboard"', false)
            ->assertSee('data-course-tab="editor"', false)
            ->assertSee('data-course-tab="settings"', false)
            ->assertSee('data-editor-preview', false)
            ->assertDontSee('data-tab-action="editor"', false)
            ->assertSee('action="'.route('instructor.courses.update', $course).'"', false)
            ->assertSee('action="'.route('instructor.courses.toggle-publish', $course).'"', false)
            ->assertSee('href="'.route('instructor.courses.export', $course).'"', false)
            ->assertSee('action="'.route('instructor.courses.destroy', $course).'"', false)
            ->assertDontSee('data-tab-action="dashboard"', false)
            ->assertDontSee('Publish with admin override')
            ->assertDontSee('name="vendor_id"', false)
            ->assertSee('name="instructor_ids[]"', false)
            ->assertDontSee('name="visibility"', false);

        $this->actingAs($creator)->put(route('instructor.courses.update', $course), [
            'title' => 'Creator updated course', 'category' => 'Design', 'level' => 'intermediate',
            'language' => 'en', 'price' => 125, 'is_free' => 0,
            'short_introduction' => 'A safe creator edit.',
            'description' => '<h2>Overview</h2><p>Creator content.</p>',
            'vendor_id' => null, 'instructor_ids' => [$creator->id, $otherTeacher->id], 'visibility' => 'draft',
            'thumbnail_file' => UploadedFile::fake()->image('creator-thumbnail.png'),
            'intro_video_file' => UploadedFile::fake()->create('creator-preview.mp4', 10, 'video/mp4'),
        ])->assertRedirect(route('instructor.courses.show', $course).'#settings');

        $course->refresh();
        $this->assertSame('Creator updated course', $course->title);
        $this->assertSame($vendor->id, $course->vendor_id);
        $this->assertSame($creator->id, $course->primary_instructor_id);
        $this->assertTrue($course->teachers()->whereKey($creator->id)->exists());
        $this->assertTrue($course->teachers()->whereKey($otherTeacher->id)->exists());
        $this->assertSame('public', $course->visibility);
        $this->assertTrue($course->is_published);
        $this->assertMatchesRegularExpression('#^/(?:storage/)?course-thumbnails/#', $course->thumbnail);
        $this->assertMatchesRegularExpression('#^/(?:storage/)?course-previews/#', $course->intro_video);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '/', $course->thumbnail), '/'));
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '/', $course->intro_video), '/'));

        $this->actingAs($creator)->post(route('instructor.courses.sections.store', $course), ['title' => 'Creator chapter'])
            ->assertRedirect(route('instructor.courses.show', $course).'#editor');
        $this->assertDatabaseHas('sections', ['course_id' => $course->id, 'title' => 'Creator chapter']);

        $chapter = Section::where('course_id', $course->id)->firstOrFail();
        $this->actingAs($outsider)->get(route('instructor.courses.show', $course))->assertForbidden();
        $this->actingAs($outsider)->put(route('instructor.courses.update', $course), [])->assertForbidden();
        $this->actingAs($outsider)->post(route('instructor.courses.sections.store', $course), ['title' => 'Taken'])->assertForbidden();
        $this->actingAs($outsider)->get(route('instructor.courses.lessons.create', [$course, $chapter]))->assertForbidden();
        $this->actingAs($creator)->get(route('admin.courses.edit', $course))->assertForbidden();
    }

    public function test_creator_can_only_export_publish_or_delete_their_own_course(): void
    {
        Role::findOrCreate('teacher', 'web');
        $creator = User::factory()->create();
        $creator->assignRole('teacher');
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $otherCreator = User::factory()->create();
        $otherCreator->assignRole('teacher');
        InstructorProfile::create(['user_id' => $otherCreator->id, 'status' => 'approved']);
        $course = Course::create([
            'title' => 'Creator controls', 'slug' => 'creator-controls', 'primary_instructor_id' => $creator->id,
            'marketplace_status' => 'live', 'marketplace_visible' => true, 'visibility' => 'public', 'is_published' => true,
        ]);

        $this->actingAs($otherCreator)->get(route('instructor.courses.export', $course))->assertForbidden();
        $this->actingAs($otherCreator)->patch(route('instructor.courses.toggle-publish', $course))->assertForbidden();
        $this->actingAs($otherCreator)->delete(route('instructor.courses.destroy', $course))->assertForbidden();

        $this->actingAs($creator)->get(route('instructor.courses.export', $course))
            ->assertOk()
            ->assertHeader('content-type', 'application/json');
        $this->actingAs($creator)->patch(route('instructor.courses.toggle-publish', $course))->assertRedirect();
        $this->assertFalse($course->fresh()->marketplace_visible);
        $this->actingAs($creator)->patch(route('instructor.courses.toggle-publish', $course))->assertRedirect();
        $this->assertTrue($course->fresh()->marketplace_visible);

        $draft = Course::create(['title' => 'Direct creator draft', 'slug' => 'direct-creator-draft', 'primary_instructor_id' => $creator->id]);
        $this->actingAs($creator)->patch(route('instructor.courses.toggle-publish', $draft))->assertRedirect();
        $this->assertTrue($draft->fresh()->marketplace_visible);
        $this->assertSame('live', $draft->fresh()->marketplace_status);
        $this->actingAs($creator)->delete(route('instructor.courses.destroy', $course))
            ->assertRedirect(route('instructor.courses.index'));
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }
}
