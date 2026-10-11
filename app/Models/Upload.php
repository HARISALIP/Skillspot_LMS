<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Upload extends Model
{
    protected $fillable = [
        'uuid','original_name','file_name','mime_type','extension',
        'size','disk','path','url','type','folder','is_public',
        'uploaded_by','used_in',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'used_in'   => 'array',
        'size'      => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->uuid)) $m->uuid = (string) Str::uuid();
        });
    }

    public function uploader() { return $this->belongsTo(User::class,'uploaded_by'); }

    // ── Human-readable file size ───────────────────────────────────────
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    // ── Type icon ─────────────────────────────────────────────────────
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'image'    => '🖼️',
            'video'    => '🎬',
            'pdf'      => '📄',
            'document' => '📝',
            default    => '📎',
        };
    }

    // ── Full accessible URL ────────────────────────────────────────────
    public function getPublicUrlAttribute(): string
    {
        if ($this->disk === 'public') {
            return route('media.public', $this->uuid);
        }
        if (!empty($this->url)) return $this->url;
        // If R2 public URL is configured, use that
        $publicBase = \App\Models\Setting::get('r2_public_url','');
        if ($publicBase) return rtrim($publicBase,'/') . '/' . $this->path;
        // Fall back to signed temporary URL (60 min)
        try {
            return \Illuminate\Support\Facades\Storage::disk($this->disk)
                ->temporaryUrl($this->path, now()->addMinutes(60));
        } catch (\Exception $e) {
            return '';
        }
    }

    // ── Determine type from mime ───────────────────────────────────────
    public static function typeFromMime(string $mime): string
    {
        if (str_starts_with($mime, 'image/'))                  return 'image';
        if (str_starts_with($mime, 'video/'))                  return 'video';
        if ($mime === 'application/pdf')                        return 'pdf';
        if (in_array($mime, ['application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain','application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']))
            return 'document';
        return 'other';
    }
}
