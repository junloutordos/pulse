# Visual direction

Chosen 17 Oct 2026 by the project owner: **Atlas, with sans-serif headings.** Deepened on 17 Oct 2026 after review ("not premium, too generic"), keeping the same direction.

- **Idea:** the map is the hero. A deep navy shell frames calm paper-coloured pages; the choropleth carries the colour.
- **Workflow:** the six steps are a real sequence, so they are the navigation (a numbered stepper) instead of eyebrow labels above every heading.
- **Type:** one clean sans-serif family, Hanken Grotesk, for titles, text, tables and figures (tabular figures). Sentence case everywhere. Bundled via `@fontsource-variable`, so the app works offline.
- **Surfaces:** hierarchy comes from tone steps (paper, white surface, sunken surface), a 1px ring and, for floating cards only, one soft shadow. No gradients, no emoji, no tracked all-caps labels. Radius differs by role: 6px controls, 10px groups, 16px panels, 0 for the full-bleed map.
- **Colour:** royal blue. Tokens in `resources/css/tokens.css`, light and dark. Sequential royal-blue ramp for maps (luminous in dark). Status colours are reserved for data status.
- **Map:** MapLibre with no basemap. Two shapes: **Tiles** (one equal-area hexagon per municipality, placed near its real location) and **Boundaries** (official PSGC polygons, simplified, with province outlines and labels). Hatching marks "no value". Hover shows a card, colour changes ease, zoom controls are custom.
- **Motion:** only where it answers an action (metric change eases colours, selection pans the map, bars grow). Reduced motion is respected.
- **Checks:** `node scripts/check-contrast.mjs` enforces 4.5:1 for every text pair in both themes; `tokens.test.ts` keeps tokens complete and the display face sans-serif.
