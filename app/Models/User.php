<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles {
        hasRole as traitHasRole;
    }

    protected $fillable = ['name', 'email', 'phone', 'country', 'country_name', 'registered_via', 'registered_vendor_id', 'portal_access', 'password', 'email_verified_at', 'phone_verified_at'];
    protected $hidden   = ['password', 'remember_token'];

    /** Safe HasRole wrapper */
    public function hasRole($roles, string $guard = null): bool
    {
        try {
            return $this->traitHasRole($roles, $guard);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** All vendors this student has access to */
    public function vendorAccess()
    {
        return $this->belongsToMany(Vendor::class, 'student_vendor_access', 'user_id', 'vendor_id')
            ->withPivot('granted_by','note')
            ->withTimestamps();
    }

    /** Skillspot Courses assigned to this teacher */
    public function assignedCourses()
    {
        return $this->belongsToMany(\App\Models\Course::class, 'course_teachers', 'user_id', 'course_id')
            ->withPivot('added_by','created_at');
    }

    /** Check if student has access to a specific vendor */
    public function hasVendorAccess(int $vendorId): bool
    {
        return $this->vendorAccess()->where('vendor_id', $vendorId)->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
