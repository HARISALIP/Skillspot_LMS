<?php

namespace Tests\Feature;

use App\Models\Upload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_local_file_is_served_and_private_file_is_hidden(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Storage::fake('public');
        Storage::disk('public')->put('uploads/course-pdfs/guide.pdf', '%PDF-1.4 demo');

        $upload = Upload::create([
            'original_name' => 'guide.pdf',
            'file_name' => 'guide.pdf',
            'path' => 'uploads/course-pdfs/guide.pdf',
            'disk' => 'public',
            'is_public' => true,
        ]);

        $this->get($upload->public_url)->assertOk()->assertStreamedContent('%PDF-1.4 demo');

        $upload->update(['is_public' => false]);
        $this->get(route('media.public', $upload->uuid))->assertNotFound();
    }
}
