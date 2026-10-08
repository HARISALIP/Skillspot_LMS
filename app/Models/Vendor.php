<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vendor extends Model
{
    protected $fillable = [
        'user_id','brand_name','slug','logo','banner_image','favicon',
        'primary_color','accent_color','domain','description','tagline',
        'email','phone','website','status','license_key','plan',
        'license_expires_at','settings','social_links',
    ];

    protected $casts = [
        'settings'           => 'array',
        'social_links'       => 'array',
        'license_expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($v) {
            if (empty($v->slug)) {
                $v->slug = static::uniqueSlug($v->brand_name);
            }
        });
        // When a vendor is created, auto-add owner to vendor_users pivot
        static::created(function ($v) {
            if ($v->user_id) {
                \DB::table('vendor_users')->insertOrIgnore([
                    'vendor_id'  => $v->id,
                    'user_id'    => $v->user_id,
                    'role'       => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
        // When owner changes, update pivot too
        static::updated(function ($v) {
            if ($v->wasChanged('user_id') && $v->user_id) {
                \DB::table('vendor_users')->insertOrIgnore([
                    'vendor_id'  => $v->id,
                    'user_id'    => $v->user_id,
                    'role'       => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $slug  = Str::slug($name);
        $count = static::where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    // Primary owner (legacy)
    public function user()        { return $this->belongsTo(User::class); }
    public function courses()     { return $this->hasMany(Course::class); }
    public function enrollments() { return $this->hasMany(Enrollment::class); }

    // Students with direct access to this vendor portal
    public function studentAccess()
    {
        return $this->belongsToMany(User::class, 'student_vendor_access', 'vendor_id', 'user_id')
            ->withPivot('granted_by','note')
            ->withTimestamps();
    }

    // Teachers assigned to this vendor
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'vendor_teachers', 'vendor_id', 'user_id')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    // All users who can manage this vendor
    public function managers()
    {
        return $this->belongsToMany(User::class, 'vendor_users', 'vendor_id', 'user_id')
            ->withPivot('role','added_by')
            ->withTimestamps();
    }

    public function students()
    {
        return User::whereIn('id',
            Enrollment::where('vendor_id', $this->id)->pluck('user_id')
        );
    }

    public function isActive(): bool { return $this->status === 'active'; }

    public function portalUrl(): string
    {
        if ($this->domain) return 'https://' . $this->domain;
        return url('/v/' . $this->slug);
    }
}
