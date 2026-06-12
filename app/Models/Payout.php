<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'instructor_id', 'amount_piastres', 'status',
        'provider_reference', 'idempotency_key', 'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function items()
    {
        return $this->hasMany(PayoutItem::class);
    }
}
