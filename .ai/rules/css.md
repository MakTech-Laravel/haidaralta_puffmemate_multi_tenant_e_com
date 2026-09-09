---
paths:
  - resources/css/app.css
---

# Css

## Light-only red/black/white theme
Brand tokens in resources/css/app.css are red / black / white. The site is light-only: `:root` and `.dark` share the same light tokens. use-appearance always coerces dark/system to light; HandleAppearance always shares appearance=light; app.blade.php never adds the dark class. Do not reintroduce a real dark palette without an explicit product decision.

## Red fills, black text tokens
Red is for fills only (bg-primary, sidebar logo tile, rings). Keep --accent-foreground and --sidebar-accent-foreground black so active nav / user menu text stays readable. Soft red tint backgrounds are fine; do not set accent text to red.
