<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->assertSeeText('Import via ZIP')
            ->assertSee(route('admin.courses.edit', $course));

        $this->actingAs($student)->get(route('admin.courses'))->assertForbidden();
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
}
