# Skirttique Testing — Stage 15

Three layers: fast deterministic **unit tests** for the money-critical
logic, a live **integration matrix** for the full multi-currency chain,
and a **manual checklist** for what only a human (or real gateway keys)
can judge. Automated layers run from the repo root:

```
npm test              # PHP unit suite + TypeScript typecheck
npm run test:currency # live multi-currency matrix (needs the dev site up)
```

## 1. Unit tests — `wp-content/plugins/skirttique-core/tests`

44 tests / 60 assertions, all passing. Pure logic, no database, no
Docker — runs in well under a second. Standard PHPUnit classes
(`phpunit.xml.dist` included); on a host without Composer a tiny shim +
`run.php` executes the identical files (`php tests/run.php`). The WP
functions the code touches are stubbed in `tests/bootstrap.php` — a
focused test double, not a WordPress emulator.

Coverage of the highest-risk paths:

- **Currency** — rate resolution (placeholder → option → filter
  precedence, case-insensitivity, unknown→1.0 fail-visible), conversion
  rounding to whole units, `''`/non-numeric pass-through, the
  admin-stays-NGN / admin-AJAX-converts scope guard, the WC currency +
  decimals + variation-cache-key filters.
- **Market** — cookie validation and fallback: default without a cookie,
  case normalisation, unknown/junk/injection values rejected to the
  default, the full five-market map, filter extensibility.
- **GatewayRouter** — NGN→Paystack / international→Stripe, unknown→Stripe
  default, case-insensitivity, the filter (Flutterwave-ready), the soft
  narrowing (only offer when configured, pass through when absent), admin
  left untouched.
- **HouseContent sanitizers** — text tag-stripping, script neutralising,
  textarea newline preservation, social-URL sanitisation +
  `javascript:` rejection, image-id validation (real image kept,
  non-image dropped to fallback), rate validation (positive-only,
  known-currency-only, junk dropped to placeholders).

## 2. Integration matrix — `tests/currency-matrix.sh`

Proves the chain the units can't reach alone: **market cookie →
`Market::current()` → Currency price filters → WooCommerce price HTML →
theme render**, against the running site. Per market it asserts the
correct symbol and a whole-number amount (checks the money *voice*, so
it survives rate changes). Latest run:

| Market | Rendered | NGN×rate |
|---|---|---|
| NG | ₦68,500 | base |
| US | $45 | 68 500 × 0.00065 = 44.5 → 45 |
| GB | £35 | × 0.00051 = 34.9 → 35 |
| ZA | R808 | × 0.0118 = 808.3 → 808 |
| AE | د.إ164 | × 0.0024 = 164.4 → 164 |

`5 passed, 0 failed` — every figure matches the unit math, whole units,
right symbol.

## 3. Behaviours already proven in earlier stages (regression anchors)

- Quick-add → bag drawer opens with the item + count bubble (Stage 7/12,
  re-verified live).
- Variable purchase form: fieldset/legend sizing, exclusive
  `aria-pressed`, Add gated until the choice completes (Stage 14).
- Dialog focus trap + restore-to-opener; closed drawers out of tab order
  (Stage 5/14).
- axe-core: 0 WCAG 2.2 AA violations across home/shop/PDP/saved/cart/
  quick-view (Stage 14).
- Per-page asset trims: wc-blocks off catalog, on cart/checkout
  (Stage 12).

## Manual QA checklist (pre-launch — needs a human or real keys)

Automated tooling cannot stand in for these. Run on staging after DNS
cutover with live-but-test gateway credentials.

**Purchase journeys (per gateway)**
- [ ] NGN order end-to-end through **Paystack test mode** — card success,
      card decline, cancelled/abandoned return.
- [ ] USD (and one more international) order through **Stripe test mode**
      — `4242…` success, a decline card, 3-D Secure challenge card.
- [ ] Confirm the order currency, totals, and gateway on the resulting
      WooCommerce order match the market the customer shopped in.
- [ ] Coupon applied in a non-NGN market converts/ög-holds correctly.

**Multi-currency depth**
- [ ] Switch market mid-session with items in the bag — prices and
      totals re-render; no mixed-currency cart.
- [ ] Variable product: price updates per size in each market.
- [ ] Shipping (GIG / DHL placeholders) converts with the cart.

**Screen readers** (VoiceOver + NVDA)
- [ ] Browse → open quick view → choose size → add → open bag → checkout,
      listening for sane announcements (not just axe-clean structure).
- [ ] Announcement/press pause buttons announce their pressed state.
- [ ] Form errors (checkout, newsletter) are announced on submit.

**Cross-browser / device**
- [ ] Safari (iOS) + Chrome (Android) real-device pass of the hero,
      drawers, sticky PDP summary, and the mobile gallery scroll-snap.
- [ ] 400% zoom / 320px reflow — no loss of content or horizontal scroll.

**Content edges**
- [ ] Out-of-stock and on-backorder products on card, PDP, quick view.
- [ ] A product with no gallery (single image) and one with 5+ images.
- [ ] House Settings: blank every field → shipped copy returns; set each
      → storefront reflects (fast regression of the Stage 11 contract).

> Note on the local rig: wp-env on Windows serves pages in 8–25 s and its
> headless-Chrome Lighthouse runs are unreliable (documented in
> docs/performance.md). Timing-sensitive manual checks belong on staging,
> not the local environment.
