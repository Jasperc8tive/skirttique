# Skirttique Launch Runbook — Stage 16

The production go-live procedure. Target stack (from the project brief):
**Hostinger Business** (LiteSpeed) + **Cloudflare** + **Rank Math** +
**Paystack** (NGN) / **Stripe** (international).

The gate for the whole procedure is one command:

```
wp eval-file wp-content/themes/skirttique/tools/preflight.php
```

It must report **0 blocking (FAIL)** before DNS is pointed. Everything
below exists to get it there and to prove the store works once it is.

---

## 1. Build the release artifacts

On a machine with the toolchain (not the server):

```
npm ci
npm run theme:install
npm run build          # runs `tokens` first: tokens.css → theme.json + assets, then webpack
npm test               # PHP units + TypeScript typecheck — must pass
```

`build/index.{js,css,asset.php}` and `assets/css/tokens.css` are
generated — they must be present in the deploy, and never hand-edited.

## 2. What ships (and what must not)

**Deploy:**
- `wp-content/themes/skirttique/` — **including** `build/` and
  `assets/`, **excluding** `node_modules/`, `src/`, `tools/` are
  optional (operational scripts; handy to keep for first-boot config).
- `wp-content/plugins/skirttique-core/` — **excluding** `tests/`.

**Never deploy:** `node_modules/`, `.wp-env.json`, `.wp-env.override.json`,
`tools/dev-proxy.mjs`, `tools/wp-env-tune.mjs`, `/design-system/`,
`/reports/`, `Homepage.mp4`. (`.gitignore` already excludes the build
output and env files from version control.)

Install the same plugins the environment declares: WooCommerce, Woo
Paystack, WooCommerce Stripe Gateway, Rank Math.

## 3. wp-config.php — production constants

```php
define( 'WP_ENVIRONMENT_TYPE', 'production' );  // Currency/gateway guards + preflight rely on this
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
// SCRIPT_DEBUG unset (defaults false) — ship the minified bundle.
define( 'DISALLOW_FILE_EDIT', true );           // no theme/plugin editor in wp-admin
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
// Salts: regenerate at https://api.wordpress.org/secret-key/1.1/salt/
```

Set strong admin credentials; the dev `admin/password` must not exist in
production.

## 4. First-boot configuration (run once on the server)

WP-CLI, in order:

```
wp theme activate skirttique
wp rewrite structure '/%postname%/' --hard
wp option update blog_public 1
wp option update woocommerce_coming_soon no

# SEO — idempotent; then flush in a SEPARATE call (sitemap routes need a fresh boot)
wp eval-file wp-content/themes/skirttique/tools/configure-seo.php
wp rewrite flush

# Editorial media → local WebP + category/hero wiring (only if not migrating the uploads dir)
wp eval-file wp-content/themes/skirttique/tools/sideload-editorial-media.php

# Standing pages — legal/policy + the block-composed utility pages.
# BOTH need --use-include (plain eval-file's path-const regex exhausts the
# PCRE JIT stack on these files' long literals and silently no-ops).
wp eval-file --use-include wp-content/themes/skirttique/tools/seed-content-pages.php
wp eval-file --use-include wp-content/themes/skirttique/tools/seed-utility-pages.php

# New pattern files were added in Phase 2 — clear the pattern cache once.
wp eval "wp_get_theme()->delete_pattern_cache();"
```

The utility/campaign demo content in `seed-utility-pages.php` is
environment-guarded: the demo campaign is skipped when
`WP_ENVIRONMENT_TYPE=production`, so only the real pages (Size Guide,
FAQs, Contact, Newsletter, Visit) are created.

Then, in wp-admin:
- **WooCommerce → Settings → General**: base country/currency **NGN**,
  ₦ symbol left, **0 decimals** (matches the pricing voice).
