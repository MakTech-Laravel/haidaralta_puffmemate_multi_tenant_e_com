---
paths:
    - resources/css/app.css
---

# Css

## Light-only grey / black / white theme with one blue accent (single source of truth)

All colors live in `resources/css/app.css`, in two commented layers. Nothing else may define site colors (the DB `theme_color` only feeds the PWA manifest and browser-bar meta; there is no runtime `--primary` override). The look is Apple-inspired: white canvas, light-grey surfaces, near-black ink and a single blue accent. There is no green anywhere.

**Layer 1: `--palette-*` (the only place with hex).** Reference: client storefront / checkout / portal reference screenshots.

- `--palette-black` `#1D1D1F` near-black (dark banners, announcement bar, hero overlay base, headings); `--palette-black-soft` `#2C2C2E` hover on dark surfaces
- `--palette-white` `#FFFFFF` page canvas, cards, inputs; `--palette-grey-50` `#F5F5F7` soft surfaces (sidebar frame, section bands, tiles, chips, fallbacks); `--palette-grey-100` `#ECECF0` hover fill; `--palette-grey-300` `#C7C7CC` scrollbar thumb
- `--palette-ink` `#1D1D1F` text; `--palette-ink-muted` `#6E6E73` secondary text; `--palette-line` `#E5E5EA` borders; `--palette-line-strong` `#D2D2D7` input borders
- `--palette-apple-grey` `#8E8E93` / `-100` `#F2F2F7`: icons, placeholders, disabled UI. Never body text (fails AA on white)
- `--palette-blue-600` `#2B70DB` accent (primary buttons, links, selected state, focus ring); `-700` `#1F5CC0` hover; `-300` `#9BBCF0`; `-100` `#E3EEFE`; `-50` `#F3F8FF` selected option card
- `--palette-danger` `#8B3A3A` (+ `-tint`), `--palette-warning` `#8A5A14` (+ `-tint`), `--palette-star` (review stars)

**Layer 2: semantic roles** (`--background`, `--primary`, `--primary-tint`, `--ink-surface`, `--secondary`, `--muted`, `--destructive`, `--success`, `--warning`, `--fallback`, `--scrim`, `--chart-*`, `--sidebar-*`...) reference the palette. Components use the role utilities only (`bg-primary`, `bg-primary-tint`, `bg-ink-surface`, `text-ink-surface-foreground`, `text-muted-foreground`, `bg-fallback`, `bg-scrim`, `bg-success-tint`...). Do not hardcode hex or Tailwind palette colors (`red-500`, `bg-black`, `yellow-*`) in components.

To re-color the site: edit a `--palette-*` value, rebuild. Add a comment for every new variable.

## Rules

- Page canvas is `--background` (white); cards are white with a hairline border; quiet surfaces and section bands use `bg-muted` (`#F5F5F7`).
- `--primary` (blue) is for actions only: buttons, links, selected state, active icon, focus ring. Dark banners, announcement bar, hero overlays and the auth brand plane use `bg-ink-surface` / `text-ink-surface-foreground`, never `bg-primary`.
- Selected option cards (delivery, payment, package) use `border-primary bg-primary-tint ring-1 ring-primary`. Selected chips/tabs may use `bg-foreground text-background`.
- Section/image fallbacks and skeletons use `bg-fallback`. Modal/sheet backdrops use `bg-scrim`, not `bg-black/*`.
- Default `Button` is a solid blue pill (`bg-primary`); `secondary` is the soft grey fill; `outline` is white with a hairline border; `destructive` is a muted-brick fill. Radius scale: sm 8, md 10, lg 12, xl 16, 2xl 20, 3xl 28px (`--radius` = 0.75rem). Inputs/selects are `h-10 rounded-xl`; cards `rounded-2xl`; dialogs `rounded-2xl`.
- Status badges (`lib/order-badges.ts`) use success (blue tint) / warning / destructive / primary tints so failures are distinguishable.
- Hex is unavoidable only where CSS vars cannot reach (map SDK markers `lib/maps/types.ts`, email and 503 Blade templates, SVG presentation attributes in `navigation-overlay.tsx`, the pre-paint `html` background in `app.blade.php`). Those mirror the palette and carry a "keep in sync with app.css" comment.
- The site is light-only: `:root` and `.dark` share the same tokens. use-appearance always coerces dark/system to light; HandleAppearance always shares appearance=light; app.blade.php never adds the dark class. Do not reintroduce a real dark palette without an explicit product decision.
- `@layer base` in `app.css` holds the global border color, scrollbar styling, body colors and heading tracking. Keep the `@layer base {` line intact.

## Global brand scrollbars

Site-wide scrollbars live in `resources/css/app.css` (`--scrollbar-*` tokens): light track, grey thumb, darker on hover/active. Use Firefox/Chromium `scrollbar-color` + WebKit `::-webkit-scrollbar*` so nested overflow areas match. Prefer `scrollbar-none` / `scrollbar-thin` utilities for local overrides; do not restyle scrollbars per page.
