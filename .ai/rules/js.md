---
paths:
  - 'resources/js/{app.tsx,components/navigation-overlay.tsx}'
---

# Js

## Navigation overlay replaces progress bar
Page visits use NavigationOverlay. createInertiaApp progress is false. Show on router `before` and hide on `navigate`/`finish` — never rely on `start`: clicks on `prefetch` Links served from the cache fire only `before` + `navigate` (cached: true). Ignore async, prefetch and partial (`only`/`except`) visits so cart qty/add and debounced search stay quiet. Keep it visible at least ~1100ms so a fast local visit is still obvious.
