<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassSessionFile extends Model
{
    protected $fillable = [
        'live_class_id', 'live_class_session_id', 'uploaded_by',
        'file_type', 'title', 'disk', 'path', 'mime_type', 'file_size', 'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function liveClass() { return $this->belongsTo(LiveClass::class); }
    public function session()   { return $this->belongsTo(LiveClassSession::class, 'live_class_session_id'); }
    public function uploader()  { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function isPdf(): bool
    {
        return ($this->mime_type ?? '') === 'application/pdf';
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
