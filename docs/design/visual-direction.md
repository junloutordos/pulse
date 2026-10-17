# Visual direction

Chosen 17 Oct 2026 by the project owner from three options (Atlas, Ledger, Field): **Atlas, with sans-serif headings instead of serif.**

- **Feel:** calm cartographic report. Hairline rules, square corners (2px), no shadows, no gradients, one ocean-blue accent. Hierarchy comes from type, spacing and alignment rather than boxes.
- **Type:** Source Sans 3 (headings at semibold, body) and IBM Plex Mono for figures. Fonts are bundled with `@fontsource` packages, so the app works offline.
- **Colour:** tokens in `resources/css/tokens.css`, light and dark. Sequential blue ramp for maps. Status colours (ok, warn, crit) are reserved for data status.
- **Rules kept from the design doc:** no purple gradients, glassmorphism, emoji icons, identical rounded cards, or accent bars on cards. Copy is plain and specific.
- **Checks:** `node scripts/check-contrast.mjs` enforces 4.5:1 for every text pair in both themes; `resources/js/__tests__/tokens.test.ts` keeps tokens complete and the display face sans-serif.
