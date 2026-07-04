# Skirttique

A luxury WooCommerce build for a global modest-fashion house — midi and
maxi skirts, made in limited runs in Lagos, sold into five markets. Not a
template: a custom block theme and a commerce plugin, built to feel
editorial, quiet, and expensive, and to convert.

Markets: Nigeria · South Africa · United Kingdom · United States · UAE.
Full multi-currency — the customer shops, sees prices, and **pays** in
their market's currency, routed to the right gateway (Paystack for NGN,
Stripe internationally).

---

## The split that matters

- **`wp-content/themes/skirttique/`** — the theme *dresses* the store.
  Block theme (FSE), PHP 8.3, TypeScript + SCSS, every value driven from
  one design-token file. Presentation only.
- **`wp-content/plugins/skirttique-core/`** — the plugin *runs* the
  store. Wishlist, recently-viewed, quick view, currency conversion,
  gateway routing, carriers, schema, the owner's House Settings screen.
  Survives any future redesign.

One rule underneath everything: **`design-system/tokens.css` is the
single source of design truth.** It generates `theme.json` and the SCSS
variables; nothing invents a colour or size outside it.

## Local development

Docker + Node. From the repo root:

```
npm install
npm run env:start        # boots wp-env (WordPress + WooCommerce + gateways + Rank Math) on :8888
npm run theme:install
npm run build            # tokens → theme.json + assets, then webpack
```

Site: http://127.0.0.1:8888 (use `127.0.0.1`, not `localhost` — IPv6
loopback isn't mapped). Admin: `admin` / `password`.

Watch mode: `npm run theme:start`. Design style guide (no WordPress):
`npx serve design-system` → :4173.

## Tests

```
npm test                 # PHP unit suite (44 tests) + TypeScript typecheck
npm run test:currency    # live multi-currency matrix (dev site must be up)
```

See [docs/testing.md](docs/testing.md) for the layers and the manual QA
checklist.

## Going to production

[docs/launch.md](docs/launch.md) is the runbook; the gate is
`wp eval-file wp-content/themes/skirttique/tools/preflight.php` (must
report 0 blockers).

## Documentation

| Doc | What |
|---|---|
| [architecture.md](docs/architecture.md) | Repo layout, theme/plugin design, data flow |
| [design-system.md](docs/design-system.md) | Tokens, type, colour, motion, the signature devices |
| [performance.md](docs/performance.md) | Optimizations + LiteSpeed/Cloudflare production config |
| [seo.md](docs/seo.md) | Rank Math config, schema, sitemap, launch checklist |
| [accessibility.md](docs/accessibility.md) | WCAG 2.2 AA record + manual checks |
| [testing.md](docs/testing.md) | Unit + integration + manual QA |
| [launch.md](docs/launch.md) | Production runbook |

## Build stages

**Phase 1** — the store, in 16 reviewed stages, each stopped for
approval: Architecture Audit → Design System → Theme Foundation → Header
→ Homepage → Collection Pages → Product Pages → Cart → Checkout →
Account → CMS Experience → Performance → SEO → Accessibility → Testing →
Launch. (The header stage brought the footer with it; the homepage
stage, the press/social-proof band.)

**Phase 2** — the editorial expansion, in 10 further reviewed stages
(theme + plugin 1.1.0): Block & Component Foundation → Content
Architecture (Journal / Lookbook / Campaign) → Homepage v2 → Collection
Landing Pages → Product Page v2 → Shop & Search (facets + instant
search) → Editorial Pages (About / Journal / Lookbook) → Commerce
Experience (bag progress, Order Success, Custom Orders) → Utility & Legal
(Size Guide, Contact, FAQ, Shipping/Returns, legal + consent, 404,
Coming Soon, Newsletter, Store Locator) → Integration, QA & Launch
(Instagram feed, full-journey QA). The block library is 23 native
Gutenberg blocks; every "builder" is the block editor composing them.
