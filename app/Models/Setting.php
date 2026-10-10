<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'label'];

    // ── Get a setting value with optional default ──────────────────────
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
                $row = static::where('key', $key)->first();
                return $row ? $row->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    // ── Set / upsert a setting and bust cache ──────────────────────────
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'text', string $label = ''): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type, 'label' => $label]
        );
        Cache::forget("setting:{$key}");
    }

    // ── Get all settings as key=>value map ─────────────────────────────
    public static function allKeyed(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
