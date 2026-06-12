<?php

use App\Models\LedgerEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionCourseAccess;
use App\Models\Course;
use App\Models\User;
use App\Services\RevenueAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;



// Helper to create a full subscription with course accesses
function createSubscriptionWithAccesses(int $amountPiastres, array $instructorCourseCounts): Subscription
{
    $plan = Plan::create([
        'name'            => 'monthly',
        'duration_months' => 1,
        'price_piastres'  => $amountPiastres,
    ]);

    $student = User::factory()->create();

    $subscription = Subscription::create([
        'user_id'              => $student->id,
        'plan_id'              => $plan->id,
        'amount_paid_piastres' => $amountPiastres,
        'starts_at'            => now(),
        'ends_at'              => now()->addMonth(),
        'status'               => 'active',
    ]);

    foreach ($instructorCourseCounts as $count) {
        $instructor = User::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            $course = Course::create([
                'title'         => 'Course ' . $i,
                'instructor_id' => $instructor->id,
            ]);
            SubscriptionCourseAccess::create([
                'subscription_id' => $subscription->id,
                'course_id'       => $course->id,
                'instructor_id'   => $instructor->id,
            ]);
        }
    }

    return $subscription;
}

// TEST 1: Basic allocation is correct
it('allocates revenue proportionally by courses accessed', function () {
    $subscription = createSubscriptionWithAccesses(120000, [2, 1]); // 2 courses + 1 course

    $service = new RevenueAllocationService();
    $service->allocate($subscription);

    $entries = LedgerEntry::where('subscription_id', $subscription->id)->get();

    expect($entries)->toHaveCount(2);
    expect($entries->sum('amount_piastres'))->toBe(84000); // 70% of 120000
});

// TEST 2: Running allocation twice never creates duplicate entries
it('never double allocates when called twice', function () {
    $subscription = createSubscriptionWithAccesses(120000, [1, 1]);

    $service = new RevenueAllocationService();
    $service->allocate($subscription);
    $service->allocate($subscription); // run twice

    $count = LedgerEntry::where('subscription_id', $subscription->id)->count();

    expect($count)->toBe(2); // still only 2 entries, not 4
});

// TEST 3: Total allocated never exceeds instructor pool
it('never allocates more than the instructor pool', function () {
    $subscription = createSubscriptionWithAccesses(100000, [1, 1, 1]); // 3 instructors

    $service = new RevenueAllocationService();
    $service->allocate($subscription);

    $total = LedgerEntry::where('subscription_id', $subscription->id)->sum('amount_piastres');
    $expectedPool = (int) (100000 * 0.70); // 70000

    expect((int) $total)->toBe($expectedPool);

});

// TEST 4: Rounding — every piastre is accounted for
it('accounts for every piastre with rounding', function () {
    // 100000 * 70% = 70000 / 3 = 23333.33...
    $subscription = createSubscriptionWithAccesses(100000, [1, 1, 1]);

    $service = new RevenueAllocationService();
    $service->allocate($subscription);

    $total = LedgerEntry::where('subscription_id', $subscription->id)->sum('amount_piastres');

    expect((int) $total)->toBe(70000); // not 69999 or 70001
});

// TEST 5: No accesses = no ledger entries
it('creates no entries when no courses were accessed', function () {
    $plan = Plan::create([
        'name'            => 'monthly',
        'duration_months' => 1,
        'price_piastres'  => 100000,
    ]);

    $student = User::factory()->create();

    $subscription = Subscription::create([
        'user_id'              => $student->id,
        'plan_id'              => $plan->id,
        'amount_paid_piastres' => 100000,
        'starts_at'            => now(),
        'ends_at'              => now()->addMonth(),
        'status'               => 'active',
    ]);

    $service = new RevenueAllocationService();
    $service->allocate($subscription);

    expect(LedgerEntry::count())->toBe(0);
});
