# Skirttique Content Architecture — Stages 18 & 19

How editorial content is structured, and where each thing is edited.

## Content types

| Type | What | Edited at | URL |
|---|---|---|---|
| **Journal** | Editorial blog — deliberately core WordPress posts, NOT a CPT (feeds, comments, category archives, SEO support all come free) | Posts | `/journal/` (posts page) + `/{slug}/` |
| **Lookbook** (CPT, plugin) | Immersive photography stories, block-composed, no shopping pressure | Lookbooks menu | `/lookbook/` archive + singles |
| **Campaign** (CPT, plugin) | One standalone landing page per launch; no archive | Campaigns menu | `/campaign/{slug}/` |
| **Collections** | WooCommerce `product_cat`, now with editorial term-meta | Products → Categories | `/product-category/{slug}/` |

Journal categories seeded: Fashion, Styling, Behind the Scenes,
Campaigns, Guides, Care Tips, Lifestyle.

Both CPTs live in **skirttique-core** (content survives a theme change),
open in the block editor (`show_in_rest`) with a starter block template
(hero + gallery for lookbooks; hero + product grid + CTA for campaigns),
and render through minimal immersive templates
(`single-lookbook.html` / `single-campaign.html` — header, the composed
content, footer; nothing else).

Reading settings: `/` = the Home page (front-page template),
`/journal/` = posts page. Note: with a static front page, WordPress
canonically 301s `127.0.0.1` → the site host — use `curl -L` in scripts.

## Collection term-meta (Stage 20 consumes)

On Products → Categories → edit, alongside Woo's own thumbnail
(= the card image):

- **Collection story** (`st_collection_story`) — the editorial
  introduction for the landing page; blank falls back to the
  description.
- **Landing hero image** (`st_collection_hero_id`) — wide hero, distinct
  from the card thumbnail. Media picker (same script as House Settings).

Saved with nonce + `manage_product_terms` capability;
`Skirttique\Core\Services\CollectionMeta`.

## House Settings additions

- **Contact details** — client-care email, WhatsApp number
  (international format), business hours, studio location. Consumed by
  the Contact page and WhatsApp links in Stage 25.
- **Experience** — sitewide switches for page transitions and parallax
  (both default ON; `''`/never-saved means enabled, only an explicit
  `off` disables). Bridged to `window.stConfig.motion`; the motion
  modules early-return when switched off. `prefers-reduced-motion`
  is honoured regardless — the owner switch can only *reduce* motion,
  never force it on.

## Homepage v2 (Stage 19)

Thirteen sections, in order: Hero · Signature Collections · New In ·
Craftsmanship Story · Best Sellers · Featured Collection · Why
Skirttique · Featured Product · Philosophy · Editorial Lookbook ·
In Their Words · Instagram · Closing Band. All render through
`inc/components.php`; every editable value flows House Settings →
shipped copy.

**Homepage Builder** — the owner can compose their *own* front page from
Skirttique blocks (edit the front page in the block editor). If the
front-page content contains any `skirttique/*` block, that composition
replaces the shipped homepage entirely; a blank front page renders the
shipped thirteen sections. One switchable code path in
`patterns/homepage.php`, no settings to configure.

House Settings additions (Stage 19): craftsmanship statement/paragraph/
figure, featured collection (select; blank = first collection with an
editorial story), featured product (select; blank = newest piece), and
the "why Skirttique" reasons (`Title|Description` lines). The Featured
Collection section is fed by the Stage 18 term-meta — it upgrades itself
the moment a collection is given a story or landing hero.

Self-hiding sections: Editorial Lookbook renders nothing until a
lookbook is published; Instagram renders shipped placeholder tiles until
Stage 26 hydrates the real feed (and hides entirely if it has neither
tiles nor a profile URL).

## Collection landing pages (Stage 20)

Every collection (product_cat) archive is now an editorial landing —
one code path in `patterns/shop.php`, no separate template:

- **Full-bleed hero** carrying the h1 and the collection story. Image:
  landing hero term-meta → category thumbnail. Story: term-meta →
  description. A collection with no imagery keeps the plain text head.
