<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $fillable = ['payout_id', 'ledger_entry_id'];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
