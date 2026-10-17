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
            'is_public' => true,
        ]);

        $this->assertSame(
            route('media.public', $upload->uuid),
            $upload->public_url
        );
    }

    public function test_private_upload_does_not_expose_a_public_url(): void
    {
        $upload = new Upload(['disk' => 'local', 'path' => 'assignment-submissions/private.pdf', 'is_public' => false]);
        $this->assertSame('', $upload->public_url);
    }
}
