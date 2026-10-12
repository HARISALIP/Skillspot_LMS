<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
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
            ->assertSeeText('All Courses')
            ->assertSeeText('Unpublished')
            ->assertSeeText('New Course')
            ->assertSeeText('Course description')
            ->assertSee('title="Heading 2"', false)
            ->assertSee('title="Image / Gallery"', false)
            ->assertSee('id="courseEditorImage"', false)
            ->assertSee('id="courseEditorVideo"', false)
            ->assertSeeText('Import via ZIP')
            ->assertSeeText('Learning')
            ->assertSeeText('Administrator')
            ->assertSeeText('Search')
            ->assertSeeText('Notifications')
            ->assertSeeText('Batches')
            ->assertSeeText('Certifications')
            ->assertSeeText('Getting started')
            ->assertSeeText('Collapse')
            ->assertSeeText('Light')
            ->assertSeeText('Dark')
            ->assertSeeText('System')
            ->assertSee(route('admin.courses.edit', $course));

        $this->actingAs($student)->get(route('admin.courses'))->assertForbidden();

        $this->actingAs($teacher)->get(route('admin.courses.create'))
            ->assertRedirect(route('admin.courses', ['new' => 1]));

        $this->actingAs($teacher)->get(route('admin.courses', ['new' => 1]))
            ->assertOk()
            ->assertSee('id="newCourseModal" class="fixed inset-0 z-50  overflow-y-auto', false);
    }

    public function test_teacher_can_import_a_frappe_course_zip_as_a_draft(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

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

    public function test_teacher_can_create_a_course_from_the_modal_fields(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('admin.courses.store'), [
            'modal_create' => 1,
            'title' => 'Modal Course',
            'short_introduction' => 'A short introduction',
            'description' => '<p>Course details</p><video src="https://cdn.example.test/video.mp4" controls></video><iframe src="https://www.youtube.com/embed/example"></iframe><script>alert(1)</script>',
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
        $this->assertStringContainsString('<video', $course->description);
        $this->assertStringContainsString('<iframe', $course->description);
        $this->assertStringNotContainsString('<script>', $course->description);
        $this->assertTrue($course->teachers()->whereKey($teacher)->exists());
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
