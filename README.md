# El Marché

One marketplace for green products and services in Tunisia — certified seed, organic inputs, local food, artisanat, upcycled goods, plants and flowers, agronomy consulting and workshops. One catalogue, one seller flow, one checkout, three languages.

Laravel 12 · Filament 3 · PostgreSQL · Tailwind

---

## The one idea worth understanding first

**The category tree is the engine.** Every category carries its own configuration:

```
Category
  ├─ requirements   → which credentials a seller must prove to list here
  ├─ fields         → which attributes the listing form asks for and validates
  ├─ rule           → fragile / perishable / live / lot number required / group booking
  └─ price caps     → state-fixed ceilings, enforced at save
```

So `semences/cereales` demands a Ministry of Agriculture authorization, asks for germination rate and lot number, and refuses any price above the published ceiling. `terroir/distillats` demands a sanitary authorization and a lot number. `artisanat/poterie` asks for dimensions and needs only verified identity. `services/ateliers` demands liability insurance.

Same form. Same code. Same tables. **Adding a category is rows, not a migration.**

Requirements and rules **cascade down the tree**, which is what stops someone adding a jam subcategory that quietly skips the food-safety check.

---

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

createdb green_marketplace          # or point DB_* at any Postgres
php artisan migrate --seed
php artisan storage:link

npm run dev
php artisan serve
```

Storefront: `http://localhost:8000` · Admin: `http://localhost:8000/admin`
Seeded admin: **admin@greenmarketplace.tn / password**

SQLite works for a quick look, but Postgres is the target: the listing attribute store is a JSON column that becomes `jsonb` with a GIN index, and the `listings_one_seller_chk` constraint is Postgres-only.

---

## Where the important code lives

| Concern | File |
|---|---|
| **The publishing gate — read this first** | `app/Services/Publishing/PublishingGate.php` |
| Credential lifecycle and enforcement | `app/Services/CredentialManager.php` |
| Category inheritance | `app/Models/Category.php` (`effectiveRequirements`, `effectiveFields`, `effectiveRule`, `activePriceCap`) |
| Cart → order, with snapshots and stock safety | `app/Services/OrderPlacer.php` |
| Gapless invoice numbering | `app/Services/DocumentNumberGenerator.php` |
| Launch categories, credentials, price caps | `database/seeders/` |
| Verification queue | `app/Filament/Resources/CredentialResource.php` |
| Green Score (Phase 2) | `app/Services/GreenScoreCalculator.php` |
| Seller analytics (Phase 2) | `app/Services/SellerAnalyticsService.php` |

### The publishing gate

Everything the marketplace promises a buyer is enforced in **one place**, at the moment a listing tries to go live:

- required credentials held, approved and unexpired
- price at or under the state ceiling
- every required category field filled
- lot number present where the category demands it
- no invasive species
- a title, an image, a season window if seasonal

Not in a form request (bypassable from the admin panel), not in a controller (bypassable from a console command) — in the gate, so **every path into `active` goes through the same rules.** The Filament admin action calls the same service. There is deliberately no override.

When it blocks, it records a `listing.blocked_by_requirement` audit entry with the violation codes. That is the most useful supply-side metric you have: it tells you exactly where onboarding friction is losing you sellers.

### Credential expiry is enforcement, not notification

`php artisan credentials:check-expiry` runs daily (already scheduled in `routes/console.php`):

1. marks lapsed credentials expired
2. **suspends every live listing that depended on them** — and only those; a potter who also sells seed does not lose their pottery when the seed authorization lapses
3. sends T-60 / T-30 / T-7 reminders, each exactly once

A silently expired certificate on a live "Bio certifié" listing is how a green marketplace produces its first scandal. This is the code that prevents it.

```bash
php artisan credentials:check-expiry --dry-run
```

---

## Deliberate decisions worth knowing

**Money is integer millimes.** TND has three decimals. `App\Support\Money` converts at the edges; no float touches a price. Floats in money become reconciliation problems, and reconciliation problems become seller disputes.

**The JSON attribute column is named `attribute_values`, not `attributes`.** `attributes` shadows Eloquent's internal property and breaks reads from inside the model. One rename, one confusing afternoon avoided.

**Order lines snapshot title and price.** A seller editing their listing tomorrow must not change what a buyer bought today.

**Stock decrements with a conditional update** (`where('stock', '>=', $qty)`) inside the checkout transaction. The `WHERE` clause is the lock — two simultaneous checkouts cannot oversell the last unit.

**Invoice numbering is gapless**, generated under `lockForUpdate` rather than an auto-increment, because a rolled-back row must not leave a hole in a Tunisian invoice sequence.

**RTL is a `dir` attribute, not a rewrite.** Every template uses logical properties (`ms-` / `me-` / `ps-` / `pe-`, `text-start` / `text-end`). There is no `ml-` or `mr-` in this codebase — keep it that way and Arabic stays free.

**Editing a live listing sends it back to `pending`** (`app/Observers/ListingObserver.php`). Otherwise a seller could publish something compliant and quietly swap the contents.

**Credential documents live on a private disk**, never `public`, served only through signed short-lived URLs.

---

## Tests

```bash
php artisan test
```

They cover the rules that protect the business, not the framework:

