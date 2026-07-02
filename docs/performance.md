# Skirttique Performance — Stage 12

What was measured, what was changed, and how production hosting must be
configured to hit the targets (Lighthouse 95+, LCP < 2.5s, CLS < 0.1).

## Reading the local numbers honestly

The dev environment (wp-env on a Windows bind mount, `SCRIPT_DEBUG`,
no page cache, two payment gateways loading thousands of PHP files per
request) serves pages in 8–25 **seconds**. Every millisecond metric in a
local Lighthouse run is dominated by that TTFB and says nothing about
production. What *does* transfer:

- **CLS 0 and TBT 0 ms** (baseline run) — layout stability and JS
  main-thread discipline are environment-independent. Both are already
  at target.
- Request-level audits (render-blocking resources, preload discovery,
  image formats/dimensions, unused CSS) — all addressed below.
- On production (Hostinger LiteSpeed + Cloudflare + page cache), TTFB
  is expected in the 100–400 ms band, which puts LCP well under 2.5s
  given the hero is preloaded and served as sized WebP.

Reports live in `/reports` (`home-mobile-before.json` / `-after.json`).

## Changes shipped in Stage 12

### Images — everything local, sized, WebP
- `tools/sideload-editorial-media.php` (idempotent, run once per
  environment) pulls the six verified editorial photographs into the
  media library **as WebP** and wires them where the CMS expects owner
  media: the four `product_cat` thumbnails and the House Settings
  hero/philosophy image ids. Every image on the site now serves from
  `/wp-content/uploads` with WordPress-generated `srcset`/`sizes`;
  the Unsplash hotlinks survive only as no-media fallbacks in the
  patterns. This also cleared the baseline's `image-aspect-ratio` and
  `image-size-responsive` failures (hotlinks lied about intrinsic size
  and served no responsive candidates) and removes the external-host
  dependency (third-party connection + DNS on the LCP path).
- The header collections panel now dresses itself from the first
  category thumbnail instead of a hotlink.

### Critical-path discovery
- **Font preloads never actually rendered before Stage 12** — the
  Stage 3 code used `wp_resource_hints`, which silently ignores the
  `preload` relation. Rebuilt on the `wp_preload_resources` filter
  (WP 6.1+): both brand WOFF2 files preload on every page, and on the
  front page the hero image preloads with `imagesrcset`/`imagesizes`
  and `fetchpriority=high`, so the LCP request starts before layout.

### Asset diet (per-page)
- `woocommerce_enqueue_styles → []` — the three classic WooCommerce
  stylesheets (general/layout/smallscreen) are dead weight; every Woo
  surface is restyled by the design system.
- `woocommerce-blocktheme` shim dequeued everywhere.
- `wc-blocks-style` (~250 KiB) now loads **only** on cart, checkout and
  account — the only block-component consumers. Dequeued in three
  passes (enqueue, print, footer-print) because Woo Blocks re-enqueues
  during block render.
- `wc-add-to-cart` script dequeued off those same pages (our own
  endpoints replaced Woo's archive buttons in Stage 6/7).
- WooCommerce **order attribution** feature disabled
  (`woocommerce_feature_order_attribution_enabled = no`) — removes
  `sourcebuster.js` + `order-attribution.js` from every page. Re-enable
  from WooCommerce → Settings → Advanced → Features if marketing wants
  source attribution on orders; the trade is ~12 KiB JS + a cookie per
  visitor.

Homepage head after the diet: 2 stylesheets (tokens + bundle,
~15 KiB total), fonts + hero preloaded, zero third-party requests.

### Kept deliberately
- **jQuery + cart-fragments** stay: the classic mini-cart drawer and
  header count ride Woo's fragment refresh. Revisit only if the drawer
  moves to the Store API.
- **jquery-migrate** stays: removing it saves ~11 KiB but risks gateway
  plugin JS (Paystack/Stripe) that may still use removed APIs. Not
  worth the audit surface now.
- **Font subsetting skipped**: the two WOFF2 files are 17 + 56 KiB and
  the character needs are multilingual-adjacent (₦, market names).
  Subsetting would save ~25 KiB against a real risk of missing glyphs;
  revisit post-launch with real traffic data.

### Accessibility touch-ups found by the audit
- Header market toggle raised to a ≥40px/44px target (WCAG 2.2 target
  size), matching the icon cluster.
- The baseline's `color-contrast` / `label-content-name-mismatch`
  failures were artifacts: the strained local Apache reset the CSS
  request mid-audit, so axe measured default-blue links on unstyled
  markup. Local media (fewer parallel connections) removes the trigger;
  the re-run is the proof either way.

## Production configuration (Hostinger + LiteSpeed + Cloudflare)

Apply at deploy (Stage 16 checklist references this section):

**LiteSpeed Cache plugin**
- Page cache ON, TTL 8h public / 30m private; ESI OFF (fragments are
  AJAX, not ESI); cache logged-in OFF (cart/account are session-shaped).
- Exclude from page cache: `/cart`, `/checkout`, `/my-account`,
  `/?wc-ajax=*` (LiteSpeed's WooCommerce integration does this when
  "WooCommerce" support is toggled on — verify, don't assume).
- Object cache: Redis if the plan provides it (Hostinger Business does
  via LiteSpeed's memcached/Redis toggle) — biggest TTFB lever after
  page cache for the uncached cart/checkout paths.
- CSS/JS minify ON; combine OFF (HTTP/2, and combining breaks per-page
  dequeues); "Load CSS asynchronously" OFF (our CSS is 15 KiB and
  async CSS causes FOUC on the tokens).
- Image optimization: leave OFF — media is uploaded as WebP already;
  double-processing degrades.
- Browser cache TTL 1 year for `/wp-content/uploads`, `/build`,
  `/assets/fonts` (immutable, hash/versioned URLs).

**Cloudflare**
- Proxy ON, Brotli ON, HTTP/3 ON, Early Hints ON (pairs with the
  preload links).
- Cache rule: "Cache Everything" for anonymous requests EXCEPT
  `/cart*`, `/checkout*`, `/my-account*`, `*wc-ajax*`, `*wp-json*` —
  or simpler and safer: default caching (static assets only) and let
  LiteSpeed own HTML caching. Choose one HTML cacher, never both.
- Disable Rocket Loader (breaks deferred module scripts and inline
  config ordering).

**WordPress**
- `WP_ENVIRONMENT_TYPE=production`, `SCRIPT_DEBUG` unset, debug log off.
- Search visibility ON (`blog_public=1`) — the dev environment is
  intentionally discouraged and its robots.txt reflects that; Stage 13
  owns robots/sitemap/meta.

## Deferred to later stages
- `meta-description` and `robots.txt` Lighthouse failures → Stage 13
  (SEO) with Rank Math.
- Full-page a11y sweep (all templates, keyboard walk) → Stage 14.
- A production Lighthouse pass from a real deploy is the only number
  that should be quoted against the 95+ target.
