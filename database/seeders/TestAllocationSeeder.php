<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionCourseAccess;
use App\Models\User;
use App\Services\RevenueAllocationService;
use Illuminate\Database\Seeder;

class TestAllocationSeeder extends Seeder
{
    public function run(): void
    {
        // Create plan
        $plan = Plan::create([
            'name'             => 'annual',
            'duration_months'  => 12,
            'price_piastres'   => 120000, // EGP 1200
        ]);

        // Create student
        $student = User::factory()->create(['name' => 'Student A']);

        // Create instructors
        $instructorA = User::factory()->create(['name' => 'Instructor A']);
        $instructorB = User::factory()->create(['name' => 'Instructor B']);

        // Create courses
        $course1 = Course::create(['title' => 'Laravel Basics',   'instructor_id' => $instructorA->id]);
        $course2 = Course::create(['title' => 'Laravel Advanced', 'instructor_id' => $instructorA->id]);
        $course3 = Course::create(['title' => 'Vue.js Basics',    'instructor_id' => $instructorB->id]);

        // Create subscription
        $subscription = Subscription::create([
            'user_id'               => $student->id,
            'plan_id'               => $plan->id,
            'amount_paid_piastres'  => 120000,
            'starts_at'             => now(),
            'ends_at'               => now()->addMonths(12),
            'status'                => 'active',
        ]);

        // Record course accesses
        SubscriptionCourseAccess::create(['subscription_id' => $subscription->id, 'course_id' => $course1->id, 'instructor_id' => $instructorA->id]);
        SubscriptionCourseAccess::create(['subscription_id' => $subscription->id, 'course_id' => $course2->id, 'instructor_id' => $instructorA->id]);
        SubscriptionCourseAccess::create(['subscription_id' => $subscription->id, 'course_id' => $course3->id, 'instructor_id' => $instructorB->id]);

        // Run allocation
        $service = new RevenueAllocationService();
        $service->allocate($subscription);

        // Show results
        $entries = \App\Models\LedgerEntry::all();
        foreach ($entries as $entry) {
            $this->command->info(
                "Instructor {$entry->instructor_id} → " .
                ($entry->amount_piastres / 100) . " EGP"
            );
        }
    }
}
