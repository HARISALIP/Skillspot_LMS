<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id','title','slug','description','thumbnail','intro_video',
        'language','level','category','price','sale_price','is_free',
        'is_published','is_featured','visibility','requirements','outcomes',
        'max_students','course_type','batch_name','batch_start_date','batch_end_date','registration_open',
    ];

    protected $casts = [
        'is_free'            => 'boolean',
        'is_published'       => 'boolean',
        'is_featured'        => 'boolean',
        'registration_open'  => 'boolean',
        'visibility'         => 'string',
        'price'              => 'decimal:2',
        'sale_price'         => 'decimal:2',
        'requirements'       => 'array',
        'outcomes'           => 'array',
        'batch_start_date'   => 'date',
        'batch_end_date'     => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($c) {
            if (empty($c->slug)) $c->slug = static::uniqueSlug($c->title);
        });
        static::updating(function ($c) {
            if ($c->isDirty('title') && empty($c->slug)) $c->slug = static::uniqueSlug($c->title);
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $slug  = Str::slug($title);
        $count = static::withTrashed()->where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function vendor()       { return $this->belongsTo(Vendor::class); }
    public function sections()     { return $this->hasMany(Section::class)->orderBy('order'); }
    public function enrollments()  { return $this->hasMany(Enrollment::class); }
    public function reviews()      { return $this->hasMany(Review::class); }
    public function certificates() { return $this->hasMany(Certificate::class); }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'course_allowed_users', 'course_id', 'user_id')
            ->withPivot('added_by','note','created_at');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'course_teachers', 'course_id', 'user_id')
            ->withPivot('added_by','created_at');
    }

    /** Is course full? */
    public function isFull(): bool
    {
        if ($this->max_students === 0) return false;
        return $this->enrollments()->count() >= $this->max_students;
    }

    /** Can this user register for this vendor course? */
    public function canVendorStudentEnroll($user = null): bool
    {
        if (!$this->registration_open) return false;
        if ($this->isFull()) return false;
        if (!$user) return false;
        // For vendor courses, user must be in allowed_users list OR max_students=0
        if ($this->max_students > 0) {
            return $this->allowedUsers()->where('user_id', $user->id)->exists();
        }
        return true;
    }

    public function isVisibleTo($user = null): bool
    {
        if ($this->visibility === 'public') return true;
        if (!$user) return false;
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) return true;
        if ($this->visibility === 'restricted') {
            return $this->allowedUsers()->where('user_id', $user->id)->exists();
        }
        return false;
    }

    public function canEnroll($user = null): bool
    {
        if ($this->visibility === 'draft') return false;
        if (!$user) return $this->visibility === 'public';
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) return true;
        if ($this->visibility === 'public') return true;
        if ($this->visibility === 'restricted') {
            return $this->allowedUsers()->where('user_id', $user->id)->exists();
        }
        return false;
    }

    public function getLessonsCountAttribute(): int
    {
        return $this->sections->sum(fn($s) => $s->lessons->count());
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()->count();
    }

    public function getSlotsLeftAttribute(): ?int
    {
        if ($this->max_students === 0) return null;
        return max(0, $this->max_students - $this->enrolled_count);
    }
}
