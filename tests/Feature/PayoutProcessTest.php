<?php

use App\Jobs\ProcessInstructorPayout;
use App\Models\Course;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionCourseAccess;
use App\Models\User;
use App\Services\MockPaymentProvider;
use App\Services\RevenueAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;



// Helper to set up an instructor with unpaid earnings
function createInstructorWithEarnings(int $amountPiastres = 120000): User
{
    $plan = Plan::create([
        'name'            => 'monthly',
        'duration_months' => 1,
        'price_piastres'  => $amountPiastres,
    ]);

    $student     = User::factory()->create();
    $instructor  = User::factory()->create();

    $course = Course::create([
        'title'         => 'Test Course',
        'instructor_id' => $instructor->id,
    ]);

    $subscription = Subscription::create([
        'user_id'              => $student->id,
        'plan_id'              => $plan->id,
        'amount_paid_piastres' => $amountPiastres,
        'starts_at'            => now(),
        'ends_at'              => now()->addMonth(),
        'status'               => 'active',
    ]);

    SubscriptionCourseAccess::create([
        'subscription_id' => $subscription->id,
        'course_id'       => $course->id,
        'instructor_id'   => $instructor->id,
    ]);

    $service = new RevenueAllocationService();
    $service->allocate($subscription);

    return $instructor;
}

// TEST 1: Running payout twice never double pays
it('never double pays when payout job runs twice', function () {
    $instructor = createInstructorWithEarnings();

    // Mock provider to always succeed
    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldReceive('pay')->once()->andReturn('prov_test123');
    app()->instance(MockPaymentProvider::class, $mockProvider);

    // Run job twice
    ProcessInstructorPayout::dispatchSync($instructor);
    ProcessInstructorPayout::dispatchSync($instructor);

    expect(Payout::count())->toBe(1); // only one payout created
    expect(Payout::first()->status)->toBe('paid');
});

// TEST 2: Retried job never double pays
it('never double pays when job is retried after failure', function () {
    $instructor = createInstructorWithEarnings();

    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldReceive('pay')->once()->andReturn('prov_retry123');
    app()->instance(MockPaymentProvider::class, $mockProvider);

    ProcessInstructorPayout::dispatchSync($instructor);
    ProcessInstructorPayout::dispatchSync($instructor); // simulates retry

    expect(Payout::count())->toBe(1);
});

// TEST 3: Provider timeout marks payout as unknown, not paid
it('marks payout as unknown when provider times out', function () {
    $instructor = createInstructorWithEarnings();

    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldReceive('pay')
        ->andThrow(new \RuntimeException('Payment provider timed out. Status unknown.'));
    app()->instance(MockPaymentProvider::class, $mockProvider);

    ProcessInstructorPayout::dispatchSync($instructor);

    expect(Payout::first()->status)->toBe('unknown');
});

// TEST 4: Provider failure marks payout as failed
it('marks payout as failed when provider rejects payment', function () {
    $instructor = createInstructorWithEarnings();

    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldReceive('pay')
        ->andThrow(new \RuntimeException('Payment provider rejected the payment.'));
    app()->instance(MockPaymentProvider::class, $mockProvider);

    ProcessInstructorPayout::dispatchSync($instructor);

    expect(Payout::first()->status)->toBe('failed');
});

// TEST 5: Instructor with no earnings gets no payout
it('creates no payout for instructor with no earnings', function () {
    $instructor = User::factory()->create();

    $mockProvider = Mockery::mock(MockPaymentProvider::class);
    $mockProvider->shouldNotReceive('pay');
    app()->instance(MockPaymentProvider::class, $mockProvider);

    ProcessInstructorPayout::dispatchSync($instructor);

    expect(Payout::count())->toBe(0);
});
