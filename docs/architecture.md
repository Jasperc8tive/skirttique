# Skirttique Architecture

## Repository layout

```
C:\Skirttique
├── design-system/            Stage 2 — canonical tokens + living style guide
│   ├── tokens.css            ← SINGLE SOURCE OF DESIGN TRUTH
│   └── index.html            style guide (npx serve, port 4173)
├── docs/                     project documentation
├── wp-content/
│   ├── themes/skirttique/    custom block theme (presentation)
│   └── plugins/skirttique-core/  commerce functionality (behaviour)
├── .wp-env.json              local WordPress environment (Docker)
└── package.json              root: environment orchestration scripts
```

**The split that matters:** the *theme* dresses the store, the *plugin* runs it.
Wishlist data, gateway routing, and carrier integrations survive any future redesign.

## Theme — `skirttique`

Block theme (FSE), PHP 8.3, strict types everywhere.

- **`theme.json` is generated — never edit it.** `tools/generate-theme-json.mjs` parses
  `design-system/tokens.css`, copies it into `assets/css/tokens.css` (runtime source), and
  regenerates all presets. Runs automatically via `prebuild`. Change a colour in tokens.css →
  `npm run build` → editor palette, front end, and style guide all agree.
- **Build:** `@wordpress/scripts` (webpack). Entry `src/index.ts` → `build/index.{js,css}` +
  `index.asset.php` (cache-busting version + dependency array consumed by `inc/assets.php`).
  TypeScript strict; SCSS with zero raw values — every rule reads `var(--st-*)`.
- **`inc/` modules:** `setup.php` (supports, editor styles), `assets.php` (enqueues, font
  preloads), `performance.php` (core output stripping). functions.php is a thin loader.
- **System behaviours shipped now:** the Drape (`.st-drape` scroll reveal) and the Hemline
  (`a.st-hemline` signature underline), both progressive enhancements that degrade cleanly.
- Header (Stage 4): `patterns/header.php` — announcement rotator, minimal nav with a
  collections panel driven by `product_cat`, market selector (header popover on desktop,
  inside the menu drawer ≤60rem), and native `<dialog>` drawers for search, bag (Woo
  mini-cart + AJAX fragment on `[data-st-cart-count]`), and mobile menu. Market state:
  `skirttique_market` cookie ↔ `Skirttique\Core\Services\Market` (five markets; Stage 9
  keys currency conversion and gateway routing off it).
- **Footer (Stage 5):** `patterns/footer.php` — the house list (newsletter band posting to
  admin-post.php, no JS required: nonce + honeypot, success/error states rendered from
  `st-joined`/`st-join-error` query flags), hem divider, brand + Shop/House/Care columns,
  legal bar with the inline market selector. Social links are placeholder `#` until the
  brand profiles are confirmed (`skirttique_social_links` filter).
- **Catalog (Stage 6):** `templates/archive-product.html` → `patterns/shop.php` serves the
  shop page, every collection (`product_cat`) archive, and product search through Woo's
  template hierarchy. Editorial header + collection switcher + toolbar (count, GET-based
  `orderby` sort with a `<noscript>` Apply) + card grid + pagination.
  `Skirttique\WooCommerce\product_card()` (inc/woocommerce.php) renders the design-system
  card: 3:4 frame, hover/focus crossfade to the first gallery image, quick-add bar
  (`data-st-quick-add` → `quick-add.ts` → wc-ajax add_to_cart → fragments → bag drawer);
  variable/out-of-stock cards link to the product page instead. Sale `<ins>` is amber-deep —
  the view's single amber moment. The Woo mini-cart is re-clothed in `_drawer.scss`.
  Store base: NGN, `₦68,500` format, Lagos. Demo catalog: `tools/seed-demo-catalog.php`
  (run with `wp eval-file`; idempotent; placeholder Unsplash imagery — content stage
  replaces it). WooCommerce's Coming Soon mode is disabled (`woocommerce_coming_soon=no`).
