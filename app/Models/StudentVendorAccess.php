<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentVendorAccess extends Model
{
    protected $table = 'student_vendor_access';
    protected $fillable = ['user_id','vendor_id','granted_by','note'];

    public function user()   { return $this->belongsTo(User::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
}
