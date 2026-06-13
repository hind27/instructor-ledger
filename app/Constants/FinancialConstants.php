<?php

namespace App\Constants;

class FinancialConstants
{
    // Platform revenue share
    const PLATFORM_CUT_PERCENT = 30;
    const INSTRUCTOR_SHARE_PERCENT = 70;

    // Ledger entry types
    const LEDGER_TYPE_EARNING = 'earning';
    const LEDGER_TYPE_REFUND  = 'refund';
    const LEDGER_TYPE_PAYOUT  = 'payout';

    // Payout statuses
    const PAYOUT_STATUS_PENDING    = 'pending';
    const PAYOUT_STATUS_PROCESSING = 'processing';
    const PAYOUT_STATUS_PAID       = 'paid';
    const PAYOUT_STATUS_FAILED     = 'failed';
    const PAYOUT_STATUS_UNKNOWN    = 'unknown';

    // Subscription statuses
    const SUBSCRIPTION_STATUS_ACTIVE   = 'active';
    const SUBSCRIPTION_STATUS_REFUNDED = 'refunded';
    const SUBSCRIPTION_STATUS_EXPIRED  = 'expired';

    // Plan types
    const PLAN_MONTHLY   = 'monthly';
    const PLAN_QUARTERLY = 'quarterly';
    const PLAN_ANNUAL    = 'annual';
}
