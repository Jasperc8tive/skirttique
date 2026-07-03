# Skirttique Content Architecture — Stage 18

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