- **Breadcrumbs** + the existing collection switcher and sorted grid.
- **"More from the house"** — the other collections as cards after the
  grid (the current one excluded).

Dressing a collection is entirely a Products → Categories task: name,
description, thumbnail, story, landing hero. Shipped stories were seeded
for the four live collections
(`tools/seed-collection-stories.php`, idempotent, never overwrites) —
replace them in wp-admin whenever the house prefers its own words.
Adding a future collection = create the category, add products, dress
it; the landing page exists immediately.

Also fixed here: product search rendered WooCommerce's default block
template because the theme never provided
`templates/product-search-results.html` — it now routes through the
same shop pattern as everything else.

## Product Page v2 (Stage 21)

- **Zoom** — every gallery figure carries `data-st-zoom` (the Stage 17
  primitive): pointer-origin close-up, keyboard-accessible, reduced-
  motion safe.
- **Editorial panels** — products gained a **Skirttique tab** (Products
  → edit): Fabric & composition, Care, The story
  (`Skirttique\Core\Services\ProductEditorial`, keys `_st_fabric` /
  `_st_care` / `_st_story`). Story and fabric panels hide when blank;
  care falls back to the house note. Panel order: The piece · The story
  · Fabric & composition · Care · Size & fit · Delivery & returns.
- **Worn with** — compact pairing rows in the summary: curated
  cross-sells first, padded from the piece's own collection. Simple
  pieces add straight to the bag; variable pieces link through.
  (Distinct from the upsell rail: the rail is browsing, this is pairing.)
- **Trust strip** — delivery/returns/secure badges between the buy form
  and the panels.
- **Reviews** — house-styled list (stars, verified-owner badge) + a
  rating form posting through standard WordPress comments; WooCommerce
  classifies the comment as a review and stores the rating. Follows the
  site's moderation settings; no rating pre-selected. Hidden entirely if
  reviews are disabled. Woo's structured data picks up the aggregate
  rating automatically.
- **Sticky add-to-bag** (mobile) — a bar that appears once the buy form
  scrolls out of view; simple pieces add directly, variable pieces read
  "Choose size" and scroll back to the choice (upgrading to a direct add
  once a size is picked). Implementation note: computed from the rect on
  an rAF-throttled scroll listener, NOT an IntersectionObserver — an
  instant jump past the form never produces an intersection transition,
  so an observer sleeps through anchor jumps.
- **360° spin** remains deferred until spin photography exists
  (decision of record from the Phase 2 kickoff).

Dev-only demo data: `tools/seed-product-editorial.php` (refuses to run
in production) dresses two demo pieces and adds two sample reviews.

## Shop & Search (Stage 22)

**Facets** — a refine row on every catalog view (shop, collections,
search), entirely server-rendered links and one GET form, so filtering
works with no JavaScript at all:

- **Size** — links using WooCommerce's *native* `filter_size` main-query
  param (no plugin, no custom query code). Shown only when sized
  products exist.
- **Price** — min/max inputs using Woo's native `min_price`/`max_price`
  params. Multi-currency aware: the shopper types the *market* currency
  and `Currency::normalize_price_filter()` converts the params back to
  the NGN base (floor/ceil, inclusive) before Woo's lookup-table filter
  reads them.
- **On sale** — a toggle link (`?on_sale=1`), ours via `pre_get_posts`
  (`apply_sale_facet` in inc/woocommerce.php).
- Facets, sort, search phrase, and collection all combine; sorting
  carries active facets as hidden inputs; a filtered-empty state offers
  "Clear filters".

**Instant search** — the header search drawer now shows as-you-type
results: `Services\Search` (`wc-ajax=skirttique_search`) returns up to 4
product cards (same `skirttique_product_card_html` renderer as
everything else), a total, and the full-results URL. search.ts debounces
300ms, aborts stale requests, announces counts through a live region,
and degrades to the plain GET form without JS. "View all n results"
lands on the Stage 20 search template.

## Editorial pages (Stage 23)

