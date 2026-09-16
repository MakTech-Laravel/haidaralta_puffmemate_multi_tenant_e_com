---
paths:
    - resources/css/app.css
---

# Css

## Light-only red/black/white theme

Brand tokens in resources/css/app.css are red / black / white. The site is light-only: `:root` and `.dark` share the same light tokens. use-appearance always coerces dark/system to light; HandleAppearance always shares appearance=light; app.blade.php never adds the dark class. Do not reintroduce a real dark palette without an explicit product decision.

## Red fills, black text tokens

Red is for fills only (bg-primary, sidebar logo tile, rings). Keep --accent-foreground and --sidebar-accent-foreground black so active nav / user menu text stays readable. Soft red tint backgrounds are fine; do not set accent text to red.

## Global brand scrollbars

Site-wide scrollbars live in `resources/css/app.css` (`--scrollbar-*` tokens): gray track, brand-red thumb (`--primary`) by default, darker red on hover, black on active. Use Firefox/Chromium `scrollbar-color` + WebKit `::-webkit-scrollbar*` so nested overflow areas match. Prefer `scrollbar-none` / `scrollbar-thin` utilities for local overrides; do not restyle scrollbars per page.
