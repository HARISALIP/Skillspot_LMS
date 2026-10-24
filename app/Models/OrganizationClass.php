<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationClass extends Model
{
    protected $fillable = ['vendor_id', 'name'];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function students() { return $this->belongsToMany(User::class, 'organization_class_students'); }
    public function courses() { return $this->belongsToMany(Course::class, 'organization_class_courses'); }
}
