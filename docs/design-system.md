# Skirttique Design System — v1.0 (Stage 2)

The single written source of design truth for the Skirttique build. The machine-readable
counterpart is [`design-system/tokens.css`](../design-system/tokens.css); the visual proof is
[`design-system/index.html`](../design-system/index.html). In Stage 3 these tokens map 1:1 into
`theme.json` presets and SCSS variables — no value may be invented outside this document.

---

## 1. Principles

1. **Quiet, not empty.** Whitespace is the luxury. Every section earns its place.
2. **Green is the voice; orange is the whisper.** Dark Foliage carries the brand. Smoky Orange
   appears **at most once per view**.
3. **Structure from hairlines, not shadows.** Radius is `0` everywhere. Elevation exists only on
   drawers and overlays.
4. **Motion is fabric.** Two easings, three durations. Content settles the way a hem falls.
   `prefers-reduced-motion` disables all of it.
5. **Nothing looks like stock WooCommerce.** Every Woo surface is re-clothed in these tokens.

## 2. Signature — the Hemline

The one ornament the system allows, rooted in the product itself: a **1px line that catches air**.

- **Link underlines** lift from straight to a gentle quadratic curve on hover/focus
  (two stacked SVG paths cross-fading; 200ms silk ease).
- **Section dividers** are pre-curved hem lines (`Q` curve, 12px rise over full width).
- Usage: text links, dividers, active nav state. Never on buttons, never animated on scroll.

A Parfumerie Script annotation (one per page maximum) is the secondary signature — reserved for a
single hand-written word inside a serif statement. Blocked until the licensed font file arrives.

## 3. Colour

### Core roles

| Token | Hex | Role |
|---|---|---|
| `--st-color-nectar` | `#FDFFF4` | Page ground. Never pure white. |
| `--st-color-seaweed` | `#121B1A` | Primary ink. |
| `--st-color-foliage` | `#17320B` | Brand anchor: buttons, footer, dark bands. |
| `--st-color-sage` | `#7D9176` | Whisper: large captions, tints, hover washes. |
| `--st-color-amber` | `#BA6A31` | Rationed accent — one per view. |
| `--st-color-khaki` | `#F5D796` | Dark grounds only: eyebrows, focus rings. |

### Derived + semantic

`surface #F6F8EA` (cards/fields) · `hairline #E4E7D6` · `hairline-dark #2C4420` ·
`sage-deep #566851` (AA small text) · `amber-deep #91501D` (AA small text) ·
`foliage-deep #0F2306` (hover/pressed) · `ink-soft #3E4A45` (secondary text) ·
`error #96372A` (muted brick — never bright red) · `success #566851` ·
`focus #BA6A31` on light / `focus-dark #F5D796` on dark.

### Contrast rules (WCAG AA)

- Sage `#7D9176` and Amber `#BA6A31` pass **large text only** (~3.2:1 on Nectar). At small sizes
  always use `sage-deep` / `amber-deep` (≥4.5:1).
- Nectar on Foliage ≈ 13:1; Khaki on Foliage ≈ 9:1 — free use on dark grounds.
- Every interactive element has a visible `:focus-visible` ring: Amber on light, Khaki on dark.

## 4. Typography

| Role | Face | Case & tracking |
|---|---|---|
| Hero / display / H1–H3 | **La Luxes Serif** 400 | Caps-only face — every glyph renders as a capital. Keep statements short. `-0.015em`, leading 1.04–1.15 |
| Body, UI, forms | **Garet** Book | Sentence case, `0.01em`, leading 1.65 |
| Eyebrow, button, nav, caption | **Garet** Book | UPPERCASE, `0.16em` |
| Script accent (≤1 per page) | **Parfumerie Script** | As drawn — *file pending* |

Fluid scale (desktop-first `clamp()`): hero 52→108px · display 40→68px · h1 34→52px ·
h2 26→36px · h3 21→26px · body-lg 18px · body 16px · small 14px · caption 12px.

**Loading:** self-hosted subsetted WOFF2, `font-display: swap`, preload the two critical faces,
metric-compatible fallbacks (Didot/Georgia for La Luxes; Avenir Next/Futura for Garet) to hold CLS
near zero. Verified in-browser (Stage 2): **La Luxes is a caps-only display face** — lowercase
input renders as capitals, which suits headline-only duty and rules it out for running text. Additional weights (Light/Medium/Bold of both families) have reserved tokens
(`--st-weight-*`) and slot in without code changes.

## 5. Space & layout

- 4px base scale: `4 8 12 16 24 32 48 64 96 128 160`.
- Section rhythm: `--st-section` = `clamp(96px, 10vw, 160px)`.
- Container `1440px` max; fluid gutter `24→64px`; 12-column grid, 24px gap.
- Prose measure capped at `38rem`.
- Product imagery is always `3:4`; editorial bands `16:9`; portrait cards `4:5`.

## 6. Motion

| Token | Value | Use |
|---|---|---|
| `--st-ease-silk` | `cubic-bezier(0.22, 1, 0.36, 1)` | Reveals, hovers — settles like silk |
| `--st-ease-drape` | `cubic-bezier(0.65, 0, 0.35, 1)` | Drawers, image swaps — weighted |
| `--st-dur-micro / move / reveal` | `200 / 450 / 800ms` | Hover / drawer / scroll reveal |

**The Drape** (scroll reveal): `clip-path` opens bottom-up + 24px settle, once per element,
staggered ≤200ms. GSAP only where CSS cannot (page transitions); everything here is CSS-first.

## 7. Component rules (consumed by Stages 4–10)

- **Buttons:** rectangular, Garet caps 12px/0.16em, 18×40px padding. Primary = Foliage fill;
  secondary = 1px ink outline filling Foliage on hover; tertiary = hemline link. On dark grounds
  primary inverts to Nectar (hover Khaki).
- **Forms:** surface-filled fields, hairline borders, Foliage border on focus; error text in brick
  with a plain-language fix ("Enter a full email address, like name@example.com").
- **Product card:** 3:4 frame, crossfade to second image (450ms drape), quick-add bar rises on
  hover and is always visible on keyboard focus; meta = sage caption / serif name / price.
  Sale price is the view's single amber moment.
- **Announcement bar, nav, mini-cart, drawers:** specified in their stages, from these tokens only.

## 8. Voice in the interface

Plain verbs, sentence case, no filler: "Add to bag", "Join the house list", "Delivery & returns".
An action keeps its name through the flow (Publish → Published). Errors say what happened and how
to fix it — never apologise, never vague. NGN prices use `₦` with thousands separators; all five
market currencies format locally (full multi-currency model, per Stage 1 approval).

## 9. theme.json mapping (Stage 3 contract)

`--st-color-*` → `settings.color.palette` (slug = token name) ·
`--st-text-*` → `settings.typography.fontSizes` ·
`--st-space-*` → `settings.spacing.spacingSizes` ·
fonts → `settings.typography.fontFamilies` with `fontFace` declarations.
SCSS mirrors tokens as `$st-*` via a generated map — one source, three consumers.
