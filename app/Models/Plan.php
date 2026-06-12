<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class Plan extends Model
{
    protected $fillable = ['name', 'duration_months', 'price_piastres'];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    // helper: get price in EGP
    public function getPriceEgpAttribute(): float
    {
        return $this->price_piastres / 100;
    }
}
