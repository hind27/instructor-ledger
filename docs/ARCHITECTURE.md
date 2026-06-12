# Architecture Documentation

## Domain Model

The system has four core concepts:

- **Subscription** — a student pays for a plan upfront (monthly, 3-month, or annual)
- **Course Access** — which courses a student accessed under their subscription
- **Ledger Entry** — an immutable record of every money movement (earning, refund, payout)
- **Payout** — a payment sent to an instructor via the external provider

## Database Design

### Why a ledger pattern?

Instead of storing a single "balance" number per instructor, every money movement is recorded as an immutable ledger entry. This means:

- The balance is always calculated by summing entries — never stored directly
- Every transaction has a full audit trail
- Mistakes can be corrected by adding a reversing entry, never by editing existing data
- The system can answer "how much was owed on any given date" at any time

### Money storage

All amounts are stored as integers in piastres (1 EGP = 100 piastres). This avoids all floating point rounding errors. For example, EGP 120.50 is stored as 12050.

### Tables

- `plans` — the three subscription tiers
- `subscriptions` — student subscription records
- `courses` — platform courses belonging to instructors
- `subscription_course_accesses` — which courses a student accessed per subscription
- `ledger_entries` — immutable financial event log
- `payouts` — instructor payout records
- `payout_items` — links payouts to the ledger entries they cover

## Revenue Allocation Strategy

When a student pays for a subscription:

1. Platform takes 30% off the top
2. Remaining 70% goes into the instructor pool
3. The pool is divided among instructors proportionally by how many courses the student accessed from each instructor

**Example:**
- Student pays EGP 1200
- Platform cut: EGP 360
- Instructor pool: EGP 840
- Student accessed 2 courses from Instructor A, 1 from Instructor B
- Instructor A gets: 2/3 × 840 = EGP 560
- Instructor B gets: 1/3 × 840 = EGP 280

**Why this approach?**
Money follows actual usage. An instructor only earns from students who accessed their content. This is the fairest proxy for value delivered.

**Rounding:**
When amounts don't divide evenly, we use `floor()` for all instructors except the last, who receives the remainder. This ensures every single piastre is accounted for — no money is lost or created.

## Idempotency Approach

The system uses two layers of idempotency protection:

### Layer 1 — Allocation idempotency
Before allocating revenue for a subscription, the service checks if ledger entries already exist for that subscription. If they do, it returns early without creating duplicates.

Additionally, each ledger entry has a unique `idempotency_key` in the format `earning_{subscription_id}_{instructor_id}`. The database enforces uniqueness on this column, so even if the check is bypassed (e.g. race condition), MySQL will reject duplicate inserts.

### Layer 2 — Payout idempotency
Before creating a payout, the job generates an idempotency key based on the instructor ID and the range of ledger entry IDs being paid. If a payout with that key already exists, the job exits immediately.

This means running `payouts:run` multiple times, or having two servers dispatch jobs simultaneously, will never result in double payment.

## Provider Timeout Handling

The mock payment provider can return three outcomes:

- **Success** — payment confirmed, mark payout as `paid`
- **Failure** — payment rejected, mark payout as `failed`, safe to retry
- **Timeout** — unknown outcome, mark payout as `unknown`

The timeout case is the dangerous one. When a timeout occurs, money may or may not have moved. The system:

1. Marks the payout as `unknown` — never retries it blindly
2. Dispatches a `CheckPayoutStatus` job with a 5-minute delay
3. The status check job calls the provider's status endpoint to resolve the outcome
4. Updates the payout to `paid` or `failed` based on the result

This prevents double payment in the timeout scenario.

## Scaling Considerations

- The `payouts:run` command dispatches one queued job per instructor — jobs run in parallel
- `lockForUpdate()` on ledger entry queries prevents race conditions when two jobs run for the same instructor simultaneously
- For 500,000 active subscriptions, allocation should be done at subscription creation time (event-driven), not in batch
- Payout jobs should use Laravel Horizon for queue monitoring at scale
- Database indexes on `instructor_id`, `subscription_id`, and `type` columns are critical for query performance at scale

## Known Limitations

- The platform cut percentage (30%) is hardcoded — in production this should be configurable per plan or per instructor contract
- Course access is recorded manually in this implementation — in production it would be driven by real usage events
- The `CheckPayoutStatus` job assumes the provider reference is available — if a timeout happens before a reference is returned, the status cannot be checked automatically
