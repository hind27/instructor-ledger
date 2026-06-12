<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInstructorPayout;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Console\Command;

class RunPayouts extends Command
{
    protected $signature   = 'payouts:run';
    protected $description = 'Process pending instructor payouts';

    public function handle(): void
    {
        // Find instructors with unpaid earnings
        $instructorIds = LedgerEntry::where('type', 'earning')
            ->whereNotIn('id', function ($query) {
                $query->select('ledger_entry_id')->from('payout_items');
            })
            ->distinct()
            ->pluck('instructor_id');

        if ($instructorIds->isEmpty()) {
            $this->info('No pending payouts.');
            return;
        }

        $this->info("Found {$instructorIds->count()} instructor(s) with pending payouts.");

        User::whereIn('id', $instructorIds)->each(function (User $instructor) {
            ProcessInstructorPayout::dispatch($instructor);
            $this->info("Dispatched payout job for instructor #{$instructor->id}");
        });

        $this->info('All payout jobs dispatched.');
    }
}
