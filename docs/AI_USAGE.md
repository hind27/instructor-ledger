# AI Usage Documentation

## How I Used AI During This Task

I used Claude (Anthropic) as a pair programming assistant throughout this challenge. 
AI helped me move faster on implementation details while I focused on architectural decisions.

## Main Workflows

- Generating boilerplate code (migrations, model relationships, job structure)
- Debugging errors in real time (migration ordering, Filament version compatibility, Pest setup)
- Explaining unfamiliar concepts before I implemented them (ledger pattern, idempotency keys)

## What Was Fully Generated vs Manually Designed

### AI-assisted (generated then reviewed and modified by me):
- Migration boilerplate
- Model fillable arrays and relationships
- Pest test structure and syntax
- Filament resource structure

### Personally designed and reasoned by me:
- The decision to split revenue by courses accessed (not equally, not by course count)
- The two-layer idempotency approach (application-level check + database unique constraint)
- The decision to treat timeouts differently from failures — marking as `unknown` rather than retrying
- The ledger pattern choice — append-only entries instead of a mutable balance column
- Storing money as integers (piastres) to avoid float errors
- The rounding strategy — remainder goes to last instructor to account for every piastre

## What Makes This Solution Different

A typical AI-generated implementation would likely:
- Store money as decimal/float
- Use a simple balance column instead of a ledger
- Retry on timeout the same as on failure — causing potential double payments
- Skip the database-level unique constraint as a second safety layer

My solution uses defense in depth — the idempotency check at the application level AND the unique constraint at the database level. Even if the application logic is bypassed, the database rejects duplicates.

## Engineering Decisions I Made Personally

1. **Ledger over balance column** — balances are derived, never stored. This gives full auditability and makes refunds trivial (add a negative entry).

2. **Two-layer idempotency** — application check first, database constraint as backup. One layer can fail; both failing simultaneously is practically impossible.

3. **Timeout ≠ Failure** — these are fundamentally different states. A failure means money did not move. A timeout means we don't know. Treating them the same would risk double payment.

4. **Revenue earned at payment time** — I decided instructors earn their share when the student pays, not spread over the subscription term. This is simpler and defensible. The trade-off is that refunds require explicit reversal entries.

5. **Split by courses accessed** — fairest proxy for value delivered. An instructor who has 100 courses on the platform but a student only watched one should not earn the same as an instructor whose 5 courses the student watched extensively.

## Trade-offs I Chose

- Simplicity over completeness — no real-time usage tracking, course access is seeded manually
- Correctness over performance — `lockForUpdate()` adds overhead but prevents race conditions
- Explicit over implicit — every money movement has a named ledger entry type rather than relying on calculations from related tables