- **About** (`/about/`) — a magazine feature composed entirely from the
  block library (hero → origin editorial → stats → craftsmanship
  editorial → convictions → CTA), rendered by `page-about.html` (a
  full-width canvas). The composition is page content: the owner edits
  or reorders it in the block editor like any other page. Seeded by
  `tools/seed-editorial-pages.php` (idempotent — skipped once the page
  holds any Skirttique block). Copy makes no claims not already shipped
  elsewhere (no invented founders, dates, or press).
- **Journal** — `home.html` (posts page) and `category.html` both render
  the `skirttique/journal` pattern: editorial head, category filter row,
  article cards (cover, kicker, serif title, excerpt, date), pagination.
  Articles render through `single.html` → `skirttique/article`: centred
  kicker/title/date head, wide cover, measured prose, "more like this"
  from the same category, back-to-journal.
- **Lookbook archive** (`/lookbook/`) — `archive-lookbook.html` →
  `skirttique/lookbooks`: full-width covers with the title resting on
  the photograph. Singles gained an "All lookbooks" foot.
- Reminder: adding pattern files requires
  `wp eval "wp_get_theme()->delete_pattern_cache();"`.
- Demo journal articles are dev-only (environment-guarded in the seed).

## Commerce experience (Stage 24)

- **Bag** — a "delivery on the house" progress bar (server-rendered in
  `bag-head.php`, kept live by `ship-progress.ts` against
  `wc/store/cart`). The threshold is the first enabled free-shipping
  method's `min_amount`; a **placeholder ₦150,000 free-shipping method**
  was seeded on the NG zone (set the real threshold in WooCommerce →
  Shipping). Shown only in the NG market: the threshold is configured in
  NGN and cross-market conversion of `min_amount` is not yet handled —
  revisit with the real rate feed. Cross-sells were seeded on the demo
  pairs, so the block cart's cross-sells section now shows, re-clothed.
- **Checkout** — the trust strip (delivery/returns/secure) joins the
  encrypted-payments note in the checkout foot.
- **Order Success** — a house thank-you (`order-head.php`) with
  what-happens-next (confirmation → dispatch from Lagos in 2–4 working
  days → tracking) above the WooCommerce confirmation blocks.
- **Custom Orders** (`/custom-orders/`) — block-composed page (hero,
  four steps, two pricing tiers, bespoke FAQ) + the enquiry form
  (`bespoke-form.php` → `Skirttique\Core\Services\BespokeRequests`:
  nonce + honeypot, capped option storage, email to the client-care
  address, read-only inbox at Skirttique → Bespoke requests,
  `skirttique_bespoke_requested` action for future CRM hand-off).
  Deliberately a form, not a booking system (decision of record).
  Linked from the footer's House column.

### Rewards — decision memo (owner call required)

The plan of record is an **established plugin styled to the design
system** — but which plugin is a licensing decision:

- **Recommended:** *WooCommerce Points & Rewards* (official Woo
  extension, ~$129/yr) — single-purpose, HPOS-compatible, maintained by
  Woo, cleanest data model for the multi-currency setup (points accrue
  on order totals, which our orders store natively per currency —
  configure earn rates per currency or on the NGN-equivalent).
- **No-budget alternative:** *WPLoyalty (free tier)* — solid free core,
  upgrade path.
- Integration when licensed: install via `.wp-env.json` (never `wp
  plugin install` — see the Rank Math incident), style its account tab
  + cart notices in `_account.scss`/`_checkout.scss`, surface the
  balance on the My Account dashboard tile grid, and QA points accrual
  against a multi-currency order matrix before launch.

Not installed in this stage: shipping a third-party loyalty plugin the
house hasn't licensed (or an arbitrary free one) would be a liability,
not a feature. This mirrors how gateway keys are handled.

## Utility & Legal (Stage 25)

The service surfaces — every one composed from the block library where
it can be, so the owner edits it like any page.

- **Size Guide** (`/size-guide/`, `page-canvas` template) — the new
  `skirttique/size-chart` block renders the measurements in centimetres,
  every numeric cell carrying `data-st-cm`; `size-guide.ts` toggles
  cm/in as pure presentation (no-JS page = the cm table), persisting the
  choice in `localStorage` and announcing it through the table caption.
  Page = hero → chart → how-to-measure feature list → fit FAQ → contact
  CTA. Verified in-browser: 62cm→24.4in, caption swaps, choice persists.
