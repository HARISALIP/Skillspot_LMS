<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Upload;
use App\Services\MediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
}