- **Homepage (Stage 5):** `templates/front-page.html` → `patterns/homepage.php` — full-bleed
  hero, collections grid from real `product_cat` terms, split philosophy statement, "current
  edit" product rail (live `wc_get_products()` through `product_card()`), press-quote band
  (`skirttique_press_quotes` filter, client-voice placeholders), closing band anchoring to
  the footer's house list.
- **Product pages (Stage 7):** `templates/single-product.html` → `patterns/product.php` —
  breadcrumbs, stacked editorial gallery (scroll-snap strip ≤60rem), sticky summary with the
  shared purchase form (`Skirttique\WooCommerce\purchase_form()`: size buttons from variation
  attributes, slimmed variation map as `data-st-variations` JSON, price per selection, Add
  armed only on a complete in-stock choice — `purchase.ts`), accordion panels
  (`skirttique_pdp_panels` filter), "More from the house" (upsells padded with related), and
  the recently-viewed rail. Quick view: card buttons (`data-st-quickview`) open the
  `#st-drawer-quickview` modal dialog (footer pattern), body fetched from the plugin's
  endpoint and re-wired in place (`quickview.ts`). Wishlist: `[data-st-wishlist-toggle]`
  everywhere a purchase form renders; guests in localStorage, customers in user meta, guest
  list merges up on first logged-in view (`wishlist.ts`). The Saved page (`/saved/`, page
  slug → `templates/page-saved.html` → `patterns/saved.php`) renders client-side through the
  shared product-cards endpoint. Recently viewed is a localStorage ring buffer (8 ids,
  `recently-viewed.ts`) — no server tracking, nothing to consent-gate. All cart adds
  (cards + purchase forms) go through `cart.ts` → `wc-ajax=skirttique_add_to_cart` →
  fragments → bag drawer.
- **Bag & Checkout (Stage 8):** WooCommerce's block cart/checkout are kept (Store API,
  validation, express-payment readiness) and re-clothed, not rebuilt. Theme templates
  override Woo's: `page-cart.html` (full chrome + `patterns/bag-head.php` editorial head),
  `page-checkout.html` (distraction-reduced: `patterns/checkout-header.php` back-to-bag /
  logotype / secure note, `patterns/checkout-footer.php` legal line — no nav, no drawers),
  `order-confirmation.html` (full chrome around Woo's confirmation blocks).
  `_checkout.scss` restyles wc-block components with tokens only; buttons start from
  theme.json's button element (`wp-element-button`). The block cart mutates through the
  Store API — never legacy fragments — so `cart.ts#initCartSync` subscribes to
  `wc/store/cart` (when `wp.data` exists) to keep the header count bubble live and
  re-enhance hemlines after React re-renders. The cart page's empty state is replaced
  in page content by `tools/customize-cart-empty-state.php` (idempotent, `wp eval-file`).
  Placeholders until Stage 9: COD gateway ("Pay on delivery"), flat-rate shipping
  (NG zone ₦3,500 / rest-of-world ₦45,000) — GatewayRouter + real carriers replace both.
- **Account (Stage 10):** `templates/page-my-account.html` → `patterns/account-head.php`
  (greets by first name when signed in) around the classic `[woocommerce_my_account]`
  shortcode. `_account.scss` re-clothes Woo's classic markup: nav rail (hairline +
  foliage current-marker; wraps to a pill row ≤60rem), dashboard tiles (theme override
  `woocommerce/myaccount/dashboard.php` — `add_theme_support('woocommerce')` in setup.php
  unlocks overrides), orders tables (stacking to labelled rows ≤40rem via Woo's
  `data-title`), address cards, and the sign-in/register split. Menu voice via
  `account_menu_items` in inc/woocommerce.php: Overview / Orders / Saved pieces (→
  `/saved/`) / Addresses / Account details / Sign out — Downloads removed. Store
  settings: self-registration ON, customers choose their own password.
