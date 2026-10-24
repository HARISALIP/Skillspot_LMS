<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;

class LessonVideo
{
    public static function upload(Lesson $lesson): ?Upload
    {
        if (!$lesson->video_r2_path) return null;

        return Upload::where('path', $lesson->video_r2_path)->where('type', 'video')->first();
    }

    public static function url(Lesson $lesson): ?string
    {
        if ($lesson->video_storage !== 'r2' || !$lesson->video_r2_path) return $lesson->video_url;

        $upload = self::upload($lesson);
        if ($upload) {
            try {
                if (!Storage::disk($upload->disk)->exists($upload->path)) return null;
                if (in_array($upload->disk, ['local', 'public'], true)) return route('video.file', $lesson);
                if ($upload->disk === 'r2') return Storage::disk('r2')->temporaryUrl($upload->path, now()->addMinutes(5));
            } catch (\Throwable $exception) { return null; }
            return null;
        }

        try {
            if (!Storage::disk('r2')->exists($lesson->video_r2_path)) return null;
            return Storage::disk('r2')->temporaryUrl($lesson->video_r2_path, now()->addMinutes(5));
        }
        catch (\Throwable $exception) { return null; }
    }
}
