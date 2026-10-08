<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchAttendance extends Model
{
    protected $table = 'batch_attendance';

    protected $fillable = [
        'lesson_id','course_id','user_id','vendor_id',
        'status','marked_by','note'
    ];

    public function lesson()   { return $this->belongsTo(Lesson::class); }
    public function course()   { return $this->belongsTo(Course::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function vendor()   { return $this->belongsTo(Vendor::class); }
    public function markedBy() { return $this->belongsTo(User::class, 'marked_by'); }
}
