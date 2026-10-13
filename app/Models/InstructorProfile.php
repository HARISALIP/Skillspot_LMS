<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorProfile extends Model
{
    protected $fillable = ['user_id', 'status', 'legal_name', 'bio', 'payout_account_name', 'payout_account_number', 'payout_ifsc', 'reviewed_by', 'review_note', 'approved_at'];

    protected function casts(): array
    {
        return ['payout_account_number' => 'encrypted', 'payout_ifsc' => 'encrypted', 'approved_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
