<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\MockPaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPayoutStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payout $payout) {}

    public function handle(MockPaymentProvider $provider): void
    {
        if ($this->payout->status !== 'unknown') {
            return;
        }

        // If no reference, we can't check — leave as unknown
        if (is_null($this->payout->provider_reference)) {
            return;
        }

        $result = $provider->checkStatus($this->payout->provider_reference);

        if ($result === MockPaymentProvider::OUTCOME_SUCCESS) {
            $this->payout->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);
        } else {
            $this->payout->update(['status' => 'failed']);
        }
    }
}
