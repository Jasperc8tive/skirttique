# Skirttique Accessibility — Stage 14

Target: WCAG 2.2 AA. Tested 2026-07-02 with axe-core 4.10.2 (WCAG A /
AA / 2.2-AA rulesets) in a settled Chromium via Playwright — not the
throttled Lighthouse rig, whose axe data was corrupted by dropped CSS
requests (see docs/performance.md).

## Results

| Surface | axe violations |
|---|---|
| Homepage (incl. rotators + pause controls) | 0 |
| Shop / collection grid | 0 |
| Product page | 0 |
| Saved (wishlist) | 0 |
| Cart (block cart, settled) | 0 |
| Quick view dialog (injected content, open state) | 0 |

Note on the block cart: axe run mid-hydration flags Woo core's skeleton
loaders (`aria-live` on spans). Those nodes exist for milliseconds on
production; the settled page is clean. Upstream (WooCommerce), not ours.

## Interaction contract (verified programmatically)

- **Skip link** → `#main-content` (core block-theme skip link; every
  template's `<main>` carries the id).
- **Headings**: exactly one `h1` per view; no skipped levels.
- **Drawers/dialogs** are native `<dialog>` + `showModal()`: focus trap
  and Esc are the platform's; closing restores focus to the opener
  (drawer.ts). Closed drawers are `visibility: hidden` so their contents
  never sit in the tab order (Stage 5 fix).
- **Purchase form**: sizes are real `<fieldset>`/`<legend>` with
  `aria-pressed` toggles — exactly one pressed; Add stays `disabled`
  until the choice completes; out-of-stock is announced through a
  `role="status"` note.
- **Rotators (WCAG 2.2.2)**: the announcement bar and press band now
  carry an explicit pause/play button (`aria-pressed`, swapped
  pause/play icons, 40px, 44px on touch). Hover/focus pause remains a
  courtesy; the button is the compliance mechanism. An explicit pause
  outlives hover state. Under `prefers-reduced-motion` rotation never
  starts and the button hides.
- **Touch targets**: 44px effective minimums on coarse pointers —
  header icons, market toggle, hemline links (vertical pseudo-element
  expansion), footer market buttons.
- **Focus visibility**: global `:focus-visible` ring (amber on light
  grounds, khaki on dark) in the base reset; component styles may
  restyle but never remove it.
- **Motion**: every animation (drape reveals, drawer slides, crossfades,
  hemline curves) is disabled or instant under `prefers-reduced-motion`;
  a `<noscript>` fallback keeps drape-hidden content visible without JS.
- **Contrast**: token-enforced — sage/amber never appear at small sizes
  (deep variants `#566851`/`#91501D` are ≥4.5:1 on Nectar); Nectar on
  Foliage ≈ 13:1; axe confirms 0 contrast violations on every surface.

## Manual checks that still belong in Stage 15 (Testing)

- A real screen-reader pass (NVDA + VoiceOver) through browse → size →
  add → checkout; automated tools cannot judge announcement quality.
- Checkout with items (gateway iframes bring third-party UI that axe
  can only assess live).
- 400% zoom / reflow spot-check.