- an uncertified seller cannot list regulated goods
- an **expired** credential reads differently from a **missing** one (renew vs apply)
- a pending credential does not count
- price ceilings hold, including the 3% contract surcharge on protected varieties
- food cannot go live without a lot number
- requirements cascade from parent categories
- expiry suspends dependent listings and **only** dependent listings
- a renewal approved before the lapse keeps listings live
- order lines snapshot price and title
- checkout refuses to oversell
- multi-seller orders split into one shipment per seller
- invoice numbers stay sequential
- a buyer never sees another buyer's data; a seller never sees another seller's orders, analytics, or listings
- a delivery tracker never shows a step this app has no real timestamp for
- a category's listing count never leaks a sibling that merely shares a slug prefix

---

## Phase 2 additions

Everything below was audited against the existing schema and routes first — nothing here duplicates a table, controller, or credential concept that already existed; several ideas (a buyer/seller distinction for "seller", the `verified_seller` badge, the `REVENUE_STATUSES` definition of real revenue) are reused as-is across every new feature that needed them, rather than redefined per feature.

| Feature | New tables | Key files |
|---|---|---|
| Green Score | `green_score_rules` | `app/Services/GreenScoreCalculator.php` |
| Tunisian origin + storytelling | `listings.governorate`, story columns | `app/Models/Listing.php` |
| Seller follows | `follows` | `app/Http/Controllers/FollowController.php` |
| Restock alerts | `restock_alerts` | `app/Http/Controllers/RestockAlertController.php` |
| Notification preferences | `users.notify_social`, `.notify_announcements` | `app/Notifications/` |
| Review responses + reports | `review_reports`, `reviews.seller_response` | `app/Http/Controllers/ReviewController.php` |
| Seller story + storefront tabs | story columns on `users` | `app/Http/Controllers/SellerStoryController.php`, `resources/views/seller/storefront.blade.php` |
| Seller analytics dashboard | — | `app/Services/SellerAnalyticsService.php` |
| Search improvements | `search_queries` | `app/Http/Controllers/CatalogController.php` |
| Delivery tracking + personalization | `recently_viewed_listings` | `app/Models/Shipment.php` (`trackerSteps`), `resources/views/components/shipment-tracker.blade.php` |
| Admin control center | — | `app/Filament/Widgets/MarketplaceHealthStats.php`, `SellerActivityStats.php` |
| Reusable components | — | `resources/views/components/empty-state.blade.php`, `verification-badge.blade.php` |

**Every number is real.** Seller analytics, admin widgets, Green Score and personalization all read from actual orders, views, follows and reviews — there is no fabricated engagement score, no invented "confirmed at" delivery timestamp, and no seller-targeted review (every review here is listing-targeted, tied to a delivered order line, same as before Phase 2).

Two bugs found only by live-verifying against the real Postgres connection (SQLite tolerates both silently, so `php artisan test` alone would never catch them):

- `DocumentNumberGenerator` ran `lockForUpdate()->max('sequence')` — Postgres rejects `FOR UPDATE` on an aggregate outright, so **every checkout was crashing** before this was fixed to lock the actual candidate row instead.
- The category tree's "include descendants" filter matched by raw string prefix (`path LIKE 'vegetal%'`), so a category named e.g. `vegetal2` would have been silently counted as a child of `vegetal`. Fixed with a `/`-bounded match (`Category::selfAndDescendantsOf()`), reused by both the catalogue filter and the home page's per-category listing counts.

The home page's root-category listing counts also went from one query pair *per root category* to two queries total, regardless of how many categories exist.

---

## What is deliberately not here (P1 / P2)

Bookings and group bookings · institutional purchase (devis → bon de commande → invoice on terms) · carrier API and COD reconciliation ledger · the impact layer · voice and photo-first listing creation · subscription boxes · plot profiles and the input register.

A buyer- or seller-facing flow to actually **open** a dispute or report an incident is also not here: `disputes` and `incidents` (FR-104/105) are real tables an admin dashboard widget already counts, but nothing in the app ever writes a row into them yet. Building an admin screen to manage rows nothing can create would have been inventing UI for a feature that doesn't exist — see "Phase 2" below.

The PRD marks all of these P1 or P2. **Every one of them is a manual process first.** Verify documents by hand, phone the carrier, reconcile in a spreadsheet — automate only when the manual version actually hurts. That is what makes a solo build shippable in weeks rather than stalling at month four with nothing live.

---

## Before you launch — non-negotiable

1. **Get a Tunisian lawyer to review the position.** Verifying sellers and awarding badges like `Bio certifié` creates representations buyers rely on; "we're just a marketplace" and "we certify every seller" are hard to hold simultaneously. The code supports the honest version — clear `Vendu par X` attribution, badge disclosure pages, versioned timestamped agreements, lot traceability, an audit log. The legal framing is not code and is not this file.
2. **Replace the placeholder agreement text** in `ReferenceDataSeeder` with what that lawyer drafts. The versioning machinery is real; the words are not.
3. **Update the price caps each season.** They're in `database/seeders/CategorySeeder.php` and editable from the admin. The seeded values are the Ministry's 2026/27 cereal prices, published 18 August 2026.
4. **Build the recall console before the first food order,** not after. The tables (`recalls`, `recall_notifications`) and the lot data are already in place; the screen is not.
5. **Set up daily backups and verify a restore.** Once real sellers are on this, the database is their livelihood.

---

## Legal references embedded in the seed data

- Loi n° 99-42 — semences, plants et obtentions végétales (seed and nursery authorization)
- Loi n° 2019-25 — sécurité sanitaire des aliments (sanitary authorization)
- Loi n° 99-30 — agriculture biologique (organic certification, CTAB-accredited bodies)
- Arrêté du 19 février 2016 — exigences phytosanitaires (imported plant material)
- Ministry of Agriculture seed prices 2026/27, published 18 August 2026
