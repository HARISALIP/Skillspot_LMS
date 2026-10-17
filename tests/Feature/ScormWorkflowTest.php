<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class ScormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/scorm'));
        parent::tearDown();
    }

    public function test_teacher_can_upload_a_scorm_zip_that_creates_a_launchable_lesson(): void
    {
        [$teacher, $course] = $this->teacherCourse();

        $response = $this->actingAs($teacher)->post(route('admin.courses.sections.store', $course), [
            'title' => 'Interactive package',
            'is_scorm' => '1',
            'scorm_package' => $this->scormZip(),
        ]);

        $response->assertRedirect(route('admin.courses.edit', $course).'#editor');
        $section = $course->sections()->firstOrFail();
        $lesson = $section->lessons()->firstOrFail();
        $this->assertTrue($section->is_scorm);
        $this->assertSame('index.html', $section->scorm_launch_path);
        $this->assertSame('scorm', $lesson->type);
        $this->assertFileExists(storage_path('app/private/'.$section->scorm_package_path.'/index.html'));
    }

    public function test_scorm_zip_without_a_manifest_is_rejected_without_leaving_a_chapter(): void
    {
        [$teacher, $course] = $this->teacherCourse();

        $this->actingAs($teacher)->from(route('admin.courses.edit', $course).'#editor')
            ->post(route('admin.courses.sections.store', $course), [
                'title' => 'Broken package',
                'is_scorm' => '1',
                'scorm_package' => $this->zip(['index.html' => '<h1>Missing manifest</h1>']),
            ])->assertSessionHasErrors('scorm_package', null, 'addChapter');

        $this->assertDatabaseMissing('sections', ['course_id' => $course->id, 'title' => 'Broken package']);
    }

    public function test_enrolled_student_can_load_scorm_assets_but_another_user_cannot(): void
    {
        [$teacher, $course] = $this->teacherCourse();
        $this->actingAs($teacher)->post(route('admin.courses.sections.store', $course), [
            'title' => 'Interactive package', 'is_scorm' => '1', 'scorm_package' => $this->scormZip(),
        ]);
        $section = $course->sections()->firstOrFail();
        $lesson = $section->lessons()->firstOrFail();

        Role::findOrCreate('student', 'web');
        $student = User::factory()->create();
        $student->assignRole('student');
        CourseService::enrollFree($student, $course);
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        $this->actingAs($student)->get(route('student.get-lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('type', 'scorm')->assertJsonPath('scorm_launch_url', fn ($url) => str_contains($url, '/scorm/'));
        $this->actingAs($student)->get(route('student.scorm.asset', [$course, $section, 'index.html']))
            ->assertOk()->assertHeader('content-type', 'text/html; charset=UTF-8');
        $this->actingAs($outsider)->get(route('student.scorm.asset', [$course, $section, 'index.html']))
            ->assertForbidden();
    }

    public function test_scorm_zip_cannot_write_outside_its_private_package_directory(): void
    {
        [$teacher, $course] = $this->teacherCourse();
        $package = $this->zip([
            '../escape.txt' => 'unsafe',
            'imsmanifest.xml' => '<?xml version="1.0"?><manifest/>',
        ]);

        $this->actingAs($teacher)->post(route('admin.courses.sections.store', $course), [
            'title' => 'Unsafe package', 'is_scorm' => '1', 'scorm_package' => $package,
        ])->assertSessionHasErrors('scorm_package', null, 'addChapter');

        $this->assertDatabaseMissing('sections', ['course_id' => $course->id]);
        $this->assertFileDoesNotExist(storage_path('app/private/scorm/escape.txt'));
    }

    public function test_scorm_runtime_data_and_completion_are_persisted(): void
    {
        [$teacher, $course] = $this->teacherCourse();
        $this->actingAs($teacher)->post(route('admin.courses.sections.store', $course), [
            'title' => 'Interactive package', 'is_scorm' => '1', 'scorm_package' => $this->scormZip(),
        ]);
        $lesson = $course->sections()->firstOrFail()->lessons()->firstOrFail();
        Role::findOrCreate('student', 'web');
        $student = User::factory()->create();
        $student->assignRole('student');
        CourseService::enrollFree($student, $course);

        $state = json_encode(['cmi.core.lesson_status' => 'completed', 'cmi.suspend_data' => 'page=4']);
        $this->actingAs($student)->postJson(route('student.save-progress', [$course, $lesson]), [
            'progress' => 100, 'watched_seconds' => 0, 'scorm_data' => $state,
        ])->assertOk()->assertJsonPath('lesson_done', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id, 'lesson_id' => $lesson->id,
            'completed' => 1, 'scorm_data' => $state,
        ]);
    }

    private function teacherCourse(): array
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
        return [$teacher, $course];
    }

    private function scormZip(): UploadedFile
    {
        return $this->zip([
            'imsmanifest.xml' => '<?xml version="1.0"?><manifest xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2"><resources><resource identifier="sco" type="webcontent" adlcp:scormtype="sco" href="index.html"><file href="index.html"/></resource></resources></manifest>',
            'index.html' => '<!doctype html><html><body>SCORM works<script>window.parent.API && window.parent.API.LMSInitialize("");</script></body></html>',
        ]);
    }

    private function zip(array $files): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'skillspot-scorm-').'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) $zip->addFromString($name, $contents);
        $zip->close();
        return new UploadedFile($path, 'course-package.zip', 'application/zip', null, true);
    }
}