- **Content pages:** `templates/page.html` is the editorial prose shell (`.st-page`,
  `_page.scss`: measured column, serif title, ruled caption headings). Privacy Policy,
  Terms of Service, and Delivery & Returns are seeded by
  `tools/seed-content-pages.php` (idempotent, `wp eval-file`) — the copy mirrors the
  PDP-panel promises (GIG/DHL, 14-day unworn returns) and needs a legal read before
  launch. Size Guide, FAQs, and Contact remain unwritten.

## Plugin — `skirttique-core`

`Skirttique\Core\` PSR-4 (spl_autoload_register, no Composer dependency in production).
`Plugin::instance()->boot()` registers services; each service implements
`ServiceInterface::register()` and wires its own hooks. HPOS compatibility declared.

| Service | Stage it lands |
|---|---|
| `Services\Newsletter` — house-list capture: admin-post handler, local option store, `skirttique_newsletter_joined` hand-off to the marketing platform | shipped (5) |
| `Services\CartAjax` — `wc-ajax=skirttique_add_to_cart`, variation-aware add following Woo's own endpoint conventions | shipped (7) |
| `Services\Wishlist` — user-meta list behind nonce-verified get/toggle/merge endpoints (guest side lives in localStorage) | shipped (7) |
| `Services\RecentlyViewed` — shared read-only `skirttique_product_cards` fragment endpoint (tracking itself is client-side) | shipped (7) |
| `Services\QuickView` — `skirttique_quickview` fragment endpoint; markup via `skirttique_quickview_html` filter (theme) | shipped (7) |
| `Services\Currency` — full multi-currency: `woocommerce_currency` + price/variation/shipping conversion off `Market::current()`, NGN-based rates (`skirttique_currency_rates` option/filter, shipped rates are placeholders), whole-unit rounding, variation-price cache keyed by currency; admin stays NGN | shipped (9) |
| `Payments\GatewayRouter` — filters `woocommerce_available_payment_gateways` to the mapped gateway per currency (NGN→`paystack`, else `stripe`, `skirttique_currency_gateways` filter); passes through untouched when the mapped gateway is unconfigured so checkout never dead-ends | shipped (9) |
| `Shipping\CarrierInterface` — contract for GIG/DHL/FedEx/UPS methods | later stage |

Paystack (`woo-paystack`) and Stripe (`woocommerce-gateway-stripe`) are installed via
`.wp-env.json` and active but have NO API keys — until keys are entered they are not
"available", so the placeholder COD gateway carries checkout. Enter test keys in
WooCommerce → Settings → Payments to see the router narrow checkout to one gateway per
currency. Orders store their settlement currency natively (mixed-currency order history is
expected); WooCommerce's stock reports assume a single currency — a reporting decision for
a later stage.

## Local environment

`npx wp-env start` → WordPress (PHP 8.3) + WooCommerce latest at `http://localhost:8888`
(admin: `admin`/`password`). Theme and plugin are bind-mounted from `wp-content/`, so edits are
live. `npm run theme:start` gives watch-mode builds.

Two gotchas: use `127.0.0.1`, not `localhost`, in curl (IPv6 loopback fails); and WordPress
caches the theme's pattern-file list — after **adding** a new file to `patterns/`, run
`npx wp-env run cli -- wp eval 'wp_get_theme()->delete_pattern_cache();'` or the pattern
silently won't register (edits to existing patterns are always live).

## Commands

| Command (repo root) | Does |
|---|---|
| `npm run env:start` / `env:stop` | boot / stop the Docker environment (start auto-applies `env:tune`) |
| `npm run env:tune` | raise the container's opcache file cap (default 4000 < WP+Woo's ~8000 files → ~19s/page on the Windows bind mount; tuned ≈ 5s) |
| `npm run theme:build` | regenerate theme.json from tokens + production build |
| `npm run theme:start` | watch mode |
| `npm run tokens` | token sync only |
