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
