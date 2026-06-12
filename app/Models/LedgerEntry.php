<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'instructor_id', 'subscription_id',
        'amount_piastres', 'type',
        'idempotency_key', 'notes'
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
