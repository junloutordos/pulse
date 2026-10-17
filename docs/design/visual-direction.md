# Visual direction

Chosen 17 Oct 2026 by the project owner: **Atlas, with sans-serif headings.** Deepened on 17 Oct 2026 after review ("not premium, too generic"), keeping the same direction.

- **Idea:** the map is the hero. A deep navy shell frames calm paper-coloured pages; the choropleth carries the colour.
- **Workflow:** the six steps are a real sequence, so they are the navigation (a numbered stepper) instead of eyebrow labels above every heading.
- **Type:** one clean sans-serif family, Hanken Grotesk, for titles, text, tables and figures (tabular figures). Sentence case everywhere. Bundled via `@fontsource-variable`, so the app works offline.
- **Surfaces:** hierarchy comes from tone steps (paper, white surface, sunken surface), a 1px ring and, for floating cards only, one soft shadow. No gradients, no emoji, no tracked all-caps labels. Radius differs by role: 6px controls, 10px groups, 16px panels, 0 for the full-bleed map.
- **Colour:** royal blue. Tokens in `resources/css/tokens.css`, light and dark. Sequential royal-blue ramp for maps (luminous in dark). Status colours are reserved for data status.
- **Map:** MapLibre with no basemap. Two shapes: **Tiles** (one equal-area hexagon per municipality, placed near its real location) and **Boundaries** (official PSGC polygons, simplified, with province outlines and labels). Hatching marks "no value". Hover shows a card, colour changes ease, zoom controls are custom.
- **Motion:** short and quiet (120, 200 and 320 ms tokens), only where it answers an action. Pages rise in; the stepper pill slides between steps; on Priority, rows glide to their new places when a weight changes and show how far they moved; the map sweeps in north to south once, colours ease between metrics, and selecting a municipality pans the map and pulses its outline; key figures count up once. Reduced motion is respected everywhere (CSS and script).
- **Interaction:** Ctrl/Cmd+K jumps to any screen or municipality; one selected municipality follows you through the workflow; map and priority views live in the address so they can be shared; decisions confirm with a toast and can be undone; the Pipeline chart has a hover card, drag-to-select, group buttons and a table view.
- **Quality gates:** axe accessibility checks on every screen in light and dark, focus management and a skip link, Playwright visual baselines (light, dark, desktop, phone), Lighthouse 100 for accessibility and best practices.
- **Checks:** `node scripts/check-contrast.mjs` enforces 4.5:1 for every text pair in both themes; `tokens.test.ts` keeps tokens complete and the display face sans-serif.
