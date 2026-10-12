<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionOverride extends Model
{
    protected $fillable = ['instructor_id', 'course_id', 'instructor_percent', 'set_by'];
    protected $casts = ['instructor_percent' => 'decimal:2'];
}
