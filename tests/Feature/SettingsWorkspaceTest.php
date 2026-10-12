<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\Section;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_grouped_settings_workspace(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.settings'))
            ->assertOk()
            ->assertSeeText('System Configuration')
            ->assertSeeText('General')
            ->assertSeeText('Course Configuration')
            ->assertSeeText('Progress')
            ->assertSeeText('Certificates')
            ->assertSeeText('Badge Assignments')
            ->assertSeeText('User Management')
            ->assertSeeText('Payment')
            ->assertSeeText('Integrations')
            ->assertSeeText('Customization')
            ->assertSeeText('Preferences')
            ->assertSeeText('Access & Availability')
            ->assertSeeText('Signup')
            ->assertSeeText('Communication')
            ->assertSeeText('Email Accounts')
            ->assertSeeText('Services')
            ->assertSeeText('Configuration')
            ->assertSeeText('SEO');
    }

    public function test_settings_can_render_inside_the_workspace_overlay(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="settingsModalButton"', false)
            ->assertSee(route('admin.settings', ['embed' => 1]))
            ->assertSee('history.pushState', false)
            ->assertSee('skillspot:navigate-settings', false);

        $this->actingAs($admin)->get(route('admin.settings', ['embed' => 1]))
            ->assertOk()
            ->assertSeeText('System Configuration')
            ->assertDontSeeText('Configure your learning platform')
            ->assertDontSee('Main navigation')
            ->assertSee('data-settings-tab="signup"', false)
            ->assertSee('skillspot:navigate-settings', false)
            ->assertSee('data-theme="dark"', false);

        $this->actingAs($admin)->get(route('admin.settings', ['embed' => 1, 'theme' => 'light']))
            ->assertOk()
            ->assertSee('data-theme="light"', false);
    }

    public function test_management_panels_can_render_without_the_main_sidebar(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.users', ['embed' => 1]))
            ->assertOk()
            ->assertSeeText('All Users')
            ->assertSee('Add User')
            ->assertDontSee('Main navigation');
    }

    public function test_general_preferences_are_saved(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->post(route('admin.settings.save', 'general'), [
            'system_language' => 'en',
            'system_timezone' => 'UTC',
            'text_direction' => 'ltr',
            'allow_guest_access' => '1',
        ])->assertRedirect(route('admin.settings', ['tab' => 'general']));

        $this->assertDatabaseHas('settings', ['key' => 'system_timezone', 'value' => 'UTC']);
        $this->assertDatabaseHas('settings', ['key' => 'text_direction', 'value' => 'ltr']);
    }

    public function test_frappe_style_signup_and_progress_preferences_are_saved(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->post(route('admin.settings.save', 'signup'), [
            'disable_signup' => '1',
            'custom_signup_content' => '<p>Invite only</p>',
        ])->assertRedirect(route('admin.settings', ['tab' => 'signup']));

        $this->actingAs($admin)->post(route('admin.settings.save', 'course-progress'), [
            'lesson_dwell_time' => '30',
            'enforce_video_completion' => '1',
        ])->assertRedirect(route('admin.settings', ['tab' => 'course-progress']));

        $this->assertDatabaseHas('settings', ['key' => 'disable_signup', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'lesson_dwell_time', 'value' => '30']);
        $this->assertDatabaseHas('settings', ['key' => 'enforce_video_completion', 'value' => '1']);
    }

    public function test_certificate_design_is_saved_inside_the_settings_workspace(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'certificates', 'embed' => 1]))
            ->assertOk()
            ->assertSeeText('Certificate design')
            ->assertSeeText('Automatically issue certificates')
            ->assertSeeText('Jane Learner')
            ->assertDontSee(route('admin.certs.issue'), false);

        $this->actingAs($admin)->post(route('admin.settings.save', 'certificates'), [
            'certificate_auto_issue' => '1',
            'certificate_title' => 'Completion Certificate',
            'certificate_intro' => 'Awarded to',
            'certificate_completion_text' => 'completed {{course_title}}',
            'certificate_signatory' => 'Academy Director',
            'certificate_footer' => 'Verified by Skillspot',
        ])->assertRedirect(route('admin.settings', ['tab' => 'certificates']));

        $this->assertDatabaseHas('settings', ['key' => 'certificate_title', 'value' => 'Completion Certificate']);
        $this->assertDatabaseHas('settings', ['key' => 'certificate_auto_issue', 'value' => '1']);
    }

    public function test_disabling_automatic_certificates_keeps_completed_courses_certificate_free(): void
    {
        $learner = User::factory()->create();
        $course = Course::create(['title' => 'Certificate Rules', 'category' => 'Design']);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Finish']);
        $lesson = $section->lessons()->create(['title' => 'Final lesson', 'type' => 'text']);
        $enrollment = CourseService::enrollFree($learner, $course);
        Setting::set('certificate_auto_issue', '0', 'certificates', 'boolean', 'Automatically issue certificates');

        CourseService::updateLessonProgress($learner, $lesson, 100);

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'completed']);
        $this->assertDatabaseMissing('certificates', ['user_id' => $learner->id, 'course_id' => $course->id]);
    }

    public function test_guest_access_setting_protects_the_public_homepage(): void
    {
        Setting::set('allow_guest_access', '0', 'general', 'boolean', 'Allow Guest Access');

        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_categories_are_managed_inside_the_settings_workspace(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->post(route('admin.settings.categories.store'), ['name' => 'Design', 'embed' => 1])
            ->assertRedirect(route('admin.settings', ['tab' => 'categories', 'embed' => 1]));

        $category = CourseCategory::where('name', 'Design')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'categories', 'embed' => 1]))
            ->assertOk()->assertSeeText('Categories')->assertSeeText('Created')->assertSeeText('Design')->assertSeeText('New');

        $this->actingAs($admin)->put(route('admin.settings.categories.update', $category), ['name' => 'Product Design'])
            ->assertRedirect(route('admin.settings', ['tab' => 'categories']));
        $this->assertDatabaseHas('course_categories', ['id' => $category->id, 'name' => 'Product Design']);

        $this->actingAs($admin)->delete(route('admin.settings.categories.destroy', $category))
            ->assertRedirect(route('admin.settings', ['tab' => 'categories']));
        $this->assertDatabaseMissing('course_categories', ['id' => $category->id]);
    }
}
