<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CourseLanguage extends Model {
    protected $fillable = ['name','code','is_active','order_col'];
    protected $casts    = ['is_active' => 'boolean'];
}
