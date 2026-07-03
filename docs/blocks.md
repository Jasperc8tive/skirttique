# Skirttique Block Library — Stage 17

Sixteen native Gutenberg blocks in a "Skirttique" editor category — the
reusable components every Phase 2 page composes from. No page-builder
plugin; the block editor **is** the builder.

## Architecture

Three layers, one source of markup:

```
inc/components.php   ← canonical section renderers (THE markup)
        ↑                       ↑
patterns/*.php          inc/blocks.php (16 render callbacks)
(homepage, footer)      + src/editor/index.jsx (sidebar fields,
                          ServerSideRender previews)
```

- **`inc/components.php`** — every section's markup exactly once,
  token-driven, escaping inside. The homepage and footer patterns were
  refactored to call these (verified byte-level: same classes, same
  aria wiring, same heading structure — zero visual change).
- **`inc/blocks.php`** — one PHP manifest registers all blocks via
  `register_block_type` with attributes + a render callback that adapts
  attributes onto a component. Adding a block = one manifest entry +
  one FIELDS entry.
- **`src/editor/index.jsx`** — client registration: a generic edit
  factory renders sidebar controls from a per-block FIELDS registry
  (text/textarea/url/toggle/number/select/image/images) and previews
  through `ServerSideRender`, so the editor canvas shows exactly what
  the storefront ships. **Keep FIELDS in sync with the PHP manifest.**
- Builds as a second webpack entry → `build/editor.js`, enqueued only in
  the editor (`enqueue_block_editor_assets`); the front end pays nothing.

## The blocks

| Block | Notes |
|---|---|
| Hero | Statement + CTA over media; `isBanner` demotes h1→h2; optional parallax |
| Editorial Section | Philosophy layout generalised; media left/right |
| CTA Band | Statement on Foliage ground |
| Collection Cards | All collections; imagery = category thumbnails |
| Product Grid | Source: newest/bestsellers/sale/collection/handpicked |
| Product Slider | Same sources on a scroll-snap rail (slider.ts arrows) |
| Testimonials | `Quote\|Source` lines; rotating with WCAG pause control |
| Newsletter | The house-list form as a standalone band (unique ids per instance) |
| FAQ | `Question\|Answer` lines → hairline accordion |
| Statistics | `Value\|Label` lines |
| Feature List | `Title\|Description` lines |
| Trust Badges | Toggleable promises (delivery/returns/secure/limited runs) |
| Gallery | Media-library multi-select; 2–4 columns; optional click-zoom |
| Video | Poster-first; ambient mode = muted loop, reduced-motion safe |
| Pricing | `Tier\|Price\|Description` hairline cards (Custom Orders) |
| Breadcrumbs | Trail from the queried object; empty outside a query |

Simple repeaters use pipe-delimited textarea lines — deliberate: fast to
edit, trivial to store, no nested-block complexity for three-line lists.

## Motion primitives (also Stage 17)

- **Page transitions** (`transitions.ts`) — a nectar veil on internal
  navigation. Conservative eligibility (plain left-click, same origin,
  no modifiers/targets/downloads/hash-only), bfcache-safe, and two
  failsafes: background-tab arrivals unveil by timeout (rAF never fires
  unfocused), and a blocked navigation never leaves the page dead behind
  the veil.
- **Parallax** (`parallax.ts`) — `data-st-parallax="factor"`, rAF-
  throttled transform-only drift, media oversized 112% so edges never show.
- **Zoom** (`media.ts`) — `data-st-zoom` figures: click/Enter toggles a
  pointer-origin close-up; keyboard-accessible (role=button, Esc).
- **Ambient video guard** (`media.ts`) — reduced-motion visitors get a
  paused poster with controls instead of an autoplaying loop.

All primitives no-op under `prefers-reduced-motion`.

## Reference page

`/component-library/` (private, editors only) instantiates every block —
regenerate with
`wp eval-file wp-content/themes/skirttique/tools/seed-component-library.php`.
It is the rendering test bed and the editor's living style guide.
