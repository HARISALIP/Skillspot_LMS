<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Course;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\LiveClassEnrollment;
use App\Models\LiveClassSession;
use App\Models\LiveClassSessionFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_teacher_creates_a_draft_from_the_shared_dialog(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Dialog Academy', 'status' => 'active']);
        $vendor->teachers()->attach($teacher->id);

        $this->actingAs($teacher)->get(route('vendor.courses'))->assertOk()
            ->assertSee('id="newCourseModal"', false)
            ->assertSee('Course description');
        $this->post(route('vendor.courses.modal.store'), [
            'modal_create' => 1,
            'title' => 'Organization Dialog Draft',
            'short_introduction' => 'Introduction',
            'description' => '<p>Organization course details.</p>',
            'instructor_ids' => [$teacher->id],
            'vendor_id' => 9999,
            'level' => 'beginner', 'language' => 'en', 'price' => 0, 'is_free' => 1,
        ])->assertRedirect();

        $course = Course::where('title', 'Organization Dialog Draft')->firstOrFail();
        $this->assertSame($vendor->id, $course->vendor_id);
        $this->assertSame('draft', $course->marketplace_status);
        $this->assertTrue($course->teachers()->whereKey($teacher->id)->exists());
    }

    public function test_view_only_vendor_navigation_has_working_read_only_destinations(): void
    {
        Role::findOrCreate('vendor', 'web');
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $manager->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'QA Academy', 'status' => 'active']);
        $vendor->managers()->attach($manager->id, ['role' => 'manager']);
        $course = Course::create(['title' => 'Read-only course', 'vendor_id' => $vendor->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Read-only section']);
        $lesson = Lesson::create(['section_id' => $section->id, 'title' => 'Read-only attendance', 'type' => 'live']);

        $this->actingAs($manager)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee(route('vendor.courses'))
            ->assertSee(route('vendor.live_classes.index'))
            ->assertDontSee(route('vendor.live_classes.create'));

        $this->actingAs($manager)->get(route('vendor.courses'))
            ->assertOk()->assertSeeText('View Only')->assertSeeText('Read-only course')
            ->assertSeeText('All Courses')->assertSee('class="course-grid"', false)
            ->assertDontSee(route('vendor.courses.create'))
            ->assertDontSee(route('vendor.courses.edit', $course));
        $this->actingAs($manager)->get(route('vendor.courses.students', $course))
            ->assertOk()->assertSee(route('vendor.courses'))->assertSeeText('0 /');
        $this->actingAs($manager)->get(route('vendor.live_classes.index'))
            ->assertOk()->assertDontSee(route('vendor.live_classes.create'));
        $this->actingAs($manager)->get(route('vendor.live_classes.create'))
            ->assertForbidden()
            ->assertSee(route('vendor.dashboard'))
            ->assertSeeText('Go to dashboard');

        $this->actingAs($manager)->get(route('vendor.teachers'))
            ->assertOk()
            ->assertDontSee(route('vendor.teachers.remove', $owner))
            ->assertDontSee(route('vendor.teachers.add'));

        $this->actingAs($manager)->get(route('vendor.batches.show', $course))
            ->assertOk()->assertSeeText('View Only Mode')->assertSeeText('View Attendance');
        $this->actingAs($manager)->get(route('vendor.batches.attendance', [$course, $lesson]))
            ->assertOk()->assertSeeText('View only')->assertSee('action="#"', false)
            ->assertDontSee(route('vendor.batches.attendance.save', [$course, $lesson]))
            ->assertDontSeeText('Save Attendance');
    }

    public function test_teacher_navigation_only_shows_destinations_the_role_can_open(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Teacher Academy', 'status' => 'active']);
        $vendor->teachers()->attach($teacher->id);
        $course = Course::create(['title' => 'Editable course', 'vendor_id' => $vendor->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'First section', 'order' => 1]);

        $this->actingAs($teacher)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee(route('vendor.live_classes.create'))
            ->assertDontSee(route('vendor.earnings'));

        $this->actingAs($teacher)->get(route('vendor.courses'))
            ->assertOk()
            ->assertSeeText('All Courses')->assertSee('class="course-grid"', false)
            ->assertSee(route('vendor.courses.create'))
            ->assertSee(route('vendor.courses.edit', $course))
            ->assertSee(route('instructor.courses.preview', ['course' => $course, 'from' => 'vendor']));

        $this->actingAs($teacher)->get(route('vendor.earnings'))
            ->assertForbidden()
            ->assertSee(route('vendor.dashboard'));

        $this->actingAs($teacher)->get(route('vendor.courses.edit', $course))
            ->assertOk()
            ->assertSeeText('Edit Course')
            ->assertSeeText('Short Introduction')
            ->assertSeeText('Preview as learner')
            ->assertSee(route('vendor.courses.submit-review', $course))
            ->assertSeeText('Save Changes');

        $this->actingAs($teacher)->get(route('vendor.courses.lessons.create', [$course, $section]))
            ->assertOk()
            ->assertSeeText('Add Lesson')
            ->assertSeeText('Lesson Title')
            ->assertSeeText('Preview as learner');

        $this->actingAs($teacher)->post(route('vendor.courses.lessons.store', [$course, $section]), [
            'title' => 'Five minute lesson',
            'type' => 'text',
            'duration' => 5,
        ])->assertRedirect();
        $this->assertSame(300, Lesson::where('title', 'Five minute lesson')->value('duration'));

        $student = User::factory()->create();
        $outsider = User::factory()->create();
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id,
            'vendor_id' => $vendor->id, 'status' => 'active']);
        $liveLesson = Lesson::create(['section_id' => $section->id, 'title' => 'Attendance lesson', 'type' => 'live']);
        $this->actingAs($teacher)->get(route('vendor.batches.show', $course))
            ->assertOk()->assertSeeText('Manage Attendance')->assertDontSeeText('View Only Mode');
        $this->actingAs($teacher)->get(route('vendor.batches.attendance', [$course, $liveLesson]))
            ->assertOk()->assertSee(route('vendor.batches.attendance.save', [$course, $liveLesson]))
            ->assertSeeText('Save Attendance')->assertSeeText($student->name);
        $this->actingAs($teacher)->post(route('vendor.batches.attendance.save', [$course, $liveLesson]), [
            'attendance' => [$student->id => 'late'],
        ])->assertRedirect();
        $this->assertDatabaseHas('batch_attendance', ['lesson_id' => $liveLesson->id,
            'user_id' => $student->id, 'course_id' => $course->id, 'status' => 'late']);
        $this->actingAs($teacher)->post(route('vendor.batches.attendance.save', [$course, $liveLesson]), [
            'attendance' => [$outsider->id => 'present'],
        ])->assertUnprocessable();
        $this->assertDatabaseMissing('batch_attendance', ['lesson_id' => $liveLesson->id, 'user_id' => $outsider->id]);

        $otherCourse = Course::create(['title' => 'Other vendor course', 'vendor_id' => $vendor->id]);
        $otherSection = Section::create(['course_id' => $otherCourse->id, 'title' => 'Other section']);
        $otherLesson = Lesson::create(['section_id' => $otherSection->id, 'title' => 'Other lesson', 'type' => 'live']);
        $this->actingAs($teacher)->post(route('vendor.batches.attendance.save', [$course, $otherLesson]), [
            'attendance' => [$student->id => 'present'],
        ])->assertForbidden();

        $approved = Course::create(['title' => 'Approved course', 'vendor_id' => $vendor->id,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
            'is_published' => true, 'visibility' => 'public']);
        $this->actingAs($teacher)->patch(route('vendor.courses.toggle-publish', $approved))->assertRedirect();
        $this->assertFalse($approved->fresh()->marketplace_visible);
        $this->assertFalse($approved->fresh()->is_published);
        $this->actingAs($teacher)->patch(route('vendor.courses.toggle-publish', $approved))->assertRedirect();
        $this->assertTrue($approved->fresh()->marketplace_visible);
        $this->assertTrue($approved->fresh()->is_published);
    }

    public function test_teacher_live_class_actions_are_scoped_and_recover_cleanly(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Live Academy', 'status' => 'active']);
        $vendor->teachers()->attach($teacher->id);
        $student = User::factory()->create();
        $outsider = User::factory()->create();
        $vendor->studentAccess()->attach($student->id);
        $liveClass = LiveClass::create([
            'vendor_id' => $vendor->id,
            'created_by' => $teacher->id,
            'title' => 'QA & safety class',
            'slug' => 'qa-safety-class',
            'schedule_type' => 'hours_based',
            'platform' => 'zoom',
            'total_hours' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($teacher)->get(route('vendor.live_classes.show', $liveClass))
            ->assertOk()->assertSeeText('Start Session')
            ->assertSee('aria-label="Learner live class link"', false)
            ->assertSee('for="live_class_student"', false)
            ->assertDontSee('const title     = &quot;', false);
        $this->actingAs($teacher)->get(route('vendor.live_classes.create'))
            ->assertOk()->assertSee('for="title"', false)->assertSee('id="title"', false);

        $this->actingAs($teacher)->post(route('vendor.live_classes.start', $liveClass), [
            'module_name' => 'Module 1',
            'session_name' => 'Session 1',
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($teacher)->post(route('vendor.live_classes.start', $liveClass))
            ->assertRedirect()->assertSessionHas('error', 'Class is already live.');
        $this->assertDatabaseCount('live_class_sessions', 1);
        $this->actingAs($teacher)->post(route('vendor.live_classes.stop', $liveClass))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('live_class_sessions', ['live_class_id' => $liveClass->id, 'status' => 'ended']);

        $this->actingAs($teacher)->post(route('vendor.live_classes.enroll', $liveClass), ['user_id' => $outsider->id])
            ->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('live_class_enrollments', ['live_class_id' => $liveClass->id, 'user_id' => $outsider->id]);
        $this->actingAs($teacher)->post(route('vendor.live_classes.enroll', $liveClass), ['user_id' => $student->id])
            ->assertRedirect()->assertSessionHas('success');
        LiveClassEnrollment::where('live_class_id', $liveClass->id)->where('user_id', $student->id)
            ->update(['status' => 'cancelled']);
        $this->actingAs($teacher)->post(route('vendor.live_classes.enroll', $liveClass), ['user_id' => $student->id])
            ->assertRedirect();
        $this->assertDatabaseHas('live_class_enrollments', [
            'live_class_id' => $liveClass->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);
        $this->actingAs($student)->get(route('vendor.portal.live_class.detail', [$vendor->slug, $liveClass->slug]))
            ->assertOk()->assertSee('id="main-content"', false)
            ->assertSee('aria-label="Overall class progress"', false)
            ->assertSeeText('Back to dashboard');

        $otherClass = LiveClass::create([
            'vendor_id' => $vendor->id,
            'created_by' => $teacher->id,
            'title' => 'Other class',
            'slug' => 'other-class',
            'schedule_type' => 'hours_based',
            'platform' => 'zoom',
            'total_hours' => 1,
            'status' => 'active',
        ]);
        $otherSession = LiveClassSession::create([
            'live_class_id' => $otherClass->id,
            'teacher_id' => $teacher->id,
            'started_at' => now(),
            'status' => 'ended',
        ]);
        $otherFile = LiveClassSessionFile::create([
            'live_class_id' => $otherClass->id,
            'live_class_session_id' => $otherSession->id,
            'uploaded_by' => $teacher->id,
            'file_type' => 'notes',
            'title' => 'Private notes',
            'disk' => 'local',
            'path' => 'private-notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'is_visible' => true,
        ]);

        $this->actingAs($teacher)->patch(route('vendor.live_classes.file.toggle', [$liveClass, $otherFile]))
            ->assertForbidden();
        $this->actingAs($teacher)->get(route('vendor.live_classes.file.view', [$liveClass, $otherFile]))
            ->assertForbidden();
        $this->assertTrue($otherFile->fresh()->is_visible);
    }
}
