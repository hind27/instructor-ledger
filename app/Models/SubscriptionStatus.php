<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionStatus extends Model
{
    protected $fillable = [
        'subscription_id',
        'status',
        'reason',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