- **Skirttique → House Settings**: real hero/philosophy imagery, social
  URLs, announcements, and — critically — **real currency rates**
  (placeholders will FAIL preflight's rate check once env=production).
- **Skirttique → House Settings → Contact**: client-care email,
  WhatsApp, hours, studio location, and any additional ateliers (the
  Visit page reads them). Blank falls back to shipped copy.
- **Skirttique → House Settings → Integrations**: paste a long-lived
  Instagram access token to light up the live "On Instagram" strip;
  leaving it blank keeps the shipped placeholder tiles. (The profile
  URL for the follow link lives under Social profiles.)
- Review the Size Guide / FAQs / Shipping / Returns / Cookie / Privacy /
  Terms copy — all shipped with a legal read-through flagged before
  launch.
- **Rewards** is deferred by decision (no loyalty plugin ships). When one
  is licensed, follow the integration checklist in
  [content.md](content.md) → *Rewards*.

## 5. Payment gateways — go-live

The GatewayRouter enforces **NGN → Paystack, everything else → Stripe**,
and is *soft*: a gateway only becomes the sole checkout offer once it is
enabled and configured, so a half-set store never dead-ends. To go live:

- **Paystack** (WooCommerce → Settings → Payments → Paystack): enable,
  **live** public + secret keys, disable test mode. Add the Paystack
  **webhook** URL from the gateway settings to your Paystack dashboard.
- **Stripe**: enable, **live** publishable + secret keys, test mode off.
  Register the Stripe **webhook** endpoint (payment intent + charge
  events) in the Stripe dashboard.
- Verify each is enabled with the market cookie set: NGN checkout offers
  only Paystack; a USD/GBP/etc. session offers only Stripe.

Preflight's gateway checks flip PASS once both are enabled.

## 6. Performance & caching

Apply the LiteSpeed Cache + Cloudflare configuration in
**[performance.md](performance.md) → Production configuration**
(page-cache TTLs, exclude cart/checkout/account/`wc-ajax`, one HTML
cacher only, Brotli/HTTP-3/Early-Hints, disable Rocket Loader). The
theme already: preloads fonts + the hero, ships WebP media with srcset,
trims WooCommerce assets per page, and disables order attribution.

## 7. SEO go-live

Per **[seo.md](seo.md) → Launch checklist**: confirm `blog_public=1`,
connect Rank Math to Google Search Console, submit
`/sitemap_index.xml`, enable the analytics module, and swap the
knowledge-graph/OG fallback image for the real logotype when it exists.

## 8. Preflight gate

```
wp eval-file wp-content/themes/skirttique/tools/preflight.php
```

Resolve every **FAIL**. Expected residual **WARN**s once configured:
none — on a fully live store env=production, rates set, gateways
enabled, pages filled should all read PASS. Any remaining WARN is a
conscious decision, not an accident.

## 9. Post-deploy smoke test (on production, before announcing)

Fast pass with real (test-mode-until-verified) data — then repeat one
order per gateway with **live** keys and a real card you refund:

- [ ] Home, shop, a PDP, cart, checkout all render with fonts + styles
      (no FOUC, no broken images).
- [ ] Switch market → prices convert + show the right symbol
      (`tests/currency-matrix.sh $PROD_URL` automates this).
- [ ] Add simple + variable product → bag drawer → checkout.
- [ ] **NGN order via Paystack** completes; order currency = NGN,
      gateway = Paystack.
- [ ] **International order via Stripe** completes; order currency
      matches the market.
- [ ] Order confirmation email sends; order appears in
      WooCommerce → Orders (HPOS).
- [ ] `/sitemap_index.xml` 200s; a product page shows Product schema
      (Google Rich Results test).
- [ ] robots.txt references the sitemap; cart/checkout/account are
      `noindex`.
- [ ] Mobile (real device): header, drawers, sticky PDP summary,
      gallery scroll-snap.

## 10. Rollback

- Keep the pre-deploy database export and the previous theme/plugin
  build.
- Gateway trouble only: disable the affected gateway — the soft router
  falls back to whatever remains enabled rather than blocking checkout.
- Full rollback: restore the DB export, redeploy the previous artifacts,
  purge LiteSpeed + Cloudflare caches.

## 11. Post-launch watch (first 48h)

- WooCommerce → Orders for stuck/failed payments; gateway dashboards for
  webhook delivery failures.
- Cloudflare analytics for cache-hit ratio and origin load.
- Search Console coverage once crawled.
- Core Web Vitals (field data) after real traffic — the only performance
  numbers worth quoting (local Lighthouse is not; see performance.md).

---

### Operational scripts (all read-only or idempotent)

| Script | Purpose |
|---|---|
| `tools/preflight.php` | Launch-readiness report (read-only, exit-codes) |
| `tools/configure-seo.php` | Rank Math config as code (idempotent) |
| `tools/sideload-editorial-media.php` | Editorial images → local WebP (idempotent) |
| `tests/run.php` / `npm run test:unit` | Plugin unit suite |
| `tests/currency-matrix.sh` | Live multi-currency check |
