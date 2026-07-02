# Skirttique Architecture (Stage 3)

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
  keys currency conversion and gateway routing off it). The footer part remains a
  placeholder; the designed footer is Stage 5.

## Plugin — `skirttique-core`

`Skirttique\Core\` PSR-4 (spl_autoload_register, no Composer dependency in production).
`Plugin::instance()->boot()` registers services; each service implements
`ServiceInterface::register()` and wires its own hooks. HPOS compatibility declared.

| Service | Stage it lands |
|---|---|
| `Services\Wishlist` — cookie + user-meta, merge on login | 7 |
| `Services\RecentlyViewed` — cookie ring buffer | 7 |
| `Services\QuickView` — dialog + quick add AJAX | 6–7 |
| `Payments\GatewayRouter` — currency→gateway map (NGN→Paystack, else Stripe) | 9 |
| `Shipping\CarrierInterface` — contract for GIG/DHL/FedEx/UPS methods | 9 |

## Local environment

`npx wp-env start` → WordPress (PHP 8.3) + WooCommerce latest at `http://localhost:8888`
(admin: `admin`/`password`). Theme and plugin are bind-mounted from `wp-content/`, so edits are
live. `npm run theme:start` gives watch-mode builds.

## Commands

| Command (repo root) | Does |
|---|---|
| `npm run env:start` / `env:stop` | boot / stop the Docker environment |
| `npm run theme:build` | regenerate theme.json from tokens + production build |
| `npm run theme:start` | watch mode |
| `npm run tokens` | token sync only |
