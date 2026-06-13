<?php

namespace App\Jobs;

use App\Constants\FinancialConstants;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\User;
use App\Services\MockPaymentProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessInstructorPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public User $instructor) {}

    public function handle(MockPaymentProvider $provider): void
    {
        // 1. Get all unpaid ledger entries for this instructor
        $unpaidEntries = LedgerEntry::where('instructor_id', $this->instructor->id)
            ->where('type', FinancialConstants::LEDGER_TYPE_EARNING)
            ->whereNotIn('id', function ($query) {
                $query->select('ledger_entry_id')->from('payout_items');
            })
            ->lockForUpdate() // prevents race conditions
            ->get();

        if ($unpaidEntries->isEmpty()) {
            return; // nothing to pay
        }

        $totalAmount = $unpaidEntries->sum('amount_piastres');

        // 2. Idempotency check — never create duplicate payout
        $idempotencyKey = 'payout_' . $this->instructor->id . '_' . $unpaidEntries->min('id') . '_' . $unpaidEntries->max('id');

        $existingPayout = Payout::where('idempotency_key', $idempotencyKey)->first();

        if ($existingPayout) {
            // already attempted — don't create another
            return;
        }

        // 3. Create payout record inside transaction
        $payout = DB::transaction(function () use ($unpaidEntries, $totalAmount, $idempotencyKey) {
            $payout = Payout::create([
                'instructor_id'   => $this->instructor->id,
                'amount_piastres' => $totalAmount,
                'status' => FinancialConstants::PAYOUT_STATUS_PROCESSING,
                'idempotency_key' => $idempotencyKey,
            ]);

            // Link ledger entries to this payout
            foreach ($unpaidEntries as $entry) {
                PayoutItem::create([
                    'payout_id'       => $payout->id,
                    'ledger_entry_id' => $entry->id,
                ]);
            }

            return $payout;
        });

        // 4. Call payment provider OUTSIDE transaction
        try {
            $reference = $provider->pay(
                $totalAmount,
                'instructor_' . $this->instructor->id
            );

            // Success
            $payout->update([
                'status' => FinancialConstants::PAYOUT_STATUS_PAID,
                'provider_reference' => $reference,
                'paid_at'            => now(),
            ]);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'timed out')) {
                // Unknown — money may have moved, do NOT retry blindly
                $payout->update(['status' => FinancialConstants::PAYOUT_STATUS_UNKNOWN]);

                // Dispatch a status check job
                CheckPayoutStatus::dispatch($payout)->delay(now()->addMinutes(5));
            } else {
                // Clean failure — safe to mark as failed
                $payout->update(['status' => FinancialConstants::PAYOUT_STATUS_FAILED]);
            }
        }
    }
}
