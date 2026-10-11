<?php

namespace Tests\Unit;

use App\Models\Upload;
use Tests\TestCase;

class UploadPublicUrlTest extends TestCase
{
    public function test_local_public_upload_has_a_stable_storage_url(): void
    {
        $upload = new Upload([
            'disk' => 'public',
            'path' => 'uploads/course-pdfs/guide.pdf',
        ]);

        $this->assertSame(
            rtrim(config('app.url'), '/') . '/storage/uploads/course-pdfs/guide.pdf',
            $upload->public_url
        );
    }
}