- **Contact** (`/contact/`, `page-contact.html`) — `skirttique/contact-details`
  reads House Settings → Contact (email, WhatsApp as a `wa.me` link,
  hours, location; each falls back to shipped copy). The message form
  (`contact-form.php` → `Services\ContactMessages`) mirrors the bespoke
  flow exactly: nonce + honeypot, capped option storage, email to
  client care, read-only inbox at **Skirttique → Messages**, and a
  `skirttique_contact_message` action for a future CRM. Round-trip
  verified (redirect flag + stored entry). Enquiry forms share one
  ruleset with the bespoke form (`.st-bespoke, .st-enquiry`).
- **FAQ** (`/faqs/`, `page-canvas`) — the `skirttique/faq` block gained
  an `anchor` attribute (renders as the section id); the page is six
  anchored categories (Ordering, Delivery, Returns, Sizing, Custom
  orders, Care) under a jump row. `.st-section[id]` carries
  `scroll-margin-top` so anchors land clear of the header.
- **Shipping / Returns split** — the old Delivery & Returns page is
  *renamed* to `/shipping/` (Shipping & Delivery) and a new `/returns/`
  (Returns & Exchanges) is seeded. Footer Care column, checkout footer,
  and the PDP Delivery panel all point at the two. The retired
  `/delivery-returns/` 301s via `Services\LegacyPaths` (WordPress's own
  old-slug redirect does **not** cover pages — it keys on `name`, page
  requests parse as `pagename` — so the map is explicit, and in the
  plugin because URL permanence must survive a theme change).
- **Legal + consent** — Privacy and Terms rewritten to reference the
  split pages; a new **Cookie Policy** (`/cookies/`) lists every cookie
  honestly (essential only today; analytics gated on consent). The
  consent banner lives in the footer pattern (`[data-st-consent]`,
  server-rendered `hidden`); `consent.ts` shows it until a choice is
  made, writes `skirttique_consent` (all|essential, 180d), fires a
  `st:consent` event for future scripts to gate on, and the footer's
  "Cookie preferences" (`[data-st-consent-open]`) reopens it. Verified
  in-browser end to end.
- **404** (`templates/404.html` → `skirttique/lost`) — house-voiced
  head, a product search (plain GET, `post_type=product`), paths onward,
  then collection cards + the newest pieces. Verified a bogus URL 404s
  with the dressed page.
- **Coming Soon** — no custom gate: the theme provides
  `templates/coming-soon.html` (+ `skirttique/coming-soon` splash:
  logotype, one line, the house list, socials) and WooCommerce's own
  **Site visibility** mode (`woocommerce_coming_soon`) swaps it in. The
  switch stays in Woo's screen (the SEO-in-Rank-Math principle); the
  splash is `noindex,nofollow` via `inc/setup.php` (keyed on
  `$_wp_current_template_id === 'skirttique//coming-soon'`, bridged to
  both `wp_robots` and `rank_math/frontend/robots`). Preflight already
  FAILs launch while the mode is on. Flip-on/flip-off verified.
- **Newsletter landing** (`/newsletter/`, `page-canvas`) — hero →
  why-join feature list → the existing newsletter block. A destination
  for bio links/QR. Footer House column links it.
- **Store Locator** (`/visit/`, `page-canvas`) — `skirttique/locations`
  reads House Settings → Ateliers (`store_locations`, one per line:
  `Name|Address|Hours|Map link`; blank falls back to one card from the
  contact location + hours). Directions links only — no embedded map,
  because a keyed map service is an owner decision (the gateway-keys
  principle). Footer House column links it as "Visit the atelier".
