<?php

namespace App\Services;

use Illuminate\Support\Str;

class MockPaymentProvider
{
    // Possible outcomes
    const OUTCOME_SUCCESS = 'success';
    const OUTCOME_FAILURE = 'failure';
    const OUTCOME_TIMEOUT = 'timeout';

    /**
     * Attempt to pay an instructor.
     * Returns a reference ID on success.
     * Throws an exception on failure or timeout.
     */
    public function pay(int $amountPiastres, string $instructorReference): string
    {
        $outcome = $this->randomOutcome();

        return match($outcome) {
            self::OUTCOME_SUCCESS => $this->handleSuccess(),
            self::OUTCOME_FAILURE => $this->handleFailure(),
            self::OUTCOME_TIMEOUT => $this->handleTimeout(),
        };
    }

    /**
     * Check status of a previous payment attempt.
     * Used to resolve 'unknown' payouts.
     */
    public function checkStatus(string $providerReference): mixed
    {
        // Simulate: 70% chance it actually succeeded
        return rand(1, 10) <= 7
            ? self::OUTCOME_SUCCESS
            : self::OUTCOME_FAILURE;
    }

    private function randomOutcome(): string
    {
        $rand = rand(1, 10);

        return match(true) {
            $rand <= 6 => self::OUTCOME_SUCCESS, // 60% success
            $rand <= 8 => self::OUTCOME_FAILURE, // 20% failure
            default    => self::OUTCOME_TIMEOUT, // 20% timeout
        };
    }

    private function handleSuccess(): string
    {
        // Return a fake provider reference ID
        return 'prov_' . Str::random(16);
    }

    private function handleFailure(): never
    {
        throw new \RuntimeException('Payment provider rejected the payment.');
    }

    private function handleTimeout(): never
    {
        // Money MAY have moved — we just don't know
        throw new \RuntimeException('Payment provider timed out. Status unknown.');
    }
}
