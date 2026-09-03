# Mini Claims Register

A lightweight insurance claims register built with Laravel, Livewire, and Flux UI. Records claims and payments, converts currencies via live exchange rates, and derives claim status automatically.

## Getting started

### Prerequisites

- PHP 8.4+
- Composer
- Node.js 18+
- PostgreSQL (or SQLite for local dev)
- ExchangeRate-API key (free tier available at https://www.exchangerate-api.com)

### Setup

```bash
git clone https://github.com/kwasii1/mini-claims-register.git
cd mini-claims-register

composer install
cp .env.example .env
php artisan key:generate

# Configure your database in .env (defaults to SQLite)
# Set your FX API key:
#   EXCHANGE_RATE_API_KEY=your-key-here

php artisan migrate --seed
npm install && npm run build
php artisan dev
```

Visit http://localhost:8000. The root URL redirects to the dashboard.

### Reviewer login

```
Email:    reviewer@example.com
Password: password
```

### Running tests

```bash
php artisan test --compact
```

### Linting

```bash
vendor/bin/pint --dirty --format agent
```

## Routes

| URL | Name | Description |
|---|---|---|
| `/` | `home` | Redirects to dashboard |
| `/dashboard` | `dashboard` | Stats cards + recent claims |
| `/claims` | `claims.index` | Filterable, paginated list |
| `/claims/register` | `claims.register` | New claim form |
| `/claims/{id}` | `claims.show` | Claim detail + record payment |

## Database schema

```
┌──────────────────────────────┐       ┌──────────────────────────────────┐
│          claims              │       │            payments              │
├──────────────────────────────┤       ├──────────────────────────────────┤
│ id              UUID (PK)    │       │ id              UUID (PK)        │
│ policy_number   VARCHAR UK   │       │ claim_id        UUID (FK) ───────┐
│ insured_name    VARCHAR      │       │ payment_date    DATE             │
│ loss_date       DATE         │       │ amount          BIGINT (cents)   │
│ date_notified   DATE         │       │ currency        VARCHAR(3)       │
│ loss_nature     VARCHAR      │       │ fx_rate_snapshot DECIMAL(12,6)   │
│ reserve_currency VARCHAR(3)  │       │ created_at      TIMESTAMP        │
│ estimated_loss_amount BIGINT │       │ updated_at      TIMESTAMP        │
│ approved_amount BIGINT NULL  │       └──────────────────────────────────┘
│ created_at      TIMESTAMP    │                     │
│ updated_at      TIMESTAMP    │                     │
└──────────────────────────────┘                     │
                                                     │
                          ┌──────────────────────────┘
                          │
                          ▼  ON DELETE CASCADE
                    claim_id → claims.id
```

**Key tables:**

- **claims** — Core entity. Stores policy details, loss info, reserve currency, and amounts in integer minor units (cents). `status` is computed at read time from `approved_amount`, payment totals, and outstanding balance.
- **payments** — Individual payments against a claim. Each row snapshots the FX rate at time of recording. `amount` is in the payment's own currency (minor units).
- **users** — Standard Laravel auth table (Fortify). A single `reviewer@example.com` seed user exists.
- **passkeys** — WebAuthn/passkey credentials linked to users.

## Design decisions

**Status is computed, not stored.** The claim status is derived on every read from `approved_amount`, payment totals, and outstanding balance. This avoids stale status if payments are added or amounts change. The three statuses are: "Reserved, not yet settled" (no approved amount), "Settled, payment outstanding" (approved but balance > 0), and "Settled and paid" (balance <= 0).

**Money stored as integer minor units.** All amounts are stored as cents (or pence/euro-cents) in integer columns. This avoids floating-point rounding issues. Display divides by 100; input multiplies by 100.

**FX rates cached with 7-day fallback.** The ExchangeRate-API pair endpoint is called on each cross-currency payment and cached for 1 hour via `Cache::remember()`. If the API is down, the system falls back to a 7-day "last-known" rate cache to avoid blocking payments during brief outages.

**Livewire SFC (single-file components).** Each page is a single `.blade.php` file with the PHP class at the top and the template below, following the Livewire 4 convention. No separate PHP class files for pages.

**Status badge colors.** Badges use Flux's `color` prop: green for settled, amber for outstanding, zinc for reserved.

## What I'd do differently with more time

- **Approvals workflow.** Currently there is no way to set `approved_amount` from the UI. In a real system, an approver would review the claim and enter the approved amount before payments can be recorded against it.
- **Payment deletion/adjustment.** Payments are append-only with no edit or delete. A production system would need reversal entries or an adjustment flow.
- **Optimistic locking.** Concurrent payments on the same claim could race. A version column or `DB::transaction` with `lockForUpdate` would prevent double-pay scenarios.
- **Audit trail.** No log of who created claims or payments, or when status changed. An `activity_log` table or Spatie Activitylog would be essential for compliance.
- **Proper pagination on the server side.** The claims list loads all matching records into memory and paginates in PHP. With large datasets, this should use `->paginate()` at the database level (the status filter currently requires in-memory filtering because it's computed, but a stored status column would fix this).
- **FX rate history display.** Showing the rate at the time of payment is useful, but a graph of historical rates or a "rate at date" lookup would add value.
- **Multi-user roles.** Only a single reviewer role exists. A real system would need adjuster, approver, and admin roles with proper authorization policies.
- **Export functionality.** CSV/PDF export of claims lists is a common requirement that isn't implemented here.
