<?php
namespace App\Services;

use App\Models\Upload;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class MediaUploader
{
    // ── Allowed types and max sizes ────────────────────────────────────
    const ALLOWED = [
        'image'    => ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'],
        'video'    => ['video/mp4','video/webm','video/ogg','video/quicktime','video/x-msvideo'],
        'pdf'      => ['application/pdf'],
        'document' => ['application/msword',
                       'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                       'application/vnd.ms-excel',
                       'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                       'text/plain'],
    ];

    // ── Upload a file to R2 ────────────────────────────────────────────
    public static function upload(
        UploadedFile $file,
        string $folder = 'general',
        bool   $isPublic = true
    ): Upload {
        $maxMb   = (int) Setting::get('max_upload_mb', 500);
        $maxBytes= $maxMb * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new \Exception("File too large. Max allowed: {$maxMb} MB");
        }

        $mime      = $file->getMimeType();
        $type      = Upload::typeFromMime($mime);
        $ext       = strtolower($file->getClientOriginalExtension());
        $uuid      = (string) Str::uuid();
        $safeName  = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName  = $uuid . '_' . $safeName . '.' . $ext;
        $path      = "uploads/{$folder}/{$fileName}";

        $disk = Setting::get('storage_driver','r2') === 'r2' ? 'r2' : 'public';

        // Upload to R2 (or local if not configured)
        $options = $isPublic ? ['visibility' => 'public'] : [];
        if (!Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()), $options)) {
            throw new \RuntimeException('File storage failed. Please try again.');
        }

        // Determine URL
        $publicBase = Setting::get('r2_public_url','');
        $url = $disk === 'public' ? ''
            : ($publicBase ? rtrim($publicBase,'/') . '/' . $path : '');

        return Upload::create([
            'uuid'          => $uuid,
            'original_name' => $file->getClientOriginalName(),
            'file_name'     => $fileName,
            'mime_type'     => $mime,
            'extension'     => $ext,
            'size'          => $file->getSize(),
            'disk'          => $disk,
            'path'          => $path,
            'url'           => $url,
            'type'          => $type,
            'folder'        => $folder,
            'is_public'     => $isPublic,
            'uploaded_by'   => Auth::id(),
        ]);
    }

    // ── Delete a file ──────────────────────────────────────────────────
    public static function delete(Upload $upload): void
    {
        try {
            Storage::disk($upload->disk)->delete($upload->path);
        } catch (\Exception $e) {
            // Silent — still remove DB record
        }
        $upload->delete();
    }

    // ── Get temporary signed URL (private files) ───────────────────────
    public static function temporaryUrl(Upload $upload, int $minutes = 60): string
    {
        try {
            return Storage::disk($upload->disk)->temporaryUrl($upload->path, now()->addMinutes($minutes));
        } catch (\Exception $e) {
            return $upload->url ?? '';
        }
    }

    // ── Get all allowed mime types flat ───────────────────────────────
    public static function allowedMimes(): array
    {
        return array_merge(...array_values(self::ALLOWED));
    }

    // ── Format allowed for validation ─────────────────────────────────
    public static function allowedExtensions(): string
    {
        return 'jpg,jpeg,png,gif,webp,svg,mp4,webm,ogg,mov,avi,pdf,doc,docx,xls,xlsx,txt';
    }
}
