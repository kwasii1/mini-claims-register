# Mini Claims Register — Delivery Plan

Stack: Laravel + Livewire starter kit, Postgres, exchangerate-api.com for FX,
deployed on Railway or Fly.io.

## Decisions locked in
- **Auth**: keep the starter kit's login as-is. No public signup — seed a
  reviewer account and put its credentials in the README.
- **Currency conversion**: live calls to exchangerate-api.com. The rate used
  is snapshotted onto the `payment` row at save time, so historical totals
  never shift if today's rate changes later. Seeder uses a fixed local rate
  snapshot (no network/API-quota dependency at seed time). Tests mock the
  API client entirely.
- **Date filter**: user picks which date field to filter on (loss date vs
  date notified) via a selector in the filter UI, not a fixed choice.
- **Test coverage**: as high as practical, grown alongside each feature
  rather than bolted on at the end. Arithmetic and status-derivation logic
  get the most attention since that's explicitly graded.

---

## Phase 0 — Foundations
**Goal:** confirm the skeleton is ready to carry real domain logic.

- Laravel + Livewire starter kit installed, boots locally
- Postgres connection configured
- Starter kit auth kept, no signup flow needed
- Git repo initialized, first commit pushed to GitHub as **public**

**Milestone 0:** Empty app deployed and reachable at a public URL before any
claims logic exists — proves the hosting pipeline works before building on
top of it.

---

## Phase 1 — Data model
**Goal:** a schema that makes the money arithmetic and the currency-mismatch
case correct by construction.

- **Claim**: policy number, insured name, loss date, date notified, loss
  nature, reserve currency, estimated loss amount, approved amount
  (nullable — drives "Reserved, not yet settled")
- **Payment**: belongs to a claim, own date, own amount, own currency, plus
  the FX rate snapshot used to convert it into the claim's reserve currency
- Store money as integer minor units (cents), never float, consistently
  everywhere (totals, balances, seed data)
- `ExchangeRate` client/service wraps exchangerate-api.com, with a local
  cache table to avoid burning API quota on repeated lookups

**Milestone 1:** Migrations + models complete, factories/seeders can
produce a valid claim with payments in mixed currencies,
`php artisan migrate:fresh --seed` works cleanly with no network calls.

---

## Phase 2 — Claim registration
**Goal:** requirement 1, with tests.

- Form to register a claim with all required fields, validated (dates
  relative to each other make sense, amount positive, currency constrained
  to a fixed list)
- Claim detail/show page as the landing point after creation
- Feature tests: validation rules, successful creation, claim starts with
  no approved amount

**Milestone 2:** Can create a claim through the UI and see it stored
correctly, including the "no approved amount yet" edge case, covered by
tests.

---

## Phase 3 — Payments against a claim
**Goal:** requirement 2, with tests.

- Add one or more payments to a claim (own date, amount, currency)
- On save, call the exchange-rate service, snapshot the rate onto the
  payment row
- Payments list visible on the claim detail page
- Feature tests: single payment, multiple payments, mixed-currency
  payments, FX client mocked
- Unit tests: FX conversion math in isolation

**Milestone 3:** Can add multiple payments in multiple currencies to a
single claim, each with a stored conversion rate, all covered by tests
that don't hit the real API.

---

## Phase 4 — Derived figures & status
**Goal:** requirements 3 and 4 — the arithmetic core they're grading.

- Compute per claim: approved amount, total paid (converted to reserve
  currency via stored rates), outstanding balance (approved − paid)
- Status as a computed property, never a stored column that can drift:
  - No approved amount → *Reserved, not yet settled*
  - Approved set, balance > 0 → *Settled, payment outstanding*
  - Balance ≤ 0 → *Settled and paid*
- Decide and document behaviour when paid exceeds approved (negative
  balance — still "Settled and paid")
- Unit tests: balance/status for all three states, the zero-balance
  boundary, zero-payments case, overpayment case, multi-currency payment
  sums

**Milestone 4:** A claim approved in USD with a GBP payment shows a
correct, converted balance and status, with the conversion logic fully
testable and tested.

---

## Phase 5 — Exchange rate integration hardening
**Goal:** make the live API dependency robust enough to demo.

- Cache fetched rates locally (avoid a live call per page load)
- Fallback path if the API is unreachable or rate-limited (last cached
  rate, or a clear "conversion unavailable" state — never a silently wrong
  number)
- Tests cover the fallback path with the client mocked to fail

**Milestone 5:** Killing network access to the FX API doesn't break the
app — it degrades visibly and predictably.

---

## Phase 6 — List view, filters, totals
**Goal:** requirement 5, with tests.

- List of all claims: policy number, insured, status, approved, paid,
  balance, currency
- Filters: date range (with a selector for loss date vs date notified),
  status, currency — combinable
- Totals row at the foot, **grouped by currency**, respecting active
  filters
- Feature tests: each filter individually, filters combined, totals grouped
  correctly by currency, totals update when filters change

**Milestone 6:** Filtering by currency shows only that currency's totals;
combining filters narrows both rows and totals correctly; all covered by
tests.

---

## Phase 7 — Seed data
**Goal:** requirement 6, and a good first impression for the reviewer.

- Seeder producing 15+ claims covering every status, several currencies,
  at least a couple of cross-currency payment cases, a spread of dates for
  the filters to bite on
- Seeder uses a fixed local rate snapshot — no live API calls at seed time
- Seeded reviewer login account for auth
- Seeder runs as part of deploy so the hosted URL is populated on first
  visit

**Milestone 7:** Fresh `migrate:fresh --seed` on a clean environment (and
on the deployed host) produces a list view immediately worth looking at.

---

## Phase 8 — Polish & commit hygiene
**Goal:** "readable code and a sensible commit history" is explicitly
graded.

- Review commit history — squash noisy WIP commits, make it read as a
  story (schema → registration → payments → derived logic → FX hardening →
  list/filters → seed → tests alongside each), pairing features with their
  tests in commits
- Basic UI cleanup — starter kit styling is fine, just shouldn't look
  broken
- Consistent currency/amount formatting everywhere

**Milestone 8:** Repo history alone, read top to bottom, explains the
build without needing to read the code.

---

## Phase 9 — Deployment
**Goal:** the hosted URL deliverable.

- Deploy to Railway or Fly.io (Postgres-friendly, generous free tier)
- Configure secrets on the host, including the exchangerate-api.com key
- Confirm the seeder has actually run against the live DB
- Smoke-test the live URL cold, in an incognito window

**Milestone 9:** Public URL loads with seeded data, login works with the
documented reviewer credentials, all filters and FX-converted totals work
in production.

---

## Phase 10 — README
**Goal:** the third deliverable.

- How to run locally (env setup incl. exchangerate-api.com key, migrate,
  seed, serve)
- Reviewer login credentials
- Assumptions made explicit: FX rate snapshotting approach, overpayment
  handling, date-range filter behaviour
- "What I'd do differently with more time": e.g. queued/async rate fetch
  instead of synchronous on save, audit trail on payments, soft-delete/void
  handling, broader edge-case coverage

**Milestone 10:** Someone with zero context can clone, run, and understand
every design decision from the README alone.
