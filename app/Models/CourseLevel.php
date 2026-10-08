<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class CourseLevel extends Model {
    protected $fillable = ['name','slug','description','is_active','order_col'];
    protected $casts    = ['is_active' => 'boolean'];
    protected static function boot() {
        parent::boot();
        static::saving(function($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->name);
        });
    }
}
