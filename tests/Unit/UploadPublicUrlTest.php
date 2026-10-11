<?php

namespace Tests\Unit;

use App\Models\Upload;
use Tests\TestCase;

class UploadPublicUrlTest extends TestCase
{
    public function test_local_public_upload_has_a_stable_storage_url(): void
    {
        $upload = new Upload([
            'uuid' => '9b45c346-ea28-4b8c-af37-5b06b27d33d8',
            'disk' => 'public',
            'path' => 'uploads/course-pdfs/guide.pdf',
        ]);

        $this->assertSame(
            route('media.public', $upload->uuid),
            $upload->public_url
        );
    }
}
