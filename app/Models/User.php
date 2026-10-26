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

    protected $fillable = [
        'name', 'email', 'phone', 'country', 'country_name', 'registered_via',
        'portal_access', 'password', 'email_verified_at', 'phone_verified_at',
        'status', 'approved_at', 'approved_by',
    ];
    protected $hidden   = ['password', 'remember_token'];

    public function isApproved(): bool
    {
        return ($this->status ?? 'approved') === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

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

    public function instructorProfile()
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function individualMarketplaceSales()
    {
        return $this->hasMany(MarketplaceSale::class, 'seller_user_id');
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
            'approved_at'       => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