- **Campaign landing** — `single-campaign.html` is the minimal canvas
  (the CPT's block template already ends in a CTA back to the shop); a
  DEV-ONLY demo campaign ("Midnight in Lagos", `noindex` per the Stage
  18 decision) proves hero + product grid + CTA. Verified over HTTP.

Three new blocks (size-chart, contact-details, locations) bring the
library to **23**; all three are mirrored in the editor FIELDS registry
and `page-canvas` is a new assignable custom template (both emitted by
`tools/generate-theme-json.mjs` — theme.json is generated, never edited
by hand). Seeds: `tools/seed-utility-pages.php` (the composed pages +
demo campaign) and the rewritten `tools/seed-content-pages.php` (the
five legal/policy pages). **Both must run with `wp eval-file
--use-include`** — plain eval-file's path-const regex exhausts the PCRE
JIT stack on these files' long string literals and silently evaluates to
nothing (exit 0, no output). Sanitiser coverage: `store_locations` added
to the textarea branch, with a test.

## Integrations, QA & Launch v2 (Stage 26)

The final Phase 2 stage — the live integration the placeholders were
waiting for, a full QA pass, and the launch touch-ups.

- **Instagram feed** — the `instagram()` component's tile source now runs
  curated block images → **live feed** → shipped placeholders. The live
  feed is `Services\InstagramFeed`: a server-side, cached fetch of the
  official Instagram Graph API (`graph.instagram.com/me/media`), NOT a
  third-party widget — no client token exposure, no render-blocking
  script, no layout shift (tiles are server-rendered through the new
  `skirttique_instagram_media` filter). Token-gated exactly like the
  gateways: **House Settings → Integrations → Instagram access token**;
  blank keeps the placeholder tiles so the section always renders. The
  result is cached one hour (transient) with a **last-good option** kept
  alongside, so an expired token or a failed request degrades to the most
  recent feed rather than a blank strip; the cache is dropped whenever
  House Settings is saved. Videos fall back to their thumbnail, images
  and album covers use `media_url`. Verified with a mocked API response:
  mapping, caching, cache-hit (no second request), last-good fallback,
  and the no-token placeholder path all pass.
- **Rewards** — the Stage 24 memo stands: **deferred by owner decision**
  (a licensing call). No loyalty plugin ships; the integration checklist
  in the Stage 24 memo is ready for whenever one is licensed. Preflight
  flags it as a conscious review item.
- **Full-journey QA** — 45 unit tests + strict TypeScript pass; the live
  currency matrix reads correctly across all five markets (₦/$/£/R/د.إ);
  **axe-core (WCAG 2.0/2.1/2.2 A+AA) reports zero violations** across
  nine views (home, shop, PDP, cart, size-guide, contact, faqs, visit,
  404); and the whole spine was driven end to end — shop → PDP → add to
  bag (Store API) → cart (the live ₦-progress bar reading "₦81,500 away
  from delivery on the house" at 46%) → checkout (trust strip + the split
  shipping/returns links). The a11y rig: `tools/dev-proxy.mjs`
  (`PROXY_PUBLIC_HOST=host.docker.internal:PORT`) rewrites the absolute
  `localhost:8888` asset URLs so the containerised Playwright browser
  loads real CSS/JS; axe-core is fetched from the theme's own
  `node_modules` through the proxy (WordPress serves it as a static
  file). Without the proxy, asset URLs 404 in the container and axe
  measures unstyled markup (the Stage 12 false-positive trap).
- **Launch pass** — theme + plugin bumped **1.0.0 → 1.1.0** (Phase 2).
  Preflight gained a Content & integrations section: block-library
  sanity (all 23 blocks must register — a FAIL catches a broken build or
  manifest/editor drift), the Instagram connection state, the Rewards
  deferral, and (production only) a demo-campaign-removed check.

## SEO / Performance panels — deliberate scope

The PRD asked for SEO and Performance settings panels. SEO settings
remain **Rank Math's screens** (duplicating them invites drift);
performance remains code + the documented host configuration
(docs/performance.md). What owners actually control lives in House
Settings: content, contact, rates, and now motion. This is a decision,
not an omission.

## Operational note (hard-won)

Never install a plugin both live (`wp plugin install`) **and** in
`.wp-env.json` — the environment installs its own copy beside the live
one and WordPress fatals on the duplicate (this bit us with Rank Math:
`Cannot redeclare rank_math()`). Plugins belong in `.wp-env.json` only;
the live install in Stage 13 was the mistake, corrected in Stage 18.
