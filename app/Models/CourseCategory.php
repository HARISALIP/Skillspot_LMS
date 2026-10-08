<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class CourseCategory extends Model {
    protected $fillable = ['name','slug','icon','description','is_active','order_col'];
    protected $casts    = ['is_active' => 'boolean'];
    protected static function boot() {
        parent::boot();
        static::saving(function($m) {
            if (empty($m->slug)) $m->slug = Str::slug($m->name);
        });
    }
    public function courses() { return $this->hasMany(Course::class,'category',$this->slug); }
}
