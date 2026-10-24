<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id','primary_instructor_id','title','slug','description','short_introduction','meta_description','meta_keywords',
        'thumbnail','thumbnail_color','intro_video','language','level','category','tags','price','sale_price','is_free',
        'is_published','is_featured','upcoming','enforce_lesson_completion','enable_certification','visibility','requirements','outcomes','resources','related_course_ids',
        'max_students','course_type','batch_name','batch_start_date','batch_end_date','registration_open',
        'marketplace_status','marketplace_visible','submitted_at','reviewed_at','reviewed_by','review_feedback',
        'organization_audience','organization_published',
    ];

    protected $casts = [
        'is_free'            => 'boolean',
        'is_published'       => 'boolean',
        'is_featured'        => 'boolean',
        'upcoming'           => 'boolean',
        'enforce_lesson_completion' => 'boolean',
        'enable_certification' => 'boolean',
        'registration_open'  => 'boolean',
        'visibility'         => 'string',
        'price'              => 'decimal:2',
        'sale_price'         => 'decimal:2',
        'requirements'       => 'array',
        'outcomes'           => 'array',
        'resources'          => 'array',
        'related_course_ids' => 'array',
        'batch_start_date'   => 'date',
        'batch_end_date'     => 'date',
        'marketplace_visible'=> 'boolean',
        'organization_published' => 'boolean',
        'submitted_at'       => 'datetime',
        'reviewed_at'        => 'datetime',
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
        static::updated(function ($c) {
            if ($c->marketplace_status === 'live' && $c->wasChanged()) {
                CourseChangeAudit::create(['course_id' => $c->id, 'actor_id' => auth()->id(),
                    'subject_type' => 'course', 'subject_id' => $c->id,
                    'changed_fields' => array_keys($c->getChanges())]);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $slug  = Str::slug($title);
        $count = static::withTrashed()->where('slug', 'like', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function vendor()       { return $this->belongsTo(Vendor::class); }
    public function organizationClasses() { return $this->belongsToMany(OrganizationClass::class, 'organization_class_courses'); }
    public function primaryInstructor() { return $this->belongsTo(User::class, 'primary_instructor_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function marketplaceReviews() { return $this->hasMany(CourseReview::class); }
    public function marketplaceSales() { return $this->hasMany(MarketplaceSale::class); }
    public function changeAudits() { return $this->hasMany(CourseChangeAudit::class); }
    public function announcements() { return $this->hasMany(CourseAnnouncement::class)->latest(); }
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

    public function canManageContent(User $user): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) return true;
        if ($user->instructorProfile()->where('status', 'suspended')->exists()) return false;

        if (!$this->vendor_id) return $this->primary_instructor_id === $user->id;
        if ($this->vendor?->status !== 'active') return false;
        if ($this->vendor->managers()->where('users.id', $user->id)->exists()) return true;

        return $this->vendor->teachers()->where('users.id', $user->id)->exists()
            && ($this->primary_instructor_id === $user->id
                || $this->teachers()->where('users.id', $user->id)->exists());
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
        if ($this->upcoming) return false;
        if ($this->visibility === 'draft') return false;
        if (!$user) return $this->visibility === 'public';
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) return true;
        if ($this->visibility === 'public') return true;
        if ($this->visibility === 'restricted') {
            return $this->allowedUsers()->where('user_id', $user->id)->exists();
        }
        return false;
    }

    public function scopeMarketplaceLive($query)
    {
        return $query->where('marketplace_status', 'live')->where('marketplace_visible', true)
            ->where('is_published', true)->where('visibility', 'public')->where('upcoming', false);
    }

    public function isMarketplaceLive(): bool
    {
        return $this->marketplace_status === 'live' && (bool) $this->marketplace_visible
            && (bool) $this->is_published && $this->visibility === 'public' && !$this->upcoming;
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
