# Skirttique SEO — Stage 13

Rank Math (per the master project context) carries titles, meta, social
cards, sitemap, robots and the schema graph. The entire configuration is
code: [`tools/configure-seo.php`](../wp-content/themes/skirttique/tools/configure-seo.php)
in the theme — run it once per environment and production matches dev
exactly. No wizard steps to remember.

```
npx wp-env run cli wp eval-file wp-content/themes/skirttique/tools/configure-seo.php
wp rewrite flush   # in a SEPARATE request — sitemap routes register on
                   # the next boot, flushing in the same run bakes rules
                   # without them (found the hard way)
```

## What is configured

**Modules**: `sitemap`, `rich-snippet`, `woocommerce`, `seo-analysis`
only. Analytics stays off until Search Console is connected at launch;
the promotional modules (Content AI, instant indexing, …) stay off.

**Titles & meta**
- Separator `—` (em dash — matches the brand voice, not the default dash).
- Home: "Skirttique — Luxury Midi & Maxi Skirts" + crafted description.
- Products: `%title% — Skirttique`, description from the product's
  short description (each demo product has one; content entry should
  keep this habit — it is both the PDP lede and the SERP snippet).
- Collections: `%term% — Skirttique Collections`, description from the
  term description (editable under Products → Categories).

**Social cards**: full Open Graph + `summary_large_image` Twitter cards;
default share image = the House Settings hero (owner-controlled). Product
pages share their product image automatically.

**Schema** (verified on PDP): one Rank Math graph — Organization
("Skirttique", knowledge-graph type company) + WebSite + WebPage +
**BreadcrumbList** mirroring the theme's visual trail — plus WooCommerce
core's single Product/Offer block. **No duplicate Product schema**:
Rank Math's Woo module defers to the core-emitted block the theme
triggers in `patterns/product.php`.

**Sitemap**: `/sitemap_index.xml` → pages, products, collections, posts
(ready for the Journal), images included. Cart, checkout, account and
Saved are `noindex,follow` and excluded from the sitemap — utility pages
are reached by acting, not searching.

**robots.txt**: WordPress-virtual, WooCommerce's crawl hygiene
(add-to-cart params, uploads noise) + the sitemap reference.

**Housekeeping**: default Hello World post and Sample Page deleted —
they were live and sitemap-listed.

## Launch checklist (Stage 16 references this)

1. `blog_public = 1` on production (dev may discourage indexing).
2. Connect Rank Math → Google Search Console, submit `sitemap_index.xml`;
   then enable the `analytics` module.
3. Replace the knowledge-graph/OG fallback image with the real logotype
   asset when brand files arrive (`knowledgegraph_logo_id`,
   `open_graph_image_id` in Rank Math → Titles & Meta).
4. Enter the real social profile URLs (Rank Math → Titles & Meta →
   Social) once the brand accounts are confirmed — the footer links
   (House Settings) and `sameAs` schema should agree.
5. Re-verify with Google's Rich Results test on one product, one
   collection, and the homepage after DNS cutover.
