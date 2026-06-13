<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'amount_paid_piastres',
        'starts_at', 'ends_at', 'status'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function courseAccesses()
    {
        return $this->hasMany(SubscriptionCourseAccess::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
    public function statusHistory()
{
    return $this->hasMany(SubscriptionStatus::class);
}

public function latestStatus()
{
    return $this->hasOne(SubscriptionStatus::class)->latestOfMany('changed_at');
}
}
