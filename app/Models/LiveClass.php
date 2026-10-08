<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id', 'created_by', 'title', 'slug', 'description',
        'platform', 'meeting_url', 'meeting_id', 'meeting_password',
        'schedule_type', 'total_hours', 'completed_hours',
        'batch_start_date', 'batch_end_date', 'batch_name',
        'status', 'max_students', 'scheduled_at',
    ];

    protected $casts = [
        'batch_start_date' => 'date',
        'batch_end_date'   => 'date',
        'total_hours'      => 'decimal:2',
        'completed_hours'  => 'decimal:2',
        'scheduled_at'     => 'datetime',
    ];


    public function vendor()      { return $this->belongsTo(Vendor::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }
    public function sessions()    { return $this->hasMany(LiveClassSession::class); }
    public function enrollments() { return $this->hasMany(LiveClassEnrollment::class); }
    public function attendance()   { return $this->hasMany(LiveClassAttendance::class); }

    // Currently live session (if any)
    public function activeSession()
    {
        return $this->sessions()->where('status', 'live')->latest()->first();
    }

    // Is teacher live right now?
    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    // Recalculate completed_hours from all ended sessions
    public function recalculateHours(): void
    {
        $total = $this->sessions()
            ->where('status', 'ended')
            ->whereNotNull('duration_minutes')
            ->sum('duration_minutes');

        $hours = round($total / 60, 2);
        $this->completed_hours = $hours;

        // Auto-complete if hours-based and target reached
        if ($this->schedule_type === 'hours_based' && $this->total_hours > 0 && $hours >= $this->total_hours) {
            $this->status = 'completed';
        }

        $this->save();
    }

    // Remaining hours
    public function remainingHours(): float
    {
        if ($this->schedule_type !== 'hours_based' || !$this->total_hours) return 0;
        return max(0, $this->total_hours - $this->completed_hours);
    }

    // Progress percentage
    public function progressPercent(): int
    {
        if ($this->schedule_type !== 'hours_based' || !$this->total_hours) return 0;
        return min(100, (int) round(($this->completed_hours / $this->total_hours) * 100));
    }
}
