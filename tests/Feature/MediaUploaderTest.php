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
