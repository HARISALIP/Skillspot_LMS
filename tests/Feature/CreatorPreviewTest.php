<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Upload;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatorPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_account_seeder_is_not_exposed_as_a_web_page(): void
    {
        $this->get('/seed-system-accounts')->assertNotFound();
        $this->assertStringContainsString('We could not load this page', view('errors.500')->render());
    }

    public function test_public_home_uses_real_counts_and_catalog_destination(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('marketplace.browse'))
            ->assertDontSee('2K+')
            ->assertDontSee('Real results from real students');
    }

    public function test_super_admin_lands_on_dashboard_even_before_setup(): void
    {
        Role::findOrCreate('super-admin', 'web');
        Setting::set('human_verify_login', '0', 'security', 'boolean');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->post('/login', ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Finish setting up your academy')
            ->assertSee(route('admin.settings', ['tab' => 'communication']));

        Setting::set('site_email', 'academy@example.test');
        $this->get(route('admin.dashboard'))->assertDontSee('Finish setting up your academy');
    }

    public function test_existing_login_destinations_for_admin_teacher_vendor_and_student(): void
    {
        foreach (['admin', 'teacher', 'vendor', 'student'] as $role) Role::findOrCreate($role, 'web');
        Setting::set('human_verify_login', '0', 'security', 'boolean');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $vendorUser = User::factory()->create();
        $vendorUser->assignRole('vendor');
        $student = User::factory()->create();
        $student->assignRole('student');
        $vendor = Vendor::create(['user_id' => $vendorUser->id, 'brand_name' => 'Login Training Center', 'status' => 'active']);
        $vendor->teachers()->attach($teacher->id);

        foreach ([
            [$admin, 'admin.dashboard'],
            [$teacher, 'vendor.dashboard'],
            [$vendorUser, 'vendor.dashboard'],
            [$student, 'student.dashboard'],
        ] as [$user, $destination]) {
            $this->post('/login', ['login' => $user->email, 'password' => 'password'])
                ->assertRedirect(route($destination));
            $this->post(route('logout'))->assertRedirect('/');
        }
    }

    public function test_creator_can_preview_private_draft_without_creating_progress(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $other = User::factory()->create();
        $other->assignRole('teacher');
        $course = Course::create(['title' => 'Private draft', 'visibility' => 'draft', 'is_published' => false, 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'First chapter']);
        $lesson = Lesson::create(['section_id' => $section->id, 'title' => 'Locked lesson', 'type' => 'text', 'content' => '<p>Private content</p>', 'is_preview' => false]);

        $this->get(route('instructor.courses.preview', $course))->assertRedirect(route('login'));
        $this->actingAs($other)->get(route('instructor.courses.preview', $course))->assertForbidden();
        $this->get(route('instructor.courses.preview.lesson', [$course, $lesson]))->assertForbidden();

        $this->actingAs($teacher)->get(route('instructor.courses.preview', $course))
            ->assertOk()->assertSee('Preview as learner')->assertSee('Locked lesson');
        $this->get(route('instructor.courses.preview.lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('content', '<p>Private content</p>');
        $this->post(route('student.save-progress', [$course, $lesson]), ['progress' => 100])->assertForbidden();
        $this->post(route('video.progress', $lesson), ['progress' => 100])->assertForbidden();
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame(0, DB::table('lesson_progress')->count());
    }

    public function test_preview_keeps_private_materials_and_draft_video_private(): void
    {
        Storage::fake('public');
        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('super-admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $other = User::factory()->create();
        $other->assignRole('teacher');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $course = Course::create(['title' => 'Draft media', 'visibility' => 'draft', 'is_published' => false, 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Files']);
        Storage::disk('public')->put('course/handout.pdf', 'private handout');
        $upload = Upload::create([
            'original_name' => 'handout.pdf', 'file_name' => 'handout.pdf', 'mime_type' => 'application/pdf',
            'path' => 'course/handout.pdf', 'disk' => 'public', 'type' => 'pdf', 'is_public' => false,
            'used_in' => ['course_id' => $course->id],
        ]);
        $lesson = Lesson::create([
            'section_id' => $section->id, 'title' => 'Private media', 'type' => 'video',
            'video_url' => 'https://example.test/private.mp4', 'is_preview' => true,
            'resources' => [['title' => 'Handout', 'kind' => 'study_material', 'upload_id' => $upload->id]],
        ]);

        $this->actingAs($other)->get(route('media.course', [$course, $upload->uuid]))->assertForbidden();
        $this->get(route('video.stream', $lesson))->assertForbidden();
        $this->actingAs($teacher)->get(route('instructor.courses.preview.lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('materials.0.url', route('media.course', [$course, $upload->uuid]));
        $this->get(route('media.course', [$course, $upload->uuid]))->assertOk();
        $this->actingAs($admin)->get(route('instructor.courses.preview', $course))->assertOk();
        $this->get(route('instructor.courses.preview.lesson', [$course, $lesson]))->assertOk();
    }

    public function test_vendor_teacher_can_preview_a_course_they_can_edit_but_outside_teacher_cannot(): void
    {
        Role::findOrCreate('teacher', 'web');
        $owner = User::factory()->create();
        $owner->assignRole('teacher');
        $colleague = User::factory()->create();
        $colleague->assignRole('teacher');
        $outsider = User::factory()->create();
        $outsider->assignRole('teacher');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Training Center', 'status' => 'active']);
        $vendor->teachers()->attach([$owner->id, $colleague->id]);
        $course = Course::create([
            'title' => 'Vendor draft', 'vendor_id' => $vendor->id,
            'primary_instructor_id' => $owner->id, 'visibility' => 'draft', 'is_published' => false,
        ]);
        $course->teachers()->attach($colleague);

        $this->actingAs($outsider)->get(route('instructor.courses.preview', $course))->assertForbidden();
        $this->actingAs($colleague)->get(route('vendor.courses.edit', $course))
            ->assertRedirect(route('instructor.courses.show', $course));
        $this->get(route('instructor.courses.preview', ['course' => $course, 'from' => 'vendor']))
            ->assertOk()->assertSee(route('vendor.courses.edit', $course));
    }
}
