---
paths:
    - resources/css/app.css
---

# Css

## Light-only grey-green theme (single source of truth)

All colors live in `resources/css/app.css`, in two commented layers. Nothing else may define site colors (the DB `theme_color` only feeds the PWA manifest and browser-bar meta; there is no runtime `--primary` override).

**Layer 1: `--palette-*` (the only place with hex).** Reference: client Product & Price Guide.

- `--palette-green-900` `#174B36` brand grey-green (banners, prices, links, active states, primary buttons); `-950` `#103A2A` darkest (hover, scrim base); `-500` `#4F8A6B`; `-200` `#A9C4B4`
- `--palette-sage-50` `#F3F5F2` page canvas; `--palette-sage-100` `#EAF0EB` chips, soft buttons, footer, image/section fallbacks
- `--palette-ink` `#202923` text; `--palette-ink-muted` `#5F6863` secondary text; `--palette-line` `#D9E0DA` borders
- `--palette-apple-grey` `#8E8E93` / `-100` `#F2F2F7`: icons, placeholders, disabled UI. Never body text (fails AA on white)
- `--palette-danger` `#8B3A3A` (+ `-tint`), `--palette-warning` `#8A5A14` (+ `-tint`), `--palette-star` (review stars)

**Layer 2: semantic roles** (`--background`, `--primary`, `--secondary`, `--muted`, `--destructive`, `--success`, `--warning`, `--fallback`, `--scrim`, `--chart-*`, `--sidebar-*`...) reference the palette. Components use the role utilities only (`bg-primary`, `text-muted-foreground`, `bg-fallback`, `bg-scrim`, `bg-success-tint`...). Do not hardcode hex or Tailwind palette colors (`red-500`, `bg-black`, `yellow-*`) in components.

To re-color the site: edit a `--palette-*` value, rebuild. Add a comment for every new variable.

## Rules

- Page canvas is `--background` (`#F3F5F2`); cards are white.
- Section/image fallbacks and skeletons use `bg-fallback` (sage) or `bg-primary`. Never black, yellow or red. Modal/sheet backdrops use `bg-scrim`, not `bg-black/*`.
- Default `Button` is solid `bg-primary text-primary-foreground`; `secondary` is the soft sage fill; `destructive` is a real muted-brick fill. Badges/alerts have `destructive`, `success`, `warning` tints.
- Status badges (`lib/order-badges.ts`) use success / warning / destructive / primary tints so failures are distinguishable.
- Hex is unavoidable only where CSS vars cannot reach (map SDK markers `lib/maps/types.ts`, email and 503 Blade templates, SVG presentation attributes in `navigation-overlay.tsx`). Those mirror the palette and carry a "keep in sync with app.css" comment.
- The site is light-only: `:root` and `.dark` share the same tokens. use-appearance always coerces dark/system to light; HandleAppearance always shares appearance=light; app.blade.php never adds the dark class. Do not reintroduce a real dark palette without an explicit product decision.

## Global brand scrollbars

Site-wide scrollbars live in `resources/css/app.css` (`--scrollbar-*` tokens): sage track, apple-grey thumb, green hover/active. Use Firefox/Chromium `scrollbar-color` + WebKit `::-webkit-scrollbar*` so nested overflow areas match. Prefer `scrollbar-none` / `scrollbar-thin` utilities for local overrides; do not restyle scrollbars per page.
