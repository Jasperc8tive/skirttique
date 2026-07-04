/**
 * Skirttique — theme.json generator.
 *
 * design-system/tokens.css is the single source of design truth.
 * This script:
 *   1. copies tokens.css into the theme (assets/css/tokens.css) for runtime use,
 *   2. parses its custom properties and regenerates theme.json presets from them.
 *
 * Never edit theme.json by hand — change tokens.css and run `npm run tokens`
 * (also runs automatically before every build).
 */

import { readFileSync, writeFileSync, copyFileSync, mkdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const themeDir = dirname(dirname(fileURLToPath(import.meta.url)));
const repoRoot = join(themeDir, "..", "..", "..");
const tokensSrc = join(repoRoot, "design-system", "tokens.css");
const tokensDest = join(themeDir, "assets", "css", "tokens.css");
const themeJsonPath = join(themeDir, "theme.json");

/* 1 — copy tokens.css into the theme */
mkdirSync(dirname(tokensDest), { recursive: true });
copyFileSync(tokensSrc, tokensDest);

/* 2 — parse :root custom properties (comments stripped) */
const css = readFileSync(tokensSrc, "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
const rootBlock = css.match(/:root\s*{([\s\S]*?)}/)?.[1] ?? "";
const tokens = {};
for (const [, name, value] of rootBlock.matchAll(/--st-([a-z0-9-]+)\s*:\s*([^;]+);/g)) {
  tokens[name] = value.trim();
}

const title = (slug) =>
  slug.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());

/* Colours → settings.color.palette */
const palette = Object.entries(tokens)
  .filter(([k]) => k.startsWith("color-"))
  .map(([k, v]) => ({ slug: k.replace("color-", ""), name: title(k.replace("color-", "")), color: v }));

/* Type sizes → settings.typography.fontSizes (clamp() strings pass through) */
const fontSizes = Object.entries(tokens)
  .filter(([k]) => k.startsWith("text-"))
  .map(([k, v]) => ({ slug: k.replace("text-", ""), name: title(k.replace("text-", "")), size: v, fluid: false }));

/* Space → settings.spacing.spacingSizes */
const spacingSizes = Object.entries(tokens)
  .filter(([k]) => k.startsWith("space-"))
  .map(([k, v]) => ({ slug: k.replace("space-", ""), name: `Space ${k.replace("space-", "")}`, size: v }));
if (tokens.section) {
  spacingSizes.push({ slug: "section", name: "Section rhythm", size: tokens.section });
}

/* Everything else → settings.custom (nested on first dash: ease-silk → custom.ease.silk) */
const PRESET_PREFIXES = ["color-", "text-", "space-", "font-"];
const custom = {};
for (const [k, v] of Object.entries(tokens)) {
  if (PRESET_PREFIXES.some((p) => k.startsWith(p)) || k === "section") continue;
  const [group, ...rest] = k.split("-");
  if (rest.length === 0) {
    custom[group] = v;
  } else {
    custom[group] ??= {};
    if (typeof custom[group] === "string") custom[group] = { base: custom[group] };
    custom[group][rest.join("-")] = v;
  }
}

/* Static structure — fonts, layout, and base styles that are not raw token values */
const themeJson = {
  $schema: "https://schemas.wp.org/trunk/theme.json",
  version: 3,
  settings: {
    appearanceTools: true,
    layout: { contentSize: "68rem", wideSize: "90rem" },
    color: {
      palette,
      custom: false,
      customGradient: false,
      defaultPalette: false,
      defaultGradients: false,
      gradients: [],
    },
    spacing: {
      spacingSizes,
      units: ["px", "rem", "%", "vh", "vw"],
    },
    typography: {
      fluid: false,
      customFontSize: false,
      fontSizes,
      fontFamilies: [
        {
          slug: "display",
          name: "La Luxes Serif (display)",
          fontFamily: '"La Luxes", Didot, "Bodoni MT", Georgia, serif',
          fontFace: [
            {
              fontFamily: "La Luxes",
              fontWeight: "400",
              fontStyle: "normal",
              fontDisplay: "swap",
              src: ["file:./assets/fonts/laluxes-regular.woff2"],
            },
          ],
        },
        {
          slug: "body",
          name: "Garet (body)",
          fontFamily: 'Garet, "Avenir Next", Futura, "Century Gothic", sans-serif',
          fontFace: [
            {
              fontFamily: "Garet",
              fontWeight: "400",
              fontStyle: "normal",
              fontDisplay: "swap",
              src: ["file:./assets/fonts/garet-book.woff2"],
            },
          ],
        },
        {
          slug: "script",
          name: "Parfumerie Script (accent — file pending)",
          fontFamily: '"Parfumerie Script", "Snell Roundhand", "Segoe Script", cursive',
        },
      ],
    },
    custom,
  },
  styles: {
    color: {
      background: "var(--wp--preset--color--nectar)",
      text: "var(--wp--preset--color--seaweed)",
    },
    typography: {
      fontFamily: "var(--wp--preset--font-family--body)",
      fontSize: "var(--wp--preset--font-size--body)",
      lineHeight: "1.65",
    },
    elements: {
      heading: {
        typography: {
          fontFamily: "var(--wp--preset--font-family--display)",
          fontWeight: "400",
          lineHeight: "1.15",
          letterSpacing: "-0.015em",
        },
      },
      h1: { typography: { fontSize: "var(--wp--preset--font-size--h1)" } },
      h2: { typography: { fontSize: "var(--wp--preset--font-size--h2)" } },
      h3: { typography: { fontSize: "var(--wp--preset--font-size--h3)" } },
      button: {
        color: {
          background: "var(--wp--preset--color--foliage)",
          text: "var(--wp--preset--color--nectar)",
        },
        typography: {
          fontFamily: "var(--wp--preset--font-family--body)",
          fontSize: "var(--wp--preset--font-size--caption)",
          letterSpacing: "0.16em",
          textTransform: "uppercase",
        },
        border: { radius: "0px" },
        ":hover": {
          color: { background: "var(--wp--preset--color--foliage-deep)" },
        },
      },
    },
  },
  templateParts: [
    { name: "header", title: "Header", area: "header" },
    { name: "footer", title: "Footer", area: "footer" },
  ],
  customTemplates: [
    {
      // Full-width block-composed pages (FAQ, size guide, newsletter,
      // visit, …) — the About canvas, assignable from the editor.
      name: "page-canvas",
      title: "Canvas — full-width sections",
      postTypes: ["page"],
    },
  ],
};

writeFileSync(themeJsonPath, JSON.stringify(themeJson, null, "\t") + "\n");
console.log(
  `theme.json regenerated — ${palette.length} colours, ${fontSizes.length} sizes, ${spacingSizes.length} spaces; tokens.css copied into theme.`
);
