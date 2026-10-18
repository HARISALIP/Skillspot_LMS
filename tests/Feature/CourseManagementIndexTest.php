<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseManagementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_use_the_course_workspace_and_students_cannot(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Robotics Fundamentals',
            'slug' => 'robotics-fundamentals',
            'category' => 'Technology',
            'visibility' => 'public',
            'is_published' => true,
            'is_featured' => true,
            'enable_certification' => true,
            'thumbnail_color' => '#123456',
        ]);
        Section::create(['course_id' => $course->id, 'title' => 'Start here']);

        $this->actingAs($teacher)->get(route('admin.courses'))
            ->assertOk()
            ->assertSeeText('Robotics Fundamentals')
            ->assertSeeText('Published')
            ->assertSeeText('All Courses')
            ->assertSeeText('Created')
            ->assertSeeText('Unpublished')
            ->assertSeeText('New Course')
            ->assertSeeText('Course description')
            ->assertSee('title="Heading 2"', false)
            ->assertSee('title="Image / Gallery"', false)
            ->assertSee('id="courseEditorImage"', false)
            ->assertSee('id="courseEditorVideo"', false)
            ->assertSee('id="courseEditorUrlPopover"', false)
            ->assertSee('id="courseEditorColorPopover"', false)
            ->assertSee('id="courseEditorTableGrid"', false)
            ->assertSee('id="courseEditorEmbedModal"', false)
            ->assertSeeText('Insert Embed')
            ->assertSee('course-creation-editor-', false)
            ->assertDontSee('window.prompt', false)
            ->assertSeeText('Import via ZIP')
            ->assertSeeText('Learning')
            ->assertSeeText('Administrator')
            ->assertSeeText('Search')
            ->assertSeeText('Notifications')
            ->assertSeeText('Batches')
            ->assertSeeText('Certifications')
            ->assertDontSeeText('Getting started')
            ->assertSeeText('Collapse')
            ->assertSeeText('Light')
            ->assertSeeText('Dark')
            ->assertSeeText('System')
            ->assertSee('style="--tw-gradient-to: #123456"', false)
            ->assertSee('aria-label="Rating 0"', false)
            ->assertSee('aria-label="Featured"', false)
            ->assertSee('aria-label="Get Certified"', false)
            ->assertDontSee('aria-label="0 lessons"', false)
            ->assertDontSee('aria-label="0 learners"', false)
            ->assertSee(route('admin.courses.edit', $course));

        $this->actingAs($student)->get(route('admin.courses'))->assertForbidden();

        $this->actingAs($teacher)->get(route('admin.courses.create'))
            ->assertRedirect(route('admin.courses', ['new' => 1]));

        $this->actingAs($teacher)->get(route('admin.courses', ['new' => 1]))
            ->assertOk()
            ->assertSee('id="newCourseModal" class="fixed inset-0 z-50  overflow-y-auto', false);
    }

    public function test_course_filters_use_real_upcoming_and_certification_fields(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');

        Course::create(['title' => 'Certified upcoming', 'slug' => 'certified-upcoming', 'category' => 'Technology', 'visibility' => 'public', 'is_published' => true, 'upcoming' => true, 'enable_certification' => true]);
        Course::create(['title' => 'Ordinary course', 'slug' => 'ordinary-course', 'category' => 'Technology', 'visibility' => 'public', 'is_published' => true, 'upcoming' => false, 'enable_certification' => false]);

        $this->actingAs($teacher)->get(route('admin.courses', ['tab' => 'upcoming', 'certification' => 1]))
            ->assertOk()
            ->assertSeeText('Certified upcoming')
            ->assertDontSeeText('Ordinary course');
    }

    public function test_created_filter_and_page_size_match_the_course_list_controls(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $mine = Course::create(['title' => 'My draft', 'category' => 'Technology', 'primary_instructor_id' => $teacher->id, 'is_published' => false]);
        Course::create(['title' => 'Someone else draft', 'category' => 'Technology', 'is_published' => false]);

        $this->actingAs($teacher)->get(route('admin.courses', ['tab' => 'created', 'limit' => 60]))
            ->assertOk()
            ->assertSeeText($mine->title)
            ->assertDontSeeText('Someone else draft')
            ->assertSee('aria-current="true"', false);
    }

    public function test_course_modal_allows_no_category_like_frappe(): void
    {
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');

        $this->actingAs($teacher)->post(route('admin.courses.store'), [
            'modal_create' => 1,
            'title' => 'Uncategorized course',
            'short_introduction' => 'A short introduction',
            'description' => '<p>Course details</p>',
            'instructor_ids' => [$teacher->id],
            'level' => 'beginner',
            'language' => 'en',
            'price' => 0,
            'is_free' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('courses', ['title' => 'Uncategorized course', 'category' => null]);
    }

    public function test_modal_validation_preserves_input_without_creating_a_category(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.courses.store'), [
            'modal_create' => 1,
            'title' => 'Keep this title',
            'short_introduction' => 'Keep this introduction',
            'description' => '<p>Keep this description</p>',
            'new_category' => 'Do not create',
            'instructor_ids' => [$student->id],
            'level' => 'beginner', 'language' => 'en', 'price' => 0, 'is_free' => 1,
        ])->assertSessionHasErrors('instructor_ids');

        $this->assertDatabaseMissing('courses', ['title' => 'Keep this title']);
        $this->assertDatabaseMissing('course_categories', ['name' => 'Do not create']);
        $this->get(route('admin.courses'))->assertOk()
            ->assertSee('id="newCourseModal" class="fixed inset-0 z-50  overflow-y-auto', false)
            ->assertSee('Keep this title')
            ->assertSee('Keep this introduction');
    }

    public function test_course_modal_category_and_media_actions_require_creator_access(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->create();
        Storage::fake('public');
        Setting::set('storage_driver', 'public');

        $this->actingAs($student)->postJson(route('courses.create.categories'), ['name' => 'Blocked'])->assertForbidden();
        $this->actingAs($student)->postJson(route('courses.create.media'), ['file' => UploadedFile::fake()->image('blocked.png')])->assertForbidden();

        $this->actingAs($admin)->postJson(route('courses.create.categories'), ['name' => 'New design'])
            ->assertCreated()->assertJsonPath('name', 'New design');
        $this->assertSame(1, CourseCategory::where('name', 'New design')->count());
        $this->postJson(route('courses.create.media'), ['file' => UploadedFile::fake()->image('cover.png')])
            ->assertCreated()->assertJsonStructure(['url']);
        $this->assertDatabaseHas('uploads', ['original_name' => 'cover.png', 'disk' => 'public']);
        $this->postJson(route('courses.create.media'), ['file' => UploadedFile::fake()->create('clip.mp4', 1, 'video/mp4')])
            ->assertCreated()->assertJsonStructure(['url']);
        $this->assertDatabaseHas('uploads', ['original_name' => 'clip.mp4', 'mime_type' => 'video/mp4', 'disk' => 'public']);

        Setting::set('storage_driver', 'r2');
        config(['filesystems.disks.r2.key' => '', 'filesystems.disks.r2.secret' => '']);
        $this->postJson(route('courses.create.media'), ['file' => UploadedFile::fake()->image('missing-storage.png')])
            ->assertUnprocessable()->assertJsonValidationErrors('file')
            ->assertDontSee('R2 storage is not configured');
    }

    public function test_admin_can_import_a_frappe_course_zip_as_a_draft(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');

        $path = tempnam(sys_get_temp_dir(), 'course-import-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('course.json', json_encode([
            'title' => 'Imported Frappe Course',
            'description' => 'Imported description',
            'category' => 'Technology',
        ]));
        $zip->close();

        $upload = UploadedFile::fake()->createWithContent('course.zip', file_get_contents($path));
        unlink($path);

        $this->actingAs($teacher)->post(route('admin.courses.import.store'), ['archive' => $upload])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'title' => 'Imported Frappe Course',
            'visibility' => 'draft',
            'is_published' => false,
        ]);
    }

    public function test_admin_can_create_a_course_from_the_modal_fields(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');

        $this->actingAs($teacher)->post(route('admin.courses.store'), [
            'modal_create' => 1,
            'title' => 'Modal Course',
            'short_introduction' => 'A short introduction',
            'description' => '<h2>Heading two</h2><h3>Heading three</h3><h4>Heading four</h4><p>Course details</p><p style="text-align: center;">Centered</p><ul><li><p>Bullet</p></li></ul><ol><li><p>Numbered</p></li></ol><blockquote><p>Quoted</p></blockquote><hr><font color="#ff0000">Red</font><span style="background-color: rgb(254, 202, 202);">Highlighted</span><mark data-color="#fecaca" style="background-color: rgb(254, 202, 202); color: inherit;">Marked</mark><code>inline()</code><strike>Removed text</strike><table><thead><tr><th>Heading</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table><video src="https://cdn.example.test/video.mp4" controls></video><iframe src="https://www.youtube.com/embed/example"></iframe><iframe src="https://evil.example.test/embed"></iframe><script>alert(1)</script>',
            'new_category' => 'Framework',
            'level' => 'beginner',
            'language' => 'en',
            'price' => 0,
            'is_free' => 1,
            'visibility' => 'draft',
            'instructor_ids' => [$teacher->id],
        ])->assertRedirect();

        $course = Course::where('title', 'Modal Course')->firstOrFail();
        $this->assertSame('Framework', $course->category);
        $this->assertSame('A short introduction', $course->short_introduction);
        $this->assertStringContainsString('<p>Course details</p>', $course->description);
        $this->assertStringContainsString('<h2>Heading two</h2><h3>Heading three</h3><h4>Heading four</h4>', $course->description);
        $this->assertStringContainsString('<ul><li><p>Bullet</p></li></ul><ol><li><p>Numbered</p></li></ol>', $course->description);
        $this->assertStringContainsString('<blockquote><p>Quoted</p></blockquote><hr>', $course->description);
        $this->assertStringContainsString('<video', $course->description);
        $this->assertStringContainsString('<iframe', $course->description);
        $this->assertStringContainsString('style="text-align: center;"', $course->description);
        $this->assertStringContainsString('<font color="#ff0000">Red</font>', $course->description);
        $this->assertStringContainsString('background-color: rgb(254, 202, 202)', $course->description);
        $this->assertStringContainsString('<mark style="background-color: rgb(254, 202, 202); color: inherit;">Marked</mark>', $course->description);
        $this->assertStringContainsString('<code>inline()</code>', $course->description);
        $this->assertStringContainsString('<thead><tr><th>Heading</th></tr></thead>', $course->description);
        $this->assertStringContainsString('<strike>Removed text</strike>', $course->description);
        $this->assertStringNotContainsString('evil.example.test', $course->description);
        $this->assertStringNotContainsString('<script>', $course->description);
        $this->assertTrue($course->teachers()->whereKey($teacher)->exists());
    }

    public function test_modal_accepts_a_safe_media_only_description(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.courses.store'), [
            'modal_create' => 1,
            'title' => 'Visual Course',
            'short_introduction' => 'Learn through visuals',
            'description' => '<p></p><img src="https://cdn.example.test/course.png"><script>alert(1)</script>',
            'level' => 'beginner',
            'language' => 'en',
            'price' => 0,
            'is_free' => 1,
            'visibility' => 'draft',
            'instructor_ids' => [$admin->id],
        ])->assertRedirect();

        $description = Course::where('title', 'Visual Course')->firstOrFail()->description;
        $this->assertStringContainsString('<img src="https://cdn.example.test/course.png">', $description);
        $this->assertStringNotContainsString('<script>', $description);
    }

    public function test_super_admin_can_invite_an_instructor_from_the_course_modal(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'no-reply@example.test']);
        Setting::set('mail_invites_verified_at', now()->toIso8601String());
        Notification::fake();
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create(['country' => 'IN', 'country_name' => 'India']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.courses'))
            ->assertOk()
            ->assertSeeText('Create New Instructor');

        $this->actingAs($admin)->postJson(route('admin.courses.instructors.store'), [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.test',
            'roles' => ['teacher'],
        ])->assertCreated()->assertJsonPath('instructor.email', 'jane@example.test');

        $instructor = User::where('email', 'jane@example.test')->firstOrFail();
        $this->assertTrue($instructor->hasRole('teacher'));
        $this->assertTrue($instructor->hasRole('student'));
        $this->assertSame('IN', $instructor->country);
        Notification::assertSentTo($instructor, ResetPassword::class);
    }

    public function test_member_invitations_stay_disabled_until_smtp_is_verified(): void
    {
        config(['mail.default' => 'log']);
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->postJson(route('admin.courses.instructors.store'), [
            'email' => 'blocked@example.test', 'roles' => ['teacher'],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.test']);
    }

    public function test_teacher_cannot_create_instructors_from_the_course_modal(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->postJson(route('admin.courses.instructors.store'), [
            'first_name' => 'Blocked', 'email' => 'blocked@example.test', 'roles' => ['teacher'],
        ])->assertForbidden();
    }
}
