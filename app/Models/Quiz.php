<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quiz extends Model {
    protected $fillable = ['lesson_id','title','pass_score','time_limit'];
    public function lesson()    { return $this->belongsTo(Lesson::class); }
    public function questions() { return $this->hasMany(QuizQuestion::class); }
}
