<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Upload;
use App\Models\User;
use App\Services\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MediaUploaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_voice_recording_is_classified_as_audio(): void
    {
        Setting::set('storage_driver', 'local');
        Storage::fake('public');
        $path = tempnam(sys_get_temp_dir(), 'voice');
        file_put_contents($path, 'webm sample');
        try {
            $file = new class($path, 'voice-note.webm', 'audio/webm', null, true) extends UploadedFile {
                public function getMimeType(): ?string { return 'video/webm'; }
            };
            $upload = MediaUploader::upload($file, 'course-materials');
            $this->assertSame('audio', $upload->type);
            $this->assertTrue(Storage::disk('public')->exists($upload->path));
        } finally {
            unlink($path);
        }
    }

    public function test_failed_file_write_does_not_create_a_media_record(): void
    {
        Setting::set('storage_driver', 'local');
        Storage::shouldReceive('disk')->once()->with('public')->andReturn(new class {
            public function put(): bool { return false; }
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File storage failed');

        try {
            MediaUploader::upload(UploadedFile::fake()->create('guide.pdf', 1, 'application/pdf'));
        } finally {
            $this->assertSame(0, Upload::count());
        }
    }

    public function test_lesson_uploads_are_private_to_course_managers_and_enrolled_students(): void
    {
        Role::findOrCreate('teacher', 'web');
        Role::findOrCreate('student', 'web');
        Setting::set('storage_driver', 'local');
        Storage::fake('public');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $student = User::factory()->create();
        $student->assignRole('student');
        $outsider = User::factory()->create();
        $outsider->assignRole('student');
        $course = Course::create(['title' => 'Private files', 'primary_instructor_id' => $teacher->id]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $response = $this->actingAs($teacher)->postJson(route('admin.media.upload'), [
            'file' => UploadedFile::fake()->image('slide.png'),
            'course_id' => $course->id,
            'is_public' => false,
        ])->assertOk()->assertJsonPath('type', 'image');

        $upload = Upload::firstOrFail();
        $this->assertFalse($upload->is_public);
        $this->assertSame($course->id, (int) data_get($upload->used_in, 'course_id'));
        $url = $response->json('url');
        $this->actingAs($student)->get($url)->assertOk();
        $this->actingAs($outsider)->get($url)->assertForbidden();
    }
}
