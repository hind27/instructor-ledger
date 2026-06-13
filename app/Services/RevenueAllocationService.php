<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use App\Constants\FinancialConstants;

class RevenueAllocationService
{
    // Platform keeps 30%, instructors share 70%


    public function allocate(Subscription $subscription): void
    {
        // 1. Check if already allocated — idempotency check
        $alreadyAllocated = LedgerEntry::where('subscription_id', $subscription->id)
        ->where('type', FinancialConstants::LEDGER_TYPE_EARNING)
            ->exists();

        if ($alreadyAllocated) {
            return; // already done, skip silently
        }

        // 2. Calculate instructor share pool
        $totalPaid        = $subscription->amount_paid_piastres;
       $platformCut = (int) round($totalPaid * FinancialConstants::PLATFORM_CUT_PERCENT / 100);
        $instructorPool   = $totalPaid - $platformCut;

        // 3. Get courses accessed grouped by instructor
        $accessesByInstructor = $subscription->courseAccesses()
            ->select('instructor_id', DB::raw('count(*) as course_count'))
            ->groupBy('instructor_id')
            ->get();

        if ($accessesByInstructor->isEmpty()) {
            return; // no courses accessed, nothing to allocate
        }

        $totalCourses = $accessesByInstructor->sum('course_count');

        // 4. Calculate each instructor's share
        $shares      = [];
        $totalShared = 0;

        foreach ($accessesByInstructor as $index => $access) {
            if ($index === $accessesByInstructor->count() - 1) {
                // Last instructor gets the remainder — handles rounding
                $amount = $instructorPool - $totalShared;
            } else {
                $amount = (int) floor($instructorPool * $access->course_count / $totalCourses);
            }

            $shares[] = [
                'instructor_id' => $access->instructor_id,
                'amount'        => $amount,
            ];

            $totalShared += $amount;
        }

        // 5. Write ledger entries inside a transaction
        DB::transaction(function () use ($shares, $subscription) {
            foreach ($shares as $share) {
                LedgerEntry::create([
                    'instructor_id'   => $share['instructor_id'],
                    'subscription_id' => $subscription->id,
                    'amount_piastres' => $share['amount'],
                    'type'            => 'earning',
                    // idempotency_key = unique per instructor per subscription
                    'idempotency_key' => 'earning_' . $subscription->id . '_' . $share['instructor_id'],
                    'notes'           => 'Revenue share from subscription #' . $subscription->id,
                ]);
            }
        });
    }
}
